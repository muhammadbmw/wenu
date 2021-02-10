<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Validator;


class VerificationController extends Controller
{
    public function verify($user_id, Request $request) {
        if (!$request->hasValidSignature()) {
            //return response()->json(["message" => "Invalid/Expired url provided.",'success' => false], 200);
			return redirect()->away('http://192.168.203.111:3000/chef/login');
        }

        $user = User::findOrFail($user_id);
		$role = $user->role;
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
			//return response()->json(["message" => "Verification successfull", 'success' => true], 200);
        }

        //return response()->json(["message" => "You are already verified", 'success' => true], 200);
		if($role == 'chef')
			return redirect()->away('http://192.168.203.111:3000/chef/login');
		else
			return redirect()->away('http://192.168.203.111:3000/foodie/login');
   }
   
   public function resend(Request $request) {
		   $validator = Validator::make($request->all(), [
			  'email' => 'required|email',
			  'password' => 'required',

		  ]);
		  if ($validator->fails()) {
			  $response = [
				  'success' => false,
				  'data' => $validator->errors(),
				  'message' => 'Validation Error.'
			  ];
			  return response()->json($response, 200);
		  }
		  if (Auth::attempt(['email' => request('email'), 'password' => request('password')])) {
			if (auth()->user()->hasVerifiedEmail()) {
				$response = [
				'success' => true,
				'message' => 'The email address is already verified.'];
				 return response()->json($response, 200);
			}
			auth()->user()->sendEmailVerificationNotification();
			$response = [
			'success' => true,
			'message' => 'Verification link sent!'];
			 return response()->json($response, 200);
		 }
		 else {
             $response = [
			'success' => false,
			'message' => 'login credential does not match.'];
			 return response()->json($response, 200);
		}
    }
   
   
}
