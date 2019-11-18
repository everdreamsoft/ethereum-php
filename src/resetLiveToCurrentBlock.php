<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 18.11.19
 * Time: 12:31
 */

namespace Ethereum;

use CsCannon\SandraManager;
use SandraCore\EntityFactory;
use SandraCore\System;

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$sandra = new System('cypress');
$sandra = SandraManager::setSandra($sandra);

$liveFactory = new EntityFactory("liveSync", 'liveData', SandraManager::getSandra());
$liveFactory->populateLocal();
$liveData = $liveFactory->last("sync", 'live2');





if ($liveData) {
    $liveData->createOrUpdateRef('lastBlock', 12612407);
}

echo"hello";


