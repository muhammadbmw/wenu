<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuGroupAvailability extends Model
{
    use HasFactory;
	protected $table = 'menu_group_availability';
	protected $primaryKey = 'id';
	protected $fillable = [
        'menu_group_id','day','start_time','end_time'
    ];
	public $timestamps = false;
}
