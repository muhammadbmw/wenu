<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
    use HasFactory;
	
	 protected $fillable = [
        'name','description','ingredients','image','price','user_id'
    ];
	
	public function getCreatedAtAttribute($value)
    {
        return date("Y-m-d g:i A",strtotime($value));
    }
	
	public function getUpdatedAtAttribute($value)
    {
        return date("Y-m-d g:i A",strtotime($value));
    }
	
	public function getIngredientsAttribute($value)
    {
        if($value == '')
			return '';
		else
			return ($value);
    }
	public function getInstructionsAttribute($value)
    {
        if($value == '')
			return '';
		else
			return ($value);
    }
	public function getImageAttribute($value)
    {
        if($value == '')
			return '';
		else
			return ($value);
    }
}
