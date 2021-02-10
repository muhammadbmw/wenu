<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MenuGroupRel extends Model
{
    use HasFactory;
	protected $table = 'menu_group_rel';
	protected $primaryKey = 'id';
	public $timestamps = false;
}
