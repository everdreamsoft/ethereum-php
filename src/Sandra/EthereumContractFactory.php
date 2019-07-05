<?php
/**
 * Created by PhpStorm.
 * User: Admin
 * Date: 24.03.2019
 * Time: 14:42
 */

namespace Ethereum\Sandra;






use SandraCore\EntityFactory;
use SandraCore\System;

class EthereumContractFactory extends EntityFactory
{

    public static $isa = 'ethAddress';
    public static $file = 'blockchainAddressFile';
    protected static $className = 'Ethereum\Sandra\EthereumContract' ;



public function __construct(System $system)
{

    parent::__construct(static::$isa,static::$file,app('Sandra')->getSandra());
    $this->generatedEntityClass = static::$className ;


}


}