<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-05
 * Time: 17:55
 */

namespace Ethereum;

use CsCannon\Blockchains\Ethereum\DataSource\InfuraProvider;
use CsCannon\Blockchains\Ethereum\EthereumAddress;
use CsCannon\Blockchains\Ethereum\EthereumAddressFactory;
use CsCannon\Blockchains\Klaytn\OfficialProvider;
use CsCannon\SandraManager;
use Ethereum\CrystalSpark\CsSmartContract;
use Ethereum\DataType\EthD20;
use SandraCore\Setup;
use SandraCore\System;

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

$sandra = new System('infura',true);
$sandra = SandraManager::setSandra($sandra);
$provider = new InfuraProvider('a6e34ed067c74f25ba705456d73a471e');
$myProcessor = new BlockProcessor($provider,$sandra,8159452);

$web3 = new Ethereum($provider->getHostUrl());

$strJsonFileContents = "[
	{
        \"constant\": false,
		\"inputs\": [
			{
				\"name\": \"spender\",
				\"type\": \"address\"
			},
			{
				\"name\": \"amount\",
				\"type\": \"uint256\"
			}
		],
		\"name\": \"approve\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"bool\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"nonpayable\",
		\"type\": \"function\"
	},
	{
		\"constant\": true,
		\"inputs\": [],
		\"name\": \"totalSupply\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"uint256\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"view\",
		\"type\": \"function\"
	},
	{
		\"constant\": false,
		\"inputs\": [
			{
				\"name\": \"sender\",
				\"type\": \"address\"
			},
			{
				\"name\": \"recipient\",
				\"type\": \"address\"
			},
			{
				\"name\": \"amount\",
				\"type\": \"uint256\"
			}
		],
		\"name\": \"transferFrom\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"bool\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"nonpayable\",
		\"type\": \"function\"
	},
	{
		\"constant\": true,
		\"inputs\": [
			{
				\"name\": \"account\",
				\"type\": \"address\"
			}
		],
		\"name\": \"balanceOf\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"uint256\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"view\",
		\"type\": \"function\"
	},
	{
		\"constant\": false,
		\"inputs\": [
			{
				\"name\": \"recipient\",
				\"type\": \"address\"
			},
			{
				\"name\": \"amount\",
				\"type\": \"uint256\"
			}
		],
		\"name\": \"transfer\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"bool\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"nonpayable\",
		\"type\": \"function\"
	},
	{
		\"constant\": true,
		\"inputs\": [
			{
				\"name\": \"owner\",
				\"type\": \"address\"
			},
			{
				\"name\": \"spender\",
				\"type\": \"address\"
			}
		],
		\"name\": \"allowance\",
		\"outputs\": [
			{
				\"name\": \"\",
				\"type\": \"uint256\"
			}
		],
		\"payable\": false,
		\"stateMutability\": \"view\",
		\"type\": \"function\"
	},
	{
		\"anonymous\": false,
		\"inputs\": [
			{
				\"indexed\": true,
				\"name\": \"from\",
				\"type\": \"address\"
			},
			{
				\"indexed\": true,
				\"name\": \"to\",
				\"type\": \"address\"
			},
			{
				\"indexed\": false,
				\"name\": \"value\",
				\"type\": \"uint256\"
			}
		],
		\"name\": \"Transfer\",
		\"type\": \"event\"
	},
	{
		\"anonymous\": false,
		\"inputs\": [
			{
				\"indexed\": true,
				\"name\": \"owner\",
				\"type\": \"address\"
			},
			{
				\"indexed\": true,
				\"name\": \"spender\",
				\"type\": \"address\"
			},
			{
				\"indexed\": false,
				\"name\": \"value\",
				\"type\": \"uint256\"
			}
		],
		\"name\": \"Approval\",
		\"type\": \"event\"
	}
]";



$result = json_decode($strJsonFileContents);


$myDaiContractAddress = '0x89d24a6b4ccb1b6faa2625fe562bdd9a23260359';

$smartContract = new CsSmartContract($result, $myDaiContractAddress, $web3);
$ethereumAF = new EthereumAddressFactory();
$ethereumAddress = $ethereumAF->get('0x1a84d1c0258bdc26f013218acb2530a76c884a38');


$balance = $smartContract->getBalance($ethereumAddress);
print_r($balance);






//Setup::flushDatagraph($sandra);

$sandra = SandraManager::getSandra();




$trackedContractArray[] = '0x0777f76d195795268388789343068e4fcd286919'; // GU pack of four
/*$trackedContractArray[] = '0x6ebeaf8e8e946f0716e6533a6f2cefc83f60e8ab'; // GU token contract
$trackedContractArray[] = '0x6e0051c750b81f583f42f93a48d56497779992d8'; // GU EpicPack
$trackedContractArray[] = '0x5789e2b5460cae9329d93a78511e2ac49f98a1f6'; // GU LegendaryPack
$trackedContractArray[] = '0x000983ba1a675327f0940b56c2d49cd9c042dfbf'; // GU ShinyLegendaryPack
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