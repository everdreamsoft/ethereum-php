<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 24.03.2019
 * Time: 14:42
 */

namespace Ethereum\Sandra;






use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use SandraCore\Entity;
use SandraCore\EntityFactory;
use SandraCore\System;
use Graze\GuzzleHttp;

class EthereumContractFactory extends \CsCannon\Blockchains\Ethereum\EthereumContractFactory
{

    public static $isa = 'ethContract';
    public static $file = 'blockchainContractFile';
    const TRACKED_VERB = 'trackedStatus';
    const TRACKED_TRUE = 'tracked';
    const TRACKED_FALSE = 'notTracked';


    const ABI_VERB = 'has';
    const ABI_TARGET = 'abi';

    protected static $className = 'Ethereum\Sandra\EthereumContract' ;





public function populateLocal($limit = 10000, $offset = 0, $asc = 'ASC')
{


    $returnStatement =  parent::populateLocal($limit, $offset, $asc);

    //we populate by default the tracked status
    $this->populateBrotherEntities(self::TRACKED_VERB);
    $this->populateBrotherEntities(self::ABI_VERB,self::ABI_TARGET);

    return $returnStatement ;

}



    public function create($address,$abi = null,$tracked = null)
    {

        $dataArray[self::IDENTIFIER] = $address;

        $entity = parent::createNew($dataArray, null);

        if (!$abi) return $entity ;

        $abiEntity = $entity->setBrotherEntity(self::ABI_VERB,self::ABI_TARGET,null);
        $abiEntity->setStorage($abi);

        return $entity ;
    }

}