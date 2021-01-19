<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class currency extends Model
{
    use HasFactory;

    public user() {
    	return $this->belongsToMany(User::class, "trades")->withPivot('quantity','price')->orderBy('created_at', 'desc');
    }
}
