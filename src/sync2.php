<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-05
 * Time: 17:55
 */

namespace Ethereum;

use CsCannon\Blockchains\Ethereum\DataSource\InfuraProvider;
use CsCannon\Blockchains\Ethereum\EthereumAddressFactory;
use CsCannon\Blockchains\Klaytn\KlaytnContractFactory;
use CsCannon\Blockchains\Klaytn\OfficialProvider;
use CsCannon\SandraManager;
use Ethereum\CrystalSpark\CsSmartContract;
use SandraCore\Setup;
use SandraCore\System;

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload


$offset = 0 ;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$sandra = new System('klaytn',true,'127.0.0.1','sandra','root','');
$sandra = SandraManager::setSandra($sandra);
$provider = new OfficialProvider('');
$myProcessor = new BlockProcessor($provider,$sandra,
    10289907);

$web3 = new Ethereum($provider->getHostUrl());

$contractFactory = new KlaytnContractFactory();







$sandra = SandraManager::getSandra();
$myProcessor = new BlockProcessor(new OfficialProvider(),$sandra,9478756);
$myProcessor = new BlockProcessor(new OfficialProvider(),$sandra,10289907);

//Setup::flushDatagraph($sandra);

$sandra = SandraManager::getSandra();



$trackedContractArray[] = '0x753fc3b652ed31ec02345cf46782d080843837b5'; // my first klaytn token
$trackedContractArray[] = '0xB2c48D6384feA29283b51622f179dC51ffB178E0'; // Settler bunny
$trackedContractArray[] = '0x53Dd98cA4B63178841155fCd80d4C4Ca7D5Ba331'; // Settler Salamender
$trackedContractArray[] = '0x7cDB98E90441DC2040B7a1627a1335D99B4C3859'; // Settler Horse


$myProcessor->trackContract($contractFactory);

echo"tracked :";
print_r($trackedContractArray);

echo "processing";




$myProcessor->process();


/* Actual issue to investigate

with the first contract GU pack of four the ABI while parsing event isn't working
The issue comes form the method event->decode->convertAbi that have non indexed values

While parsing the array

 foreach ($this->inputs as $i => $param) {
            if ($param->indexed) {
                echo "$param->name \n";
                $values[$param->name] = $indexedValues[$i]->convertByAbi($param->type);

the array indexedValues is out of scope they may be ABI parsing issues. One lead is how the ABI is JSON decoded
At while using all the contract there are several Events that are OK but the event belonging to contract

0x0777f76d195795268388789343068e4fcd286919 seems to rise an issue



*/