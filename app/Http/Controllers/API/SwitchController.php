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
		  
		 $response = [
            'success' => true,
			'role' => 'foodie',
           'message' => 'Switch successful.'
			
        ];
        return response()->json($response, 200);
	}
	
	public function foodie_to_chef()
	{
		 $user = Auth::user();
		 $profile = $user->profile;
		 if($profile){
			$user->role = 'chef';
			$user->save();
			
			$response = [
            'success' => true,
			'role' => 'chef',
			'message' => 'Switch successful.'
			];
		 } 
		 else {
			 $response = [
            'success' => false,
			'message' => 'Please create profile to become chef.'
			];
		 }
        return response()->json($response, 200);
	}
}
