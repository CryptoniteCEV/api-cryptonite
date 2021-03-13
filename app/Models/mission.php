<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class mission extends Model
{
    use HasFactory;

    protected $fillable = ['icon', 'description'];

    public function user(){
    	return $this->belongsToMany(user::class, "mission")->withPivot('is_finished');
    }
}
