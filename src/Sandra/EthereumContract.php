<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 24.03.2019
 * Time: 14:42
 */

namespace Ethereum\Sandra;



use App\AssetCollection;
use App\AssetCollectionFactory;
use App\Blockchains\Bitcoin\BitcoinAddress;
use App\Blockchains\BlockchainAddress;
use App\Blockchains\BlockchainAddressFactory;
use App\Blockchains\BlockchainContract;
use SandraCore\Entity;
use SandraCore\ForeignEntityAdapter;
use SandraCore\System;

class EthereumContract extends Entity
{

    protected static $isa = 'ethContract';
    protected static $file = 'blockchainContractFile';
    protected static  $className = 'App\Blockchains\Ethereum\EthereumContract' ;


    public function __construct($sandraConcept, $sandraReferencesArray, $factory, $entityId, $conceptVerb, $conceptTarget, System $system)
    {


        parent::__construct($sandraConcept, $sandraReferencesArray, $factory, $entityId, $conceptVerb, $conceptTarget, $system);
    }


}