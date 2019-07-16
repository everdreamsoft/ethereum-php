<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-05
 * Time: 17:55
 */

namespace Ethereum;

require_once __DIR__ . '/../vendor/autoload.php'; // Autoload files using Composer autoload


$myProcessor = new BlockProcessor();
//$myProcessor->trackContract(['0x5f5b176553e51171826d1a62e540bc30422c7717','0xa506758544a71943b5e8728d2DF8EC9E72473a9A']);

$myProcessor->process();