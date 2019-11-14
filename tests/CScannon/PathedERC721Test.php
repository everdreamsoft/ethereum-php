<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-04
 * Time: 17:46
 */

use CsCannon ;
use CsCannon\Blockchains ;

class PathedERC721Test extends \PHPUnit\Framework\TestCase
{


    public function testCryptoCarto(){

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        \CsCannon\Tests\TestManager::initTestDatagraph();



        $sandra = \CsCannon\SandraManager::getSandra();
        $provider = new \CsCannon\Blockchains\Klaytn\KlaytnCypress('');
        $provider->csCaverPath =   dirname(dirname(__FILE__)) .'/../../crystalControlCenter/public/caver/';

        $contractFactory = new \CsCannon\Blockchains\Klaytn\KlaytnContractFactory();

        $contract = $contractFactory->get('0xf4bc8ccb22e3c1466df53bbd2dc94c03ec4c300f', true,\CsCannon\Blockchains\Ethereum\Interfaces\ERC721::init());

        $assetCollectionFactory = new \CsCannon\AssetCollectionFactory(\CsCannon\SandraManager::getSandra());
        $assetCollectionFactory->populateLocal();
        $collectionEntity = $assetCollectionFactory->getOrCreate("CryptoCarto");
        $collectionEntity->setName("Crypto Carto");
        $collectionEntity->setDescription("Fantastic");
        $collectionEntity->setImageUrl("https://klaytn.champ.blockdevs.asia/token_img/6277101735386680763835789423207666416102355444464034512904");
        $assetCollectionFactory->populateLocal();
        $collectionEntity->setSolver(\CsCannon\AssetSolvers\PathPredictableSolver::getEntity('https://cryptocarto.herokuapp.com/token/{{tokenId}}'));

        $contract->bindToCollection($collectionEntity);



        $myProcessor = new \Ethereum\BlockProcessor($provider,$sandra,
            12199610);

        $myProcessor->trackContract($contractFactory);
        $myProcessor->process(2);


       $eventFactory = new \CsCannon\Blockchains\Klaytn\KlaytnEventFactory();

       $events = $eventFactory->populateLocal();
       $getDisplayable = $eventFactory->display()->return();
       $firstTransaction = reset($getDisplayable);
       $orb = $firstTransaction['orbs'][0];


       $this->assertEquals('0xe99f9adcc738f0b0fde2a12c85be9c8e0f32c252d8554e47bdadc7490e6e091a',$firstTransaction[\CsCannon\Blockchains\BlockchainEvent::DISPLAY_TXID]);
       $this->assertEquals('0x0000000000000000000000000000000000000000',$firstTransaction[\CsCannon\Blockchains\BlockchainEvent::DISPLAY_SOURCE_ADDRESS]);
       $this->assertEquals('0xe99f9adcc738f0b0fde2a12c85be9c8e0f32c252d8554e47bdadc7490e6e091a',$firstTransaction[CsCannon\Blockchains\BlockchainEvent::DISPLAY_DESTINATION_ADDRESS]);
       $this->assertEquals('1',$firstTransaction[Blockchains\BlockchainEvent::DISPLAY_QUANTITY]);
       $this->assertEquals('https://cryptocarto.herokuapp.com/token/48876200023253',$orb['imageUrl']);

        $myProcessor->process(2);

        $eventFactory = new \CsCannon\Blockchains\Klaytn\KlaytnEventFactory();

        $events = $eventFactory->populateLocal();

        //we should have only 2 events make sure the tx has not been saved twice
        $this->assertCount(2,$events);






    }





}