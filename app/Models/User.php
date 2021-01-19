<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user extends Model
{
    use HasFactory;

    public function score()
    {
        return $this->hasOne(Score::class);
    }

    public function trade()
    {
        return $this->belongsToMany(currency::class, "trades")->withPivot('price', 'quantity');
    }

    public function wallet(){
        return $this->belongsToMany(currency::class, "wallets")->withPivot('quantity');
    }

}
