<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = ['price', 'user_id', 'currency_id', 'quantity', 'is_sell', 'date'];

    public function currency(){
        return $this->belongsTo(Currency::class);
    }

    public function user(){
        return $this->belongsTo(User::class);
    }
}
