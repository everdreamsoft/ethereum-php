<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-10-23
 * Time: 09:15
 */

namespace Ethereum\CrystalSpark;


use CsCannon\Blockchains\BlockchainAddress;
use Ethereum\DataType\EthBlockParam;
use Ethereum\DataType\EthD;
use Ethereum\DataType\EthD20;
use Ethereum\DataType\EthD32;
use Ethereum\SmartContract;

class CsSmartContract extends SmartContract
{

    public function getBalance(BlockchainAddress $address){

        $addressHex = $address->getAddress();
        $addressEth = new EthD20($addressHex);

        return $this->balanceOf($addressEth);


    }

}