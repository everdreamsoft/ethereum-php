<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-05
 * Time: 09:56
 */

namespace Ethereum;


use Ethereum\Sandra\EthereumContractFactory;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use SandraCore\DatabaseAdapter;
use SandraCore\EntityFactory;
use SandraCore\System;

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
                   echo $res->getBody();
                   $abi = $res->getBody();

                   $abi = stripslashes($abi);
               } catch (ClientException  $e) {
                   $abi = null;

               }
           }


          $contractEntity = $contractFactory->get($contractAddress);

          if (!$contractEntity){


              $contractEntity = $contractFactory->create($contractAddress,$abi,true);

          }

          if ($abi){

              $contractEntity->setAbi($abi);
          }




       }







    }

    public function process(){

        // First we get tracked contracts



        //


        //Then we process blocks from the lowest block of those contracts

        $hosts = [
            // Start testrpc, geth or parity locally.
            'https://mainnet.infura.io/v3/a6e34ed067c74f25ba705456d73a471e'
        ];




        $contract = new SmartContract($abi, '0x6ebeaf8e8e946f0716e6533a6f2cefc83f60e8ab', $eth);



        }







}