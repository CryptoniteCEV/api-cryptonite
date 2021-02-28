<?php

namespace App\Helpers;

use Codenixsv\CoinGeckoApi\CoinGeckoClient;

class CoinGecko{    

    /**
     * @return the current price for a @param $coin in a specific @param $currency
     */
    public static function getPrice($coin){
        $client = new CoinGeckoClient();     
        $coin = strtolower($coin);   
        $data = $client->simple()->getPrice($coin, "usd");
        return $data[$coin]["usd"];
    }

    /**
     * @return all coins form CoinGeckoAPI
     */
    public static function getCoins(){
        $client = new CoinGeckoClient();               
        return $client->coins()->getList();
    }

    /**
     * Shows the @param $coin value at a specific @param $date
     * Also returns just the @param $coin name and the value
     */
    public static function getPriceAtDay($coin, $date){
        $client = new CoinGeckoClient();               
        $data = $client->coins()->getHistory($coin, $date);
        $response = [
            'name' => $data['id'],
            'value' => $data['market_data']['current_price']['usd']
        ];
        return $response;
    }
    
    /**
     * Shows the value of @param $coin in an interval of @param $days
     * The interval is formed as noted bellow
     *   1 - 2 days: 30 minutes
     *   3 - 30 days: 4 hours
     *   31 and before: 4 days
     */
    public static function getHistoryInDays($coin, $days){
        $client = new CoinGeckoClient();               
        $data = $client->coins()->getOHLC($coin, "usd", $days);

        for ($i=0; $i < count($data) ; $i++) { 
            $response[$i] = [
                'time' => $data[$i][0],
                'value' => $data[$i][1]
            ];            
        }
        return $response;
    }

    public static function convert_quantity($coin, $quantity, $is_sell){
        $client = new CoinGeckoClient();
        $coinPrice = self::getPrice($coin, "usd");

        if ($is_sell == 1) {
            return $coinPrice * $quantity;
        }

        return $quantity / $coinPrice; 
        
          
    }
}

/** UPDATED VENDORS CLASS
 *  Added OHLC method to vendors\codenix-sv\src\api\Coins.php
 *  Must add this method for each new machine
 *   public function getOHLC(string $id, string $vsCurrency, int $days): array
 *  {
 *       $params['vs_currency'] = $vsCurrency;
 *      $params['days'] = $days;
 *
 *       return $this->get('/coins/' . $id .'/ohlc', $params);
 *   }
 * 
*/




