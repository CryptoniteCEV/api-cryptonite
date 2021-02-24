<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class score extends Model
{
    use HasFactory;

    protected $fillable = ['experience', 'user_id'];
    protected $hidden = ['updated_at','created_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
