<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-10-23
 * Time: 09:15
 */

namespace Ethereum\CrystalSpark;


use CsCannon\AssetCollectionFactory;
use CsCannon\AssetFactory;
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

    public function ownerOf($tokenId, BlockchainBlock $atBlock){



        if (method_exists($this->rpcProvider,'ownerOf')){

            $standard = $this->csEntity->getStandard();
            $standard->setTokenId($tokenId);


            $ownerAddress = $this->rpcProvider->ownerOf($this->csEntity,$tokenId,$standard);
            $addressFactory = $this->csEntity->getBlockchain()->getAddressFactory();
            $address = $addressFactory->get($ownerAddress);




            $balance = new Balance($address);
            $balance->addContractToken($this->csEntity,$standard,1);
            $balance->saveToDatagraph($atBlock);

            //For the moment we store all assets locally
            $assetFactory = new AssetFactory($this->csEntity->system);
            $asset = $assetFactory->get($this->getAddress().'-'.$tokenId);
            if (is_null($asset)){

                //temporary for champ
                $metaData = [\CsCannon\AssetFactory::IMAGE_URL => 'https://klaytn.champ.blockdevs.asia/token_img/'.$tokenId,
                    \CsCannon\AssetFactory::METADATA_URL => 'https://klaytn.champ.blockdevs.asia/token/'.$tokenId,

                ];

                $collectionFactory = new AssetCollectionFactory($this->csEntity->system);
                $collection = $collectionFactory->get('KlaytnChamp');

                $additionNalMeta = $standard->getSpecifierData();


                $assetFactory->create($this->getAddress().'-'.$tokenId,$metaData,$collection,array($this->csEntity->subjectConcept->idConcept=>$additionNalMeta));

            }


        }



        return $balance ;

        // return $this->balanceOf($addressEth)->val();


    }

}