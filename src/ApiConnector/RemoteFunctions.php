<?php
/**
 * Created by EverdreamSoft.
 * User: Shaban Shaame
 * Date: 2019-07-16
 * Time: 16:20
 */

namespace Ethereum\ApiConnector;


use GuzzleHttp\Exception\ClientException;
use http\Client;

class RemoteFunctions
{



    public static function getAbi($contractAddress){

        $client = new \GuzzleHttp\Client();
        try {
            $res = $client->request('GET', 'https://api.etherscan.io/api?module=contract&action=getabi&address=' . $contractAddress . '');
            echo 'https://api.etherscan.io/api?module=contract&action=getabi
                &address=' . $contractAddress . '';



            $result = json_decode($res->getBody());
            $abiRaw = $result->result ;
            $abiRefined = $abiRaw;
            $abi = $abiRefined;
            $saveAbi = $abiRefined;

            // $abi = stripslashes($abi);
        } catch (ClientException  $e) {
            $abi = null;

        }

        return $abi ;




    }

}