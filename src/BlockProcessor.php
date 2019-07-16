<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-05
 * Time: 09:56
 */

namespace Ethereum;


use Ethereum\DataType\EthB;
use Ethereum\DataType\EthBlockParam;
use Ethereum\DataType\FilterChange;
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



    public function trackContract($contract,$abiArray=null){

        $sandra = new System('',true);

        if (!is_array($contract)){
            $contractArray[] = $contract ;
        }
        else{
            $contractArray = $contract ;
        }

        $contractFactory = new EthereumContractFactory($sandra);

        print_r($contractArray);


      //we search matching addressses
        $conceptsArray = DatabaseAdapter::searchConcept($contractArray,$sandra->systemConcept->get($contractFactory::IDENTIFIER),$sandra,'',$sandra->systemConcept->get(EthereumContractFactory::$file));
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
                   $res = $client->request('GET', 'https://api.etherscan.io/api?module=contract&action=getabi&address=' . $contractAddress . '');
                   echo 'https://api.etherscan.io/api?module=contract&action=getabi
                &address=' . $contractAddress . '';



                   $result = json_decode($res->getBody());
                   $abiRaw = $result->result ;
                   $abiRefined = $abiRaw;
                   $abi = $abiRefined;
                   $saveAbi = $abiRefined;

                  // $abi = stripslashes($abi);
               } catch (ClientException  $e) {
                   $abi = null;

               }
           }


          $contractEntity = $contractFactory->get($contractAddress);

          if (!$contractEntity){


              $contractEntity = $contractFactory->create($contractAddress,$abi,true);

          }

          if ($abi){

              $contractEntity->setAbi($saveAbi);
          }




       }







    }

    public function process(){

        // First we get tracked contracts

        $sandra = new System('',true);

        //


        //Then we process blocks from the lowest block of those contracts

        $hosts = [
            // Start testrpc, geth or parity locally.
            'https://mainnet.infura.io/v3/a6e34ed067c74f25ba705456d73a471e/'
        ];

        $contractFactory = new EthereumContractFactory($sandra);
        $contractFactory->populateLocal();

        foreach ($contractFactory->entityArray as $contract ){

            /** @var EthereumContract $contract */

            $abi = json_decode($contract->getAbi(),1);
            $contractAddress = $contract->get(EthereumContractFactory::IDENTIFIER);
            echo "address is $contractAddress";



            try {
                $web3 = new Ethereum('https://mainnet.infura.io/v3/a6e34ed067c74f25ba705456d73a471e');
                $networkId = '5777';
                $smartContract = new SmartContract($abi, $contractAddress, $web3);
                $smartContracts[] = $smartContract ;
                // By default ContractEventProcessor
                // process any Transaction from Block-0 to latest Block (at script run time).
               new ContractEventProcessor($web3, $smartContracts,30122);
            }
            catch (\Exception $exception) {
                echo "abi should be"; $abi ;
                throw new $exception;
            }



        }








        }

    public function getEvents(){

        try {
            echo "<h3>What's up on $url</h3>";
            $eth = new Ethereum($url);


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