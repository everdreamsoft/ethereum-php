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

    public $rpcProvider = null ;
    public $fromBlockNumber = null ;
    public $sandra = null ;

    public function __construct(RpcProvider $provider, System $sandra, $fromBlockNumber = 0)
    {

        $this->rpcProvider = $provider ;
        $this->fromBlockNumber = $fromBlockNumber ;
        SandraManager::setSandra($sandra);
        $this->sandra = $sandra ;


    }


    public function trackContract($contract,$abiArray=null){

        $sandra = SandraManager::getSandra();

        if (!is_array($contract)){
            $contractArray[] = $contract ;
        }
        else{
            $contractArray = $contract ;
        }

        $contractFactory = $this->rpcProvider->getBlockchain()->getContractFactory();

        // $contractFactory = new EthereumContractFactory();

        //print_r($contractArray);


        //we search matching addressses
        $conceptsArray = DatabaseAdapter::searchConcept($contractArray,$sandra->systemConcept->get(BlockchainContractFactory::MAIN_IDENTIFIER),$sandra,'',$sandra->systemConcept->get(BlockchainContractFactory::$file));
        $contractFactory->conceptArray = $conceptsArray ;//we preload the factory with found concepts

        $contractFactory->populateLocal();



        foreach ($contractArray as $index => $contractAddress){

            $abi = null ;
            //do we have a related abi
            if(is_array($abiArray)){
                if (isset ($abiArray[$index])){

                    $abi = $abiArray[$index] ;
                }

            }
            //we will look for the abi in etherscan
            if (!$abi) {

                $client = new Client();
                try {
                    $strJsonFileContents = "[
	{
        \"constant\": false,
		\"inputs\": [
			{
				\"name\": \"spender\",
				\"type\": \"address\"
			},
			{
				\"name\": \"amount\",
				\"type\": \"uint256\"
			}
		],
		\"name\": \"approve\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"bool\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"nonpayable\",
		\"type\": \"function\"
	},
	{
		\"constant\": true,
		\"inputs\": [],
		\"name\": \"totalSupply\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"uint256\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"view\",
		\"type\": \"function\"
	},
	{
		\"constant\": false,
		\"inputs\": [
			{
				\"name\": \"sender\",
				\"type\": \"address\"
			},
			{
				\"name\": \"recipient\",
				\"type\": \"address\"
			},
			{
				\"name\": \"amount\",
				\"type\": \"uint256\"
			}
		],
		\"name\": \"transferFrom\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"bool\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"nonpayable\",
		\"type\": \"function\"
	},
	{
		\"constant\": true,
		\"inputs\": [
			{
				\"name\": \"account\",
				\"type\": \"address\"
			}
		],
		\"name\": \"balanceOf\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"uint256\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"view\",
		\"type\": \"function\"
	},
	{
		\"constant\": false,
		\"inputs\": [
			{
				\"name\": \"recipient\",
				\"type\": \"address\"
			},
			{
				\"name\": \"amount\",
				\"type\": \"uint256\"
			}
		],
		\"name\": \"transfer\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"bool\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"nonpayable\",
		\"type\": \"function\"
	},
	{
		\"constant\": true,
		\"inputs\": [
			{
				\"name\": \"owner\",
				\"type\": \"address\"
			},
			{
				\"name\": \"spender\",
				\"type\": \"address\"
			}
		],
		\"name\": \"allowance\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"uint256\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"view\",
		\"type\": \"function\"
	},
	{
		\"anonymous\": false,
		\"inputs\": [
			{
				\"indexed\": true,
				\"name\": \"from\",
				\"type\": \"address\"
			},
			{
				\"indexed\": true,
				\"name\": \"to\",
				\"type\": \"address\"
			},
			{
				\"indexed\": false,
				\"name\": \"value\",
				\"type\": \"uint256\"
			}
		],
		\"name\": \"Transfer\",
		\"type\": \"event\"
	},
	{
		\"anonymous\": false,
		\"inputs\": [
			{
				\"indexed\": true,
				\"name\": \"owner\",
				\"type\": \"address\"
			},
			{
				\"indexed\": true,
				\"name\": \"spender\",
				\"type\": \"address\"
			},
			{
				\"indexed\": false,
				\"name\": \"value\",
				\"type\": \"uint256\"
			}
		],
		\"name\": \"Approval\",
		\"type\": \"event\"
	}
]";



                    $result = json_decode($strJsonFileContents);
                    $abiRaw = $result ;
                    $abiRefined = $abiRaw;
                    $abi = $abiRefined;
                    $saveAbi = $abiRefined;

                    // $abi = stripslashes($abi);
                } catch (ClientException  $e) {
                    $abi = null;

                }
            }


            $contractEntity = $contractFactory->get($contractAddress,true,ERC20::init());

            if (!$contractEntity){


                $contractEntity = $contractFactory->create($contractAddress,true);
                $contractEntity->setAbi($saveAbi);

            }

            if ($abi){

                $contractEntity->setAbi(json_encode($saveAbi));
            }




        }







    }

    public function process(){

        // First we get tracked contracts



        $this->startLoop();











    }

    public function startLoop($isInfinite = true){

        $sandra = SandraManager::getSandra();

        $restartAfterBlocks = 1000 ;

        //


        //Then we process blocks from the lowest block of those contracts

        $contractFactory = $this->rpcProvider->getBlockchain()->getContractFactory();
        $contractFactory->populateLocal();
        /**@var BlockchainContractFactory $contractFactory */
        $contractFactory->populateBrotherEntities($contractFactory::ABI_VERB);

        foreach ($contractFactory->entityArray as $contract ){

            /** @var EthereumContract $contract */

            $abi = json_decode($contract->getAbi());
            $contractAddress = $contract->get($contractFactory::MAIN_IDENTIFIER);
            echo "address is $contractAddress";
            if (!is_array($abi)) continue ;

            try {

                $web3 = new Ethereum($this->rpcProvider->getHostUrl());
                $smartContract = new CsSmartContract($abi, $contractAddress, $web3,$this->rpcProvider,$contract);

                $smartContracts[] = $smartContract;
            }
            catch (\Exception $exception) {

                throw new $exception;
            }

            $abi = null ;


        }


        try {

            $web3 = new Ethereum($this->rpcProvider->getHostUrl());
            $networkId = '5777';

            $persistant = false ;
            if ($this->fromBlockNumber == 'latest') $persistant = true ;


            // By default ContractEventProcessor
            // process any Transaction from Block-0 to latest Block (at script run time).
            $contractProcessor =  new ContractEventProcessor($web3, $smartContracts,$this,$this->fromBlockNumber,$this->fromBlockNumber+$restartAfterBlocks,$persistant);
        }
        catch (\Exception $exception) {

            echo $exception->getMessage();
            throw new $exception;
        }



        echo "\n restarting lookp";
        sleep(15);
        $this->fromBlockNumber = $contractProcessor->toBlockNumber ;


        $this->startLoop($smartContracts, $isInfinite);






    }















}