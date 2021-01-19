<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Validator;

class ForgotPasswordController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        $validator = Validator::make($request->all(), [
        'email' => 'required|email',
		]);
		if ($validator->fails()) {
			$response = [
				'success' => false,
				'data' => $validator->errors(),
				'message' => 'Validation Error.'
			];
			return response()->json($response, 200);
		}
		
		$status = Password::sendResetLink(
            $request->only('email')
        );
		$msg = '';
		if($status == 'passwords.throttled')
			$msg = 'Please wait before retrying.';
		elseif($status == 'passwords.user')
			$msg =  "We can't find a user with that email address.";
			
		return $status == Password::RESET_LINK_SENT
			? response()->json(['message' => 'We have emailed your password reset link!', 'success' => true], 200)
			: response()->json(['message' => $msg, 'success' => false], 200);
	}		
}
