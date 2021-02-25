<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class trade extends Model
{
    use HasFactory;

    protected $fillable = ['price', 'user_id', 'currency_id', 'quantity', 'is_sell'];
}
