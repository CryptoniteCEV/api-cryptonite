<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class currency extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'symbol'];

    public function user(){
    	return $this->belongsToMany(user::class, "trades")->withPivot('quantity','price');
    }

    public function wallet(){
        return $this->hasOne(wallet::class);
    }
}
