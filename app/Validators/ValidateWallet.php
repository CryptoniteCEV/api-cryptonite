<?php
namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class ValidateWallet{

    public static function validate_create(){
        return Validator::make(request()->all(), [
            'quantity' => 'integer|required'
        ]);
    }
}