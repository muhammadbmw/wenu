<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    use HasFactory;

    protected $fillable = [
        'address','city','province','postal_coe','mobile','user_id'
    ];
	public function user()
	{
		return $this->belongsTo(User::class);
	}
}
