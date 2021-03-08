<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'name','username','surname','profile_pic','password','date_of_birth'];
    protected $hidden = ['password','updated_at','created_at'];
    
    public function score()
    {
        return $this->hasOne(Score::class);
    }

    public function currency(){
        return $this->belongsToMany(Currency::class, "trades")->withPivot('quantity', 'is_sell', 'price', 'date')->orderBy('price','asc');
    }
    
    public function wallet(){
        return $this->belongsToMany(Currency::class, "wallets")->withPivot('quantity');
    }
    
}
