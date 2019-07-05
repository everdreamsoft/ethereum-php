<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-05
 * Time: 09:56
 */

namespace Ethereum;


use Ethereum\Sandra\EthereumContractFactory;
use SandraCore\EntityFactory;
use SandraCore\System;

class BlockProcessor
{



    public function trackContract($contract,$abi=null){

        $sandra = new System('',true);



        $contractFactory = new EthereumContractFactory($sandra);

        $contractFactory->populateLocal();







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



}