<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user extends Model
{
    use HasFactory;

    protected $fillable = ['email', 'name','username','surname','profile_pic','password','date_of_birth'];
    protected $hidden = ['password','updated_at','created_at'];
    
    public function score()
    {
        return $this->hasOne(Score::class);
    }

    public function trade(){
        return $this->belongsToMany(currency::class, "trades")->withPivot('price', 'quantity');
    }

    public function currency(){
        return $this->belongsToMany(currency::class, "wallets")->withPivot('quantity');
    }
    
}
