<?php

namespace Ethereum\Eventlistener;

use CsCannon\AssetCollectionFactory;
use CsCannon\Blockchains\Blockchain;
use CsCannon\Blockchains\BlockchainBlock;
use CsCannon\Blockchains\BlockchainBlockFactory;
use CsCannon\Blockchains\BlockchainEventFactory;
use CsCannon\Blockchains\Ethereum\EthereumAddressFactory;
use CsCannon\Blockchains\Ethereum\EthereumBlockchain;
use CsCannon\Blockchains\Ethereum\EthereumEvent;
use CsCannon\Blockchains\Ethereum\EthereumEventFactory;
use CsCannon\Blockchains\Ethereum\Interfaces\ERC20;
use CsCannon\Blockchains\Ethereum\Interfaces\ERC721;
use CsCannon\SandraManager;
use \Ethereum\DataType\Block;
use Ethereum\DataType\EthD32;
use Ethereum\DataType\EthQ;
use Ethereum\DataType\Transaction;
use Ethereum\Ethereum;
use Ethereum\Sandra\EthereumContractFactory;
use SandraCore\DatabaseAdapter;
use SandraCore\System;

class ContractEventProcessor extends BlockProcessor {

    /* @var \Ethereum\SmartContract[] $contracts */
    private $contracts;
    private $processor ;
    private $txConceptId ;

    /**
     * BlockProcessor constructor.
     *
     * @param Ethereum $web3
     *
     * @param  \Ethereum\SmartContract[] $contracts
     *   This function will be called at each block.
     *
     * @param int $fromBlockNumber
     *
     * @param \Ethereum\BlockProcessor $processor
     *
     * @param int|null $toBlockNumber
     *   Will default to latest at script start time or Block or 0.
     *
     * @param bool $persistent
     *   Make sure we can resume with latest block after script restart.
     *
     * @param float $timePerLoop
     *   Time for each request in seconds.
     *    - Use for throttling or adjust to BlockTime for continuous evaluation.
     *    - If processing takes more time, lowering this value won't help.
     *
     * @throws \Exception
     */
    public function __construct(
      Ethereum $web3,
      array $contracts,
      \Ethereum\BlockProcessor $processor,
      $fromBlockNumber = null,
      $toBlockNumber = null,
      ?bool $persistent = false,
      ?float $timePerLoop = 0.01
    )
    {
        // Add contracts.
        $this->contracts = self::addressifyKeys($contracts);
        $args = func_get_args();
        $args[1] = array($this, 'processBlock');
        $this->processor = $processor ;
        parent::__construct(...$args);
    }


    /**
     * @param \Ethereum\DataType\Block $block
     * @throws \Exception
     */
    protected function processBlock(?Block $block) {

        echo '### Block number ' . $block->number->val() ;
        echo " memory ".memory_get_usage() . PHP_EOL;
        if (!$this->txConceptId) {
            $this->txConceptId = $this->processor->sandra->conceptFactory->getConceptFromShortnameOrId(Blockchain::$txidConceptName);
        }



        $txidConcept = $this->txConceptId ;

        //print_r($this->contracts);

        if (count($block->transactions)) {
            foreach ($block->transactions as $tx) {

                /** @var Transaction $tx */

                if (is_object($tx->to) && isset($this->contracts[$tx->to->hexVal()])) {

                    $contract = $this->contracts[$tx->to->hexVal()];
                    $receipt = $this->web3->eth_getTransactionReceipt($tx->hash);

                    if (count($receipt->logs)) {
                        foreach ($receipt->logs as $filterChange) {
                            $event = $contract->processLog($filterChange,$this->contracts);
                            if (is_null($event)) continue ;

                            echo"processing".$event->getName(), "\n";
                              //  $assetCollection = new AssetCollectionFactory();

                            if ($event->hasData() && $event->getName() == 'Transfer') {
                                echo"Transfer found".$event->getName(), "\n";

                                //is the tx already in DB ?

                                $txHashUnid = $this->processor->sandra->systemConcept->get(Blockchain::$txidConceptName);


                                //$me->create(EthereumBlockchain::class,)
                                $eventData = $event->getData();
                                //$getAddress = $ethereumAddressFactory->get($eventData['from'],true);
                                $ethereumAddressFactory = $this->processor->rpcProvider->getBlockchain()->getAddressFactory();
                                $blockchain =  $this->processor->rpcProvider->getBlockchain();
                                if($this->processor->bypassKnownTx && DatabaseAdapter::searchConcept($this->processor->rpcProvider->transform($txidConcept,$tx->hash->val()),$txHashUnid,$ethereumAddressFactory->system)){
                                    //we bypass known tx if set up so and if tx exists
                                    echo"tx alrady in DB bypass ".$this->processor->rpcProvider->transform($txidConcept,$tx->hash->val());
                                    continue ;

                                }


                                if (isset ( $eventData['_from'])){
                                    $from = $eventData['_from']->hexval();
                                    $to = $eventData['_to']->hexval();
                                    $fromEntity =  $ethereumAddressFactory->get($from,true);
                                    $toEntity = $ethereumAddressFactory->get($to,true);

                                }

                                if (isset ( $eventData['from'])){
                                    $from = $eventData['from']->hexval();
                                    $to = $eventData['to']->hexval();
                                    $fromEntity =  $ethereumAddressFactory->get($from,true);
                                    $toEntity = $ethereumAddressFactory->get($to,true);

                                }
                                $quantity = null ;

                                if (isset ( $eventData['value'])){
                                    $quantity = $eventData['value']->val();


                                }


                                $blockId = $block->number->val();
                                $blockFactory = new BlockchainBlockFactory($blockchain);
                                $sandraBlock = $blockFactory->getOrCreateFromRef(BlockchainBlockFactory::INDEX_SHORTNAME,$blockId);

                                //ETHQ
                                /** @var EthQ $tokenId */
                                if (isset($eventData['tokenId'])) {
                                    $tokenId = $eventData['tokenId'] ;
                                    $tokenIdString = $tokenId->val();
                                    echo" with token ID = ".$tokenIdString."\n";
                                }



                                try{

                                    $standard =$contract->csEntity->getStandard();
                                   echo PHP_EOL. " we have a standasrd". $standard->getStandardName();

                                   if ($standard instanceof ERC20) {

                                      $contract->getBalance($fromEntity, $sandraBlock);
                                       $contract->getBalance($toEntity, $sandraBlock);
                                   }

                                    if ($standard instanceof ERC721) {

                                        $finalOwner = $contract->ownerOf($tokenIdString,$sandraBlock);
                                        $standard->setTokenId($tokenIdString);
                                        $quantity = 1 ;
                                    }

                                }
                                catch (\Exception $e){

                                    echo"Exception ".$e->getMessage();
                                   //echo $this->web3->debugHtml;
                                   //die();

                                }



                                //transformations
                                $correctedTx = $this->processor->rpcProvider->transform($txidConcept,$tx->hash->val());



                                $ethereumEventFactory =  $this->processor->rpcProvider->getBlockchain()->getEventFactory();
                                $ethereumContractFactory = $this->processor->rpcProvider->getBlockchain()->getContractFactory();
                                $sandraContract = $ethereumContractFactory->get($contract->getAddress());
                                $ethereumEventFactory->create($blockchain,$fromEntity,$toEntity,$sandraContract,$correctedTx,



                                 $block->timestamp->val(),$sandraBlock,$standard,$quantity );



                                echo"hello Transfer $from to $to ";







                            }
                        }
                    }
                }
            }
        }

        $this->processor->lastValidProcessedBlock = $block->number->val();

    }

    /**
     * @param $contracts
     * @return \Ethereum\CrystalSpark\CsSmartContract[]
     */
    private static function addressifyKeys($contracts){

        foreach ($contracts as $i => $c) {
            /* @var \Ethereum\CrystalSpark\CsSmartContract $c */
            $contracts[strtolower($c->getAddress())] = $c;
            unset($contracts[$i]);
        }
        return $contracts;
    }

}
