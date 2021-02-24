<?php

namespace App\Validators;

use Illuminate\Support\Facades\Validator;

class ValidateCoin{

    public static function validateCreate(){
        return Validator::make(request()->all(), [
            //TODO
            'name' => 'required|string|max:30',
            'acronym' => 'required|string|max:5',
            'value' => 'required|integer',
        ]);
    }
}