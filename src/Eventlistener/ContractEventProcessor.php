<?php

namespace Ethereum\Eventlistener;

use CsCannon\AssetCollectionFactory;
use CsCannon\Blockchains\Ethereum\EthereumAddressFactory;
use CsCannon\Blockchains\Ethereum\EthereumBlockchain;
use CsCannon\Blockchains\Ethereum\EthereumEvent;
use CsCannon\Blockchains\Ethereum\EthereumEventFactory;
use \Ethereum\DataType\Block;
use Ethereum\DataType\EthD32;
use Ethereum\DataType\EthQ;
use Ethereum\DataType\Transaction;
use Ethereum\Ethereum;

class ContractEventProcessor extends BlockProcessor {

    /* @var \Ethereum\SmartContract[] $contracts */
    private $contracts;

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
      $fromBlockNumber = null,
      $toBlockNumber = null,
      ?bool $persistent = false,
      ?float $timePerLoop = 0.3
    )
    {
        // Add contracts.
        $this->contracts = self::addressifyKeys($contracts);
        $args = func_get_args();
        $args[1] = array($this, 'processBlock');
        parent::__construct(...$args);
    }


    /**
     * @param \Ethereum\DataType\Block $block
     * @throws \Exception
     */
    protected function processBlock(?Block $block) {

        echo '### Block number ' . $block->number->val() . PHP_EOL;
        $ethereumAddressFactory = new EthereumAddressFactory();
        //print_r($this->contracts);

        if (count($block->transactions)) {
            foreach ($block->transactions as $tx) {

                /** @var Transaction $tx */

                //echo "tx =   \n".$tx->hash->val();

                if ($tx->hash->val() == '00cb8f296775b4f14456b3d1b316327838fd3412104f8b341ff64344d1c8008d'){

                    echo "we found the tx";
                }

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
                                //$me->create(EthereumBlockchain::class,)
                                $eventData = $event->getData();
                                //$getAddress = $ethereumAddressFactory->get($eventData['from'],true);

                                if (isset ( $eventData['_from'])){
                                    $from = $eventData['_from']->hexval();
                                    $to = $eventData['_to']->hexval();

                                }

                                if (isset ( $eventData['from'])){
                                    $from = $eventData['from']->hexval();
                                    $to = $eventData['to']->hexval();

                                }



                                echo"hello Transfer $from to $to ";


                                //ETHQ
                                /** @var EthQ $tokenId */
                                if (isset($eventData['_tokenId'])) {
                                    $tokenId = $eventData['_tokenId'] ;
                                    $tokenIdString = $tokenId->val();
                                   echo" with token ID = ".$tokenIdString."\n";
                                }




                            }
                        }
                    }
                }
            }
        }

    }

    /**
     * @param $contracts
     * @return \Ethereum\SmartContract[]
     */
    private static function addressifyKeys($contracts){

        foreach ($contracts as $i => $c) {
            /* @var \Ethereum\SmartContract $c */
            $contracts[strtolower($c->getAddress())] = $c;
            unset($contracts[$i]);
        }
        return $contracts;
    }

}
