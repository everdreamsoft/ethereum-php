<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-10-23
 * Time: 09:15
 */

namespace Ethereum\CrystalSpark;


use CsCannon\Balance;
use CsCannon\Blockchains\BlockchainAddress;
use CsCannon\Blockchains\BlockchainBlock;
use CsCannon\Blockchains\BlockchainContract;
use CsCannon\Blockchains\Ethereum\Interfaces\ERC20;
use CsCannon\Blockchains\RpcProvider;
use Ethereum\DataType\EthBlockParam;
use Ethereum\DataType\EthD;
use Ethereum\DataType\EthD20;
use Ethereum\DataType\EthD32;
use Ethereum\Ethereum;
use Ethereum\SmartContract;

class CsSmartContract extends SmartContract
{
    public $rpcProvider ;
    public $csEntity ;


    public function __construct(array $abi, string $contractAddress, Ethereum $eth, RpcProvider $provider, BlockchainContract $contract)

    {
        $this->rpcProvider = $provider ;
        $this->csEntity = $contract ;

        parent::__construct($abi, $contractAddress, $eth);
    }

    public function getBalance(BlockchainAddress $address, BlockchainBlock $atBlock){

        $balance = null ;

        if (method_exists($this->rpcProvider,'getBalance')){

            $quantity = $this->rpcProvider->getBalance($this->csEntity,$address,ERC20::init());

            if( !is_numeric($quantity)){
                return null ;

            }

            $balance = new Balance($address);
            $balance->addContractToken($this->csEntity,ERC20::init(),$quantity);
            $balance->saveToDatagraph($atBlock);

        }



        return $balance ;


        // return $this->balanceOf($addressEth)->val();


    }

}