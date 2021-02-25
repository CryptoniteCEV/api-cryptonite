<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class wallet extends Model
{
    use HasFactory;

    protected $fillable = ['quantity', 'user_id', 'currency_id'];

    public function currency(){
        return $this->belongsTo(Currency::class);
    }
}
