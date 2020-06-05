<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-05
 * Time: 09:56
 */

namespace Ethereum;


use CsCannon\Blockchains\BlockchainContractFactory;
use CsCannon\Blockchains\Ethereum\Interfaces\ERC20;
use CsCannon\Blockchains\Interfaces\UnknownStandard;
use CsCannon\Blockchains\Klaytn\KlaytnContractFactory;
use CsCannon\Blockchains\RpcProvider;
use CsCannon\SandraManager;

use Ethereum\CrystalSpark\CsSmartContract;
use Ethereum\DataType\EthB;
use Ethereum\DataType\EthBlockParam;
use Ethereum\DataType\FilterChange;
use Ethereum\Eventlistener\BlockchainToDatagraph;
use Ethereum\Eventlistener\ContractEventProcessor;
use Ethereum\Sandra\EthereumContract;
use Ethereum\Sandra\EthereumContractFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use mysql_xdevapi\Exception;
use SandraCore\DatabaseAdapter;
use SandraCore\EntityFactory;
use SandraCore\System;


class CallableEvents extends SmartContract {
    public function onCalledTrigger1 (EthEvent $event) {
        echo '### ' . substr(__FUNCTION__, 2) . "(\Ethereum\EmittedEvent)\n";
        var_dump($event);
    }
    public function onCalledTrigger2 (EthEvent $event) {
        echo '### ' . substr(__FUNCTION__, 2) . "(\Ethereum\EmittedEvent)\n";
        var_dump($event);
    }
}

class BlockProcessor
{

    public $rpcProvider = null;
    public $fromBlockNumber = null;
    public $sandra = null;
    public $persistStream = null; // the stream is the name of the variable in the database where we persist the last synched block
    public $bypassKnownTx = true; // the stream is the name of the variable in the database where we persist the last synched block
    public $lastValidProcessedBlock = 0;
    public $contractFactory = null ;

    public function __construct(RpcProvider $provider, System $sandra, $fromBlockNumber = 0)
    {

        $this->rpcProvider = $provider;
        $this->fromBlockNumber = $fromBlockNumber;
        SandraManager::setSandra($sandra);
        $this->sandra = $sandra;


    }


    public function trackContract(BlockchainContractFactory $contractFactory, $abiArray = null)
    {

        $sandra = SandraManager::getSandra();




//ins't it already populated ?
        $contractList = $contractFactory->populateLocal();
        $this->contractFactory = $contractFactory ;


        foreach ($contractList as $contractEntity) {

            $id = $contractEntity->get('id');


            echo " tracking contract id $id" . PHP_EOL;


            $contractFactory->populateBrotherEntities($contractFactory::ABI_VERB);
            $abi = $contractEntity->getAbi();


            //do we have a related abi


            //we will look for the abi in etherscan
            if (!$abi) {



                $standard = $contractEntity->getStandard();
                if ($standard instanceof UnknownStandard)continue ;


                $client = new Client();

                try {
                    $strJsonFileContents = $standard->getInterfaceAbi();



                    $result = json_decode($strJsonFileContents);
                    $abiRaw = $result;
                    $abiRefined = $abiRaw;
                    $abi = $abiRefined;
                    $saveAbi = $abiRefined;

                    // $abi = stripslashes($abi);
                } catch (ClientException  $e) {
                    $abi = null;

                }


                if ($abi) {

                    $contractEntity->setAbi(json_encode($saveAbi));
                }

            }






        }


    }

    public function process($iterations=1000)
    {

        // First we get tracked contracts

        $smartContracts = $this->prepare();
        $this->start($smartContracts,$iterations);


        // $this->startLoop();


    }

    public function startLoop($isInfinite = true)
    {

        $sandra = SandraManager::getSandra();

        $restartAfterBlocks = 1000;

        //


        //Then we process blocks from the lowest block of those contracts

        $contractFactory = $this->rpcProvider->getBlockchain()->getContractFactory();
        $contractFactory->populateLocal();
        /**@var BlockchainContractFactory $contractFactory */
        $contractFactory->populateBrotherEntities($contractFactory::ABI_VERB);

        foreach ($contractFactory->entityArray as $contract) {

            /** @var EthereumContract $contract */

            $abi = json_decode($contract->getAbi());
            $contractAddress = $contract->get($contractFactory::MAIN_IDENTIFIER);
            echo "address is $contractAddress";
            if (!is_array($abi)) continue;

            try {

                $web3 = new Ethereum($this->rpcProvider->getHostUrl());
                $smartContract = new CsSmartContract($abi, $contractAddress, $web3, $this->rpcProvider, $contract);

                $smartContracts[] = $smartContract;
            } catch (\Exception $exception) {

                throw new $exception;
            }

            $abi = null;


        }


        try {
            $sandra = SandraManager::getSandra();

            $web3 = new Ethereum($this->rpcProvider->getHostUrl());
            $networkId = '5777';

            $persistant = false;
            if ($this->fromBlockNumber == 'latest') $persistant = true;


            // By default ContractEventProcessor
            // process any Transaction from Block-0 to latest Block (at script run time).
            $from = $this->fromBlockNumber;
            $to = $this->fromBlockNumber + $restartAfterBlocks;
            $contractProcessor = new ContractEventProcessor($web3, $smartContracts, $this, $from, $to, $persistant);
            echo "finished syncing {$restartAfterBlocks} blocks ({from} to {to})\n";

            if ($this->persistStream) {

                echo PHP_EOL. "leaving live stream on datagraph :".$sandra->tablePrefix ."with RPC ". $this->rpcProvider->getHostUrl()  ;

                $liveFactory = new EntityFactory("liveSync", 'liveData', SandraManager::getSandra());
                $liveFactory->populateLocal();
                $liveData = $liveFactory->last("sync", $this->persistStream);

                if ($liveData) {
                    $liveData->createOrUpdateRef('lastBlock', $to);
                }
            }


        } catch (\Exception $exception) {

            echo $exception->getMessage();
            throw new $exception;
        }


        //echo "\n restarting lookp";
        //sleep(2);
        //$this->fromBlockNumber = $contractProcessor->toBlockNumber ;


        // $this->startLoop($smartContracts, $isInfinite);


    }

    public function start($smartContracts,$iterations)
    {

        echo "Starting from block $this->fromBlockNumber  with iteration:$iterations".PHP_EOL;



        try {
            $sandra = SandraManager::getSandra();

            $web3 = new Ethereum($this->rpcProvider->getHostUrl());
            $networkId = '5777';

            $persistant = false;
            if ($this->fromBlockNumber == 'latest') $persistant = true;


            // By default ContractEventProcessor
            // process any Transaction from Block-0 to latest Block (at script run time).
            $from = $this->fromBlockNumber;
            $to = $this->fromBlockNumber + $iterations;
            echo "To Block $to".PHP_EOL;
            $contractProcessor = new ContractEventProcessor($web3, $smartContracts, $this, $from, $to, $persistant);
            echo "finished syncing {$iterations} blocks ({from} to {to}) to block $persistant\n";


            if ($this->persistStream) {

                echo PHP_EOL. "leaving live stream on datagraph :".$sandra->tablePrefix ."with RPC ". $this->rpcProvider->getHostUrl()  ;

                $liveFactory = new EntityFactory("liveSync", 'liveData', SandraManager::getSandra());
                $liveFactory->populateLocal();
                $liveData = $liveFactory->last("sync", $this->persistStream);

                if ($liveData) {
                    $liveData->createOrUpdateRef('lastBlock', $to);
                }
            }


        } catch (\Exception $exception) {



            $liveFactory = new EntityFactory("liveSync", 'liveData', SandraManager::getSandra());
            $liveFactory->populateLocal();
            $liveData = $liveFactory->last("sync", $this->persistStream);

            if ($liveData && $this->lastValidProcessedBlock) {
                $liveData->createOrUpdateRef('lastBlock', $this->lastValidProcessedBlock);
                echo PHP_EOL. "lastBlock saved".$this->lastValidProcessedBlock  ;
            }


            echo $exception->getMessage();
           // throw new $exception;
        }


        //echo "\n restarting lookp";
        //sleep(2);
        //$this->fromBlockNumber = $contractProcessor->toBlockNumber ;


        // $this->startLoop($smartContracts, $isInfinite);


    }

    public function prepare()
    {

        $sandra = SandraManager::getSandra();


        //Then we process blocks from the lowest block of those contracts

        $contractFactory = $this->contractFactory ;
        $contractFactory->populateLocal();
        /**@var BlockchainContractFactory $contractFactory */
        $contractFactory->populateBrotherEntities($contractFactory::ABI_VERB);

        foreach ($contractFactory->entityArray as $contract) {

            /** @var EthereumContract $contract */

            if (!$abi = json_decode($contract->getAbi())){
                echo PHP_EOL."Invalid ABI for ocntract".$contract->getId().PHP_EOL;
                continue ;

            }

            $standard = $contract->getStandard();
            $contractAddress = $contract->get($contractFactory::MAIN_IDENTIFIER);


            echo PHP_EOL."address is $contractAddress";


            if($standard){
                echo PHP_EOL."Standard For".$standard->getStandardName().PHP_EOL;

            }
            else{
                echo PHP_EOL."Contract has no standards".PHP_EOL;
                continue ;
            }


            if (!is_array($abi)){

                echo PHP_EOL."no ABI found for $contractAddress";
                continue;
            }

            try {

                $web3 = new Ethereum($this->rpcProvider->getHostUrl());
                $smartContract = new CsSmartContract($abi, $contractAddress, $web3, $this->rpcProvider, $contract);

                $smartContracts[] = $smartContract;
            } catch (\Exception $exception) {

                throw new $exception;
            }

            $abi = null;


        }

        //  throw new \Exception("exit exeption");


        return $smartContracts ;


    }

}
