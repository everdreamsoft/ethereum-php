<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-05
 * Time: 17:55
 */

namespace Ethereum;

use CsCannon\Blockchains\Ethereum\DataSource\InfuraProvider;
use CsCannon\Blockchains\Klaytn\OfficialProvider;
use CsCannon\SandraManager;
use SandraCore\Setup;

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload


$offset = 0 ;

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



$sandra = SandraManager::getSandra();
$myProcessor = new BlockProcessor(new OfficialProvider(),$sandra,9478756);

//Setup::flushDatagraph($sandra);

$sandra = SandraManager::getSandra();



$trackedContractArray[] = '0x753fc3b652ed31ec02345cf46782d080843837b5'; // my first klaytn token
$trackedContractArray[] = '0xB2c48D6384feA29283b51622f179dC51ffB178E0'; // Settler bunny
$trackedContractArray[] = '0x53Dd98cA4B63178841155fCd80d4C4Ca7D5Ba331'; // Settler Salamender
$trackedContractArray[] = '0x7cDB98E90441DC2040B7a1627a1335D99B4C3859'; // Settler Horse
/*$trackedContractArray[] = '0x000983ba1a675327f0940b56c2d49cd9c042dfbf'; // GU ShinyLegendaryPack
$trackedContractArray[] = '0xe7e02be77d46ca4def893d1d05198f4be5c1ecd8'; // GU Vault
$trackedContractArray[] = '0x91b9d2835ad914bc1dcfe09bd1816febd04fd689'; // GU Capped vault
$trackedContractArray[] = '0xe7e02be77d46ca4def893d1d05198f4be5c1ecd8'; // GU Vault
$trackedContractArray[] = '0x22365168c8705e95b2d08876c23a8c13e3ad72e2'; // GU TournamentPass
$trackedContractArray[] = '0xca6746f65d53d2df5022b5d775817e62e8462690'; // GU RarePackTwo
$trackedContractArray[] = '0xe5dc9d1b58fd5a95fc20a6c6afaa76d44d70a7df'; // GU EpicPackTwo
$trackedContractArray[] = '0x6c5dc1dcda3d309a6e919e6d0965f197e0fc1913'; // GU Legendary pack two
$trackedContractArray[] = '0x80391307f1b08cc068fa1d1b77513b98c36dfbfa'; // GU ShinyLegendaryPackTwo
$trackedContractArray[] = '0x08dBf4f942ba8cd7871C13addEfdfFEf3E5a8035'; // GU RarePackThree
$trackedContractArray[] = '0x84487E50dB6317E5e834d89d0e81Fd873462Ea47'; // GU EpicPackThree
$trackedContractArray[] = '0x80b3075410Ee52C520DD203f60206F633D27A109'; // GU egendaryPackThre
$trackedContractArray[] = '0x314495517F380CEb7c498A35739E40864240ADCf'; // GU ShinylegendaryThree
$trackedContractArray[] = '0x0777f76d195795268388789343068e4fcd286919'; // GU RarePackFour
$trackedContractArray[] = '0x482cf6a9d6b23452c81d4d0f0f139c1414963f89'; // GU EpicPackFour
$trackedContractArray[] = '0xc47d7d42e44b2e04c83a45cf45898e597a0c2311'; // GU LegendaryPackFour
$trackedContractArray[] = '0x1e891c587b345ab02a31b57c1f926fb08913d10d'; // GU ShinyLegendaryPackFour
$trackedContractArray[] = '0x6Cb4AD504816bD3021aE48286f018AC725239B89'; // GU CatInThePack
$trackedContractArray[] = '0x6EbeAf8e8E946F0716E6533A6f2cefc83f60e8Ab'; // GU CardMigration */

$myProcessor->trackContract($trackedContractArray);

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