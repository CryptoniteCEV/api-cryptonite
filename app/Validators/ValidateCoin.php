<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class ValidateCoin{

    public static function validate_create(){
        return Validator::make(request()->all(), [
            'name' => 'required|string|max:30',
            'symbol' => 'required|string|max:5'
        ]);
    }
}