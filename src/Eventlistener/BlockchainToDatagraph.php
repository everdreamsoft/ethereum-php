<?php

namespace Ethereum\Eventlistener;

use CsCannon\AssetCollectionFactory;
use CsCannon\Blockchains\Ethereum\EthereumAddressFactory;
use CsCannon\Blockchains\Ethereum\EthereumBlockchain;
use CsCannon\Blockchains\Ethereum\EthereumContractFactory;
use CsCannon\Blockchains\Ethereum\EthereumEvent;
use CsCannon\Blockchains\Ethereum\EthereumEventFactory;
use CsCannon\SandraManager;
use Ethereum\ApiConnector\RemoteFunctions;
use \Ethereum\DataType\Block;
use Ethereum\Ethereum;
use Ethereum\SmartContract;

class BlockchainToDatagraph extends BlockProcessor {

    /* @var \Ethereum\SmartContract[] $contracts */
    private $contracts;
    private $knownContracts ;
private $contractNoAbi;


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

            echo 'This block has ' . count($block->transactions) . PHP_EOL;
            foreach ($block->transactions as $tx) {

                $destinationIsContract = false ;

                if (is_object($tx->to)) {

                    //is it a contract ?



                    $receipt = $this->web3->eth_getTransactionReceipt($tx->hash);

                    if (count($receipt->logs)) {
                        $destinationIsContract = true ;

                        $contract = $this->getContract($tx->to->hexVal());
                        if (is_null($contract)) continue ;


                        foreach ($receipt->logs as $filterChange) {
                            $event = $contract->processLog($filterChange);
                            if (is_null($event)) continue ;
                            $me = new EthereumEventFactory();
                            //
                              //  $assetCollection = new AssetCollectionFactory();

                            if ($event->hasData() && $event->getName() == 'Transfer') {
                                //$me->create(EthereumBlockchain::class,)
                                $eventData = $event->getData();
                                //$getAddress = $ethereumAddressFactory->get($eventData['from'],true);

                                $from = $eventData['from']->hexval();



                                echo"hello Transfer $from";
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
            $contracts[$c->getAddress()] = $c;
            unset($contracts[$i]);
        }
        return $contracts;
    }


    private  function getContract($contract){

        if (isset( $this->knownContracts[$contract])){
        return $this->knownContracts[$contract]['object'] ;
        }

        if (isset( $this->contractNoAbi[$contract])){
            null ;
        }

        $sandra = SandraManager::getSandra();

        $contractFactory = new \Ethereum\Sandra\EthereumContractFactory($sandra);
       $sandraContract =  $contractFactory->get($contract);

       if (is_null($sandraContract)) $sandraContract = $contractFactory->create($contract,null);

       $abi = $sandraContract->getAbi() ;

       if (is_null($abi)){


           $abi = RemoteFunctions::getAbi($contract);
           $sandraContract->setAbi($abi);

           echo "Got ABI for $contract \n";
       }

       if (!$abi) return null ;


        if (!is_array($abi)){
            if($abi = json_decode($abi,1));
            else {
                $this->contractNoAbi[$contract] = 1 ;
                return  null ;
            }
        }

        echo "working on contract $contract \n";

        try {
            $smartContract = new SmartContract($abi, $contract, $this->web3);
        } catch (\Exception $e) {
        }


        $this->knownContracts[$contract]['object'] = $smartContract;

       return $smartContract ;



    }

}
