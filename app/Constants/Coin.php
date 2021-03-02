<?php
namespace App\Constants;
class Coin{

    private const CRYPTOS = 
    array(
        array('name' => 'Tether',
            'symbol' => 'USDT'), 
            array('name' => 'Bitcoin',
            'symbol' => 'BTC'),
            array('name' => 'Ethereum',
            'symbol' => 'ETH'),
            array('name' => 'DogeCoin',
            'symbol' => 'DOGE')
    );

    Public function get_all(){

        return self::CRYPTOS;
    }

}