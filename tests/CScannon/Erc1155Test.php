<?php


use CsCannon\Blockchains\Ethereum\DataSource\InfuraProvider;

class Erc1155Test extends \PHPUnit_Framework_TestCase
{

    public function testRead(){
        \CsCannon\Tests\TestManager::initTestDatagraph();

        $sandra = CsCannon\SandraManager::getSandra();

        $provider = new InfuraProvider('a6e34ed067c74f25ba705456d73a471e');

        $contractF = new \Ethereum\Sandra\EthereumContractFactory();
        $contract = $contractF->get('0xd07dc4262bcdbf85190c01c996b4c06a461d2430',true,\CsCannon\Blockchains\Ethereum\Interfaces\ERC1155::init());

        $myProcessor = new \Ethereum\BlockProcessor($provider,$sandra,

            12069667);

        $myProcessor->trackContract($contractF);

        $abi = json_decode($contract->getAbi());

        try {
            $web3 = new \Ethereum\Ethereum($provider->getHostUrl());
            $smartContract = new \Ethereum\CrystalSpark\CsSmartContract($abi, $contract->getId(), $web3, $provider, $contract);
        } catch (\Exception $exception) {
            throw new $exception;
        }
        $myProcessor->process(1);


    }

}
