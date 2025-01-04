<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\UsersDocuments;

class AdminUserController extends Controller
{
    public function userDocumentsStatus(Request $request){
		
		$messages = [ 
            'userId.required' => 'The User Id field is required.',
			'userId.integer' => 'The User Id field should be integer.'
        ];
		
		$validationRules = array(
			'userId' => 'required | integer',
	    );

		$validator = Validator::make($request->all(), $validationRules, $messages);

		if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }
		
		$userId = trim($request['userId']);
		
		$usersDocuments = UsersDocuments::find($userId);
		
		if(empty($usersDocuments)){
			return response()->json(['error' => true, 'message' => 'Oops! User Not Found. Please Check Again'], 200);
		}
		
		if(isset($request['isPanApproved'])){
			$isPanApproved = trim($request['isPanApproved']);
			$usersDocuments->isPanVerified = $isPanApproved;
		}
		if(isset($request['isAadharFrontApproved'])){
			$isAadharFrontApproved = trim($request['isAadharFrontApproved']);
			$usersDocuments->isAadharFrontVerified = $isAadharFrontApproved;
		}
		if(isset($request['isAadharBackApproved'])){
			$isAadharBackApproved = trim($request['isAadharBackApproved']);
			$usersDocuments->isAadharBackVerified = $isAadharBackApproved;
		}
		
		if($usersDocuments->save()){
			return response()->json(['error' => false, 'message' => 'User Document Status Successfully Updated'], 200);
		}else{
			return response()->json(['error' => true, 'message' => 'Oops! Something went wrong.'], 200);
		}
	}
}
