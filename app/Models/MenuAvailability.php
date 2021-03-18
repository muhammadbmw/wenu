<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuAvailability extends Model
{
    use HasFactory;
	protected $table = 'menu_availability';
	protected $primaryKey = 'id';
	protected $fillable = [
        'menu_id','day','start_time','end_time','cutoff_time'
    ];
	public $timestamps = false;
}
