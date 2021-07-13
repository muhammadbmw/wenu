<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Validator;

class SwitchController extends Controller
{
    public function chef_to_foodie()
	{
		 $user = Auth::user();
		 $user->role = 'foodie';
		 $user->save();
		 $status = $user->foodie_status ? 'active': 'pending';
		  
		 $response = [
            'success' => true,
			'role' => 'foodie',
			'status' => $status,
           'message' => 'Switch successful.'
			
        ];
        return response()->json($response, 200);
	}
	
	public function foodie_to_chef()
	{
		$user = Auth::user();
		$user->role = 'chef';
		$user->save();
		$status = $user->chef_status ? 'active': 'pending';
			
			$response = [
            'success' => true,
			'role' => 'chef',
			'status' => $status,
			'message' => 'Switch successful.'
			];
		 
		
        return response()->json($response, 200);
	}
}
