<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'name','username','profile_pic','password','date_of_birth'];
    protected $hidden = ['password','updated_at','created_at'];
    
    public function score()
    {
        return $this->hasOne(score::class);
    }

    public function currency(){
        return $this->belongsToMany(currency::class, "trades")->withPivot('quantity', 'is_sell', 'price', 'date')->orderBy('price','asc');
    }
    
    public function wallet(){
        return $this->belongsToMany(currency::class, "wallets")->withPivot('quantity');
    }
    
    public function mission(){
    	return $this->belongsToMany(mission::class, "user_missions")->withPivot('is_finished', 'user_id', 'mission_id');
    }

}
