<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Validator;

class UserController extends Controller
{
  public function register(Request $request) 
  {
    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'email' => 'required|email',
        'password' => 'required|string',
        'role' => 'required|string'
      
    ]);
    if ($validator->fails()) {
        $response = [
            'success' => false,
            'data' => $validator->errors(),
            'message' => 'Validation Error.'
        ];
        return response()->json($response, 200);
    }
    $input = $request->all();
    $input['password'] = bcrypt($input['password']);
    $user = User::create($input);
    $success['token'] = $user->createToken('wenu')->accessToken;
    $success['name'] = $user->name;
    $success['role'] = $user->role;
    $response = [
        'success' => true,
        'data' => $success,
        'message' => 'User register successfully.'
    ];
    return response()->json($response, 200);

  }
 public function chef_register(Request $request) 
  {
    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'email' => 'required|email:rfc,dns|unique:users',
        'password' => 'required|string',
		'city' => 'required',
		'address' => 'required',
		'province' => 'required',
		'mobile' => 'required',
		'postal_code' => 'required|string|min:6|max:7',
    ]);
    if ($validator->fails()) {
        $response = [
            'success' => false,
            'data' => $validator->errors(),
            'message' => 'Validation Error.'
        ];
        return response()->json($response, 200);
    }
	$user = new User;
	$user->name = $request->name;
	$user->email = $request->email;
	$user->password = bcrypt($request->password);
	$user->status = 'pending';
    $user->save();
	$user_id = $user->id;
	$profile = new Profile;
	$profile->city = $request->city;
	$profile->address = $request->address;
	$profile->province = $request->province;
	$profile->postal_code = $request->postal_code;
	$profile->mobile = $request->mobile;
	$profile->user_id = $user_id;
	$profile->save();
    
    $response = [
        'success' => true,
        'message' => 'Registration successful.'
    ];
    return response()->json($response, 200);

  }
  public function login(Request $request)
  {
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
        $user = Auth::user();
        if($user->status != 'inactive'){
            $success['token'] = $user->createToken('wenu')->accessToken;
            $success['user_id'] = $user->id;
            $success['name'] = $user->name;
            $success['role'] = $user->role;
			 $success['status'] = $user->status;
            
           $response = [
            'success' => true,
            'data' => $success,
            'message' => 'User login successfully.'];
            return response()->json($response, 200);
        }
        else {
             $response = [
            'success' => false,
            'message' => 'User is not active.'];
            return response()->json($response, 200);
        }
    } else {
             $response = [
        'success' => false,
        'message' => 'login credential does not match.'];
         return response()->json($response, 200);
    }

    }
	
	//logout
	public function logout(Request $request)
    {
       $request->user()->token()->revoke();
		$response = [
            'success' => true,
            'message' => 'Successfully logged out'
        ];
        return response()->json($response, 200);
		
    }
	
	
	//get chef profile
	public function chef_profile()
	{
		 $user = Auth::user();
		 $profile = $user->profile;
		 
		$data = [ 'city' => $profile->city, 'address' => $profile->address,
				 'province' => $profile->province, 'postal_code' => $profile->postal_code,
				 'mobile' => $profile->mobile
				];
		 
		 $response = [
            'success' => true,
            'data' => $data
			
        ];
        return response()->json($response, 200);
	}
	
	//update chef profile
	public function update_chef_profile(Request $request)
	{
		 $user = Auth::user();
		 $profile = $user->profile;
		
		if ($request->filled('name')) {
			$user->name = $request->name;
		}
		if ($request->filled('password')) {
			$user->password = bcrypt($request->password);
		}
		$user->save();
		if ($request->filled('city')) {
			$profile->city = $request->city;
		}
		if ($request->filled('address')) {
			$profile->address = $request->address;
		}
		if ($request->filled('province')) {
			$profile->province = $request->province;
		}
		if ($request->filled('postal_code')) {
			$profile->postal_code = $request->postal_code;
		}
		if ($request->filled('mobile')) {
			$profile->mobile = $request->mobile;
		}
		$profile->save();
		
		$data = [ 'name' => $user->name,'city' => $profile->city, 'address' => $profile->address,
				 'province' => $profile->province, 'postal_code' => $profile->postal_code,
				 'mobile' => $profile->mobile
				];
		 
		 $response = [
            'success' => true,
            'data' => $data,
			'message' => 'Profile updated successfully.'
        ];
        return response()->json($response, 200);
	}

}
