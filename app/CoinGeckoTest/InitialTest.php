<?php

namespace App\CoinGeckoTest;

use Codenixsv\CoinGeckoApi\CoinGeckoClient;

class InitialTest{

    public static function getCoin(){

        $client = new CoinGeckoClient();
        $data = $client->simple()->getPrice('dogecoin', 'usd,eur');

        return $data;
    }
}




