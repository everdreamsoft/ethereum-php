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

        $sandra = SandraManager::getSandra();

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

            $networkId = '5777';


            // By default ContractEventProcessor
            // process any Transaction from Block-0 to latest Block (at script run time).
            new ContractEventProcessor($web3, $smartContracts,$this,$this->fromBlockNumber,null,true);
        }
        catch (\Exception $exception) {

            throw new $exception;
        }








    }

    public function getEvents(){

        try {
            echo "<h3>What's up on $url</h3>";
            $eth = new Ethereum($url);

            $abi =json_decode("");


            $contract = new SmartContract($abi, '0x6ebeaf8e8e946f0716e6533a6f2cefc83f60e8ab', $eth);

            //take a look here
            //  https://github.com/digitaldonkey/ethereum-php-eventlistener/tree/master/app/src
            // $block_latest = $eth->eth_getBlockByNumber(new EthBlockParam('latest'), new EthB(FALSE));
            $i = 8046755;
            $counter = 0;


            $ethB = new EthB(TRUE);

            while ($counter < 400) {

                //echo memory_get_usage()." - Alloc memory \n";


                $myBlockParam = new EthBlockParam($i);


                $block_latest = $eth->eth_getBlockByNumber($myBlockParam, $ethB);

                //print_r($block_latest->toArray());
                echo $block_latest->getProperty('hash') . " - $counter block id =  $i\n";
                //sleep(0.1);


                foreach ($block_latest->transactions as $tx) {

                    $countTX = 0;


                    if (isset($tx->to)) {
                        //echo $tx->to->hexVal()." - tx should be a contract \n";

                        //echo $tx->to->hexVal()." ".$contract->getAddress() ."\n";


                        if (is_object($tx->to) && $tx->to->hexVal() == $contract->getAddress()) {
                            echo "weeeee FOUNDDDDD \n";
                            //$contract = $this->contracts[$tx->to->hexVal()];
                            $receipt = $eth->eth_getTransactionReceipt($tx->hash);


                            if (count($receipt->logs)) {

                                foreach ($receipt->logs as $filterChange) {
                                    $event = $contract->processLog($filterChange);
                                    //var_dump($event);
                                    //die();
                                    if ($event->hasData()) {

                                        //var_dump($event);
                                        //var_dump($event->toArray());
                                        var_dump($event->getName());
                                    }
                                }
                            }

                        }
                    }

                }


                unset($eth);
                // unset($myBlockParam);
                // unset($ethB);


                $eth = new Ethereum($url);


                $i--;
                $counter++;
            }

            $filterChange = new FilterChange(null,null);
            $filterChange->address = $contract;
            //$address = new
            $val = $contract->processLog($filterChange);





            echo $val->hexVal();


            print_r(($this->status($eth)));


            // Show results.
            echo "<p style='color: forestgreen;'>The Address submitted this hash is:<br />";
            echo $test->hexVal()."</p>";




        }
        catch (\Exception $exception) {
            echo "<p style='color: red;'>We have a problem:<br />";
            echo $exception->getMessage() . "</p>";
            echo "<pre>" . $exception->getTraceAsString() . "</pre>";
            die();
        }
        echo "<hr />";

    }















}