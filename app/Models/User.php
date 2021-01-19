<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class user extends Model
{
    use HasFactory;

<<<<<<< HEAD
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
=======
    public function score(){
    	return $this->hasOne(Score::class);
>>>>>>> fa7f48a510aa1a9cb3b84ded756ffcba8a66768f
    }
}
