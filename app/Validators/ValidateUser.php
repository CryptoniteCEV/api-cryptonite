<?php
/*
namespace App\Validators;
use Illuminate\Support\Facades\Validator;

class ValidateUser{

    public function validateUser(){
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

    public function validateUsername(){
        return Validator::make(request()->all(), [
            'username' => 'required|string|max:25',
        ]);
    }

    public function validateEmail(){
        return Validator::make(request()->all(), [
            'email' => 'required|string|email|max:255',
        ]);
    }
}