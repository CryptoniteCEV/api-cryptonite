<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class ValidateUser{

    public static function validate_create(){
        return Validator::make(request()->all(), [
            'name' => 'required|string|max:30',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'username' => 'required|string|max:25|unique:users',
            'surname' => 'required|string|max:50',
            'profile_pic' => 'url|null',
            'date_of_birth' => 'required|date'
        ]);
    }

    public static function validate_username(){
        return Validator::make(request()->all(), [
            'username' => 'required|string|max:25',
        ]);
    }

    public static function validate_email(){
        return Validator::make(request()->all(), [
            'email' => 'required|string|email|max:255',
        ]);
    }

    public static function validate_new_password(){
        return Validator::make(request()->all(), [
            'new_password' => 'required|string|min:6',
        ]);
    }

    public static function validate_update(){
        return Validator::make(request()->all(), [
            'name' => 'string|max:30',
            'surname' => 'string|max:50',
            'profile_pic' => 'url',
            'date_of_birth' => 'date'
        ]);
    }

    public static function validate_following(){
        return Validator::make(request()->all(), [
            'username' => 'string|required|max:25'
        ]);
    }

    public static function validate_exp(){
        return Validator::make(request()->all(), [
            'new_exp' => 'integer|required|max:255'
        ]);
    }
    public static function validate_trade(){
        return Validator::make(request()->all(), [
            'is_sell' => 'string|required|max:255',
            'quantity' => 'required',
            'coin' => 'string|required|max:4'
        ]);
    }

    public static function validate_wallet(){
        return Validator::make(request()->all(), [
            'user_id' => 'integer|required|max:5',
            'quantity' => 'required',
            'currency_id' => 'integer|required|max:5'
        ]);
    }
}