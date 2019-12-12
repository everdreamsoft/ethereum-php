<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-04
 * Time: 17:46
 */

use CsCannon ;
use CsCannon\Blockchains ;

class CScannonTest extends \PHPUnit\Framework\TestCase
{


    public function testErc721(){

        define('PROJECT_ROOT', dirname(dirname(__FILE__)));

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        \CsCannon\Tests\TestManager::initTestDatagraph();


        $sandra = \CsCannon\SandraManager::getSandra();
        $provider = new \CsCannon\Blockchains\Klaytn\KlaytnCypress('');
        $provider->csCaverPath =  PROJECT_ROOT .'/../../crystalControlCenter/public/caver/';

        $contractFactory = new \CsCannon\Blockchains\Klaytn\KlaytnContractFactory();

        $klayChamp = $contractFactory->get('0x1f49e1d2a4691e4514ae91bc3040767cf344ad82', true,\CsCannon\Blockchains\Ethereum\Interfaces\ERC721::init());

        $assetCollectionFactory = new \CsCannon\AssetCollectionFactory(\CsCannon\SandraManager::getSandra());
        $assetCollectionFactory->populateLocal();
        $collectionEntity = $assetCollectionFactory->getOrCreate("KlaytnChamp");
        $collectionEntity->setName("Klaytn Champ");
        $collectionEntity->setDescription("");
        $collectionEntity->setImageUrl("https://klaytn.champ.blockdevs.asia/token_img/6277101735386680763835789423207666416102355444464034512904");
        $assetCollectionFactory->populateLocal();
        $collectionEntity->setSolver(\CsCannon\AssetSolvers\LocalSolver::getEntity());

        $klayChamp->bindToCollection($collectionEntity);



        $myProcessor = new \Ethereum\BlockProcessor($provider,$sandra,
            11711485);

        $myProcessor->trackContract($contractFactory);
        $myProcessor->process(16);


       $eventFactory = new \CsCannon\Blockchains\Klaytn\KlaytnEventFactory();

       $events = $eventFactory->populateLocal();
       $getDisplayable = $eventFactory->display()->return();
       $firstTransaction = reset($getDisplayable);
       $orb = $firstTransaction['orbs'][0];


       $this->assertEquals('0xc5ca475f10be82715eb8f32e3897b47d62a7f30f78808301582afbb01da44e58',$firstTransaction[\CsCannon\Blockchains\BlockchainEvent::DISPLAY_TXID]);
       $this->assertEquals('0x0000000000000000000000000000000000000000',$firstTransaction[\CsCannon\Blockchains\BlockchainEvent::DISPLAY_SOURCE_ADDRESS]);
       $this->assertEquals('0x6f95266bf49544267e127a0430949f3049036f01',$firstTransaction[CsCannon\Blockchains\BlockchainEvent::DISPLAY_DESTINATION_ADDRESS]);
       $this->assertEquals('1',$firstTransaction[Blockchains\BlockchainEvent::DISPLAY_QUANTITY]);
       $this->assertEquals('https://klaytn.champ.blockdevs.asia/token_img/33',$orb['imageUrl']);

        $myProcessor->process(2);

        $eventFactory = new \CsCannon\Blockchains\Klaytn\KlaytnEventFactory();

        $events = $eventFactory->populateLocal();

        //we should have only 2 events make sure the tx has not been saved twice
        $this->assertCount(2,$events);






    }

    public function testBalanceUpdate(){

        define('PROJECT_ROOT', dirname(dirname(__FILE__)));

        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);

        $sandra = new \SandraCore\System('newState',1);


        CsCannon\SandraManager::setSandra($sandra);
        $provider = new \CsCannon\Blockchains\Klaytn\KlaytnCypress('');
        $provider->csCaverPath =  PROJECT_ROOT .'/../../crystalControlCenter/public/caver/';

        $contractFactory = new \CsCannon\Blockchains\Klaytn\KlaytnContractFactory();

        $contract = $contractFactory->get('0xf4bc8ccb22e3c1466df53bbd2dc94c03ec4c300f', true,\CsCannon\Blockchains\Ethereum\Interfaces\ERC721::init());

        $myProcessor = new \Ethereum\BlockProcessor($provider,$sandra,
            14703154);



        //$myProcessor = new \Ethereum\BlockProcessor($provider,$sandra,
          //  14511747);



        $addressFactory = new Blockchains\Klaytn\KlaytnAddressFactory();
        $addressOfPreviousOwner = $addressFactory->get('0x90303ec76edcb9b36d8b47a9122fe740e6c822e6',true);
        //$addressOfNewOwner = $addressFactory->get('0xf5f66fc02a2f8a32613b8e4b8d1eb0e2502d0fb4');

       $tokenBalance =  $addressOfPreviousOwner->getBalance()->getTokenBalance();
        //$addressOfNewOwner->getBalance()->getTokenBalance();


        $myProcessor->trackContract($contractFactory);
        $myProcessor->process(2);








    }






}