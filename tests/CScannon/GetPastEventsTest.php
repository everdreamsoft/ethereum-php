<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-04
 * Time: 17:46
 */

use CsCannon ;
use CsCannon\Blockchains ;
use CsCannon\Blockchains\Ethereum\DataSource\InfuraProvider;
use Ethereum\CrystalSpark\CsSmartContract;
use Ethereum\Ethereum;

class GetPastEventsTest extends \PHPUnit\Framework\TestCase
{


    public function testPastEvents(){

        define('PROJECT_ROOT', dirname(dirname(__FILE__)));

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        \CsCannon\Tests\TestManager::initTestDatagraph();

        $sandra = CsCannon\SandraManager::getSandra();

        $provider = new InfuraProvider('a6e34ed067c74f25ba705456d73a471e');

        $contractF = new \Ethereum\Sandra\EthereumContractFactory();
        $contract = $contractF->get('0x6EbeAf8e8E946F0716E6533A6f2cefc83f60e8Ab',true,Blockchains\Ethereum\Interfaces\ERC721::init());

        $myProcessor = new \Ethereum\BlockProcessor($provider,$sandra,
            12199610);

        $myProcessor->trackContract($contractF);

        $abi = json_decode($contract->getAbi());


        try {

            $web3 = new Ethereum($provider->getHostUrl());
            $smartContract = new CsSmartContract($abi, $contract->getId(), $web3, $provider, $contract);


        } catch (\Exception $exception) {

            throw new $exception;


        }

        $smartContract->getPastEvents('Transfer',12199610);

        /*const CryptoCartoContract = deployedAbi
            && smartContractAddress
            && new caver.klay.Contract(JSON.parse(deployedAbi), smartContractAddress);

        CryptoCartoContract.getPastEvents('Transfer', {fromBlock: 0, toBlock: 'latest'}, function(error, events){
            events.forEach(event => {
                // example : event.returnValues.tokenId;
            });
});*/


    }





}