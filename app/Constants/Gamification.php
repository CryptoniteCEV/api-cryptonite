<?php
namespace App\Constants;
class Gamification{

    private const MISSIONS = 
    array(
        array('icon' => '0',//23
            'description' => 'Buy DogeCoin'), 
            array('icon' => '1',
            'description' => 'Log In'),
            array('icon' => '2',//26
            'description' => 'Check Bitcoin'),
            array('icon' => '3',//49
            'description' => 'Buy any Cryptocurrency'),
            array('icon' => '4',//45
            'description' => 'Follow Someone'),
            array('icon' => '5',//21
            'description' => 'Sell Litecoin'),
            array('icon' => '6',//8
            'description' => 'Sell all your Coins'),
            array('icon' => '7',
            'description' => 'Move all your money to BTC'),
            array('icon' => '8',
            'description' => 'Follow 2 people within the same login'),
            array('icon' => '9',
            'description' => 'Make 3 transaction within the same login'),
            array('icon' => '10',
            'description' => 'Reach Level 3'),
            array('icon' => '11',
            'description' => 'Spin the Wheel!'),
            array('icon' => '12',
            'description' => 'Register in Cryptonite'),
            array('icon' => '13',
            'description' => 'Check Your Wallet')
    );

    Public function get_all(){

        return self::MISSIONS;
    }

}