<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


use App\Models\Users;
use App\Models\Follows;

class FollowController extends Controller
{
    //
	public function follow(Request $request)
    {
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'userId.required' => 'The User Id field is required.',
				'userId.integer' => 'The User Id field should be integer',
				'followee_id.required' => 'The Followee Id field is required.',
				'followee_id.integer' => 'The Followee Id field should be integer',
			];
			
			$validationRules = array(
				'userId' => 'required | integer',
				'followee_id' => 'required | integer',
			);

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$userId = $input_data->userId;
			$followee_id = $input_data->followee_id;
			
			
			$user1 = Users::find($userId);
			if(empty($user1)){
				return response()->json(['error' => true, 'message' => "User Not Found."], 200);
			}
			$user2 = Users::find($followee_id);
			if(empty($user2)){
				return response()->json(['error' => true, 'message' => "User Not Found."], 200);
			} 
			
			$followExits = Follows::where('follower_id', $user1->id)
								  ->where('followee_id', $user2->id)
								  ->exists();
			if(!empty($followExits)){
				return response()->json(['error' => true, 'message' => "User has already followed."], 200);
			}
			
			$follows = New Follows();
			
			$follows->follower_id = $user1->id;
			$follows->followee_id = $user2->id;
			
			if($follows->save()){
				return response()->json(['error' => false, 'message' =>'User Successfully Followed'], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'Oops! Something went wrong while Following.'], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
    }
	
	public function unfollow(Request $request)
    {
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'userId.required' => 'The User Id field is required.',
				'userId.integer' => 'The User Id field should be integer',
				'followee_id.required' => 'The Followee Id field is required.',
				'followee_id.integer' => 'The Followee Id field should be integer',
			];
			
			$validationRules = array(
				'userId' => 'required | integer',
				'followee_id' => 'required | integer',
			);

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$userId = $input_data->userId;
			$followee_id = $input_data->followee_id;
			
			
			$user1 = Users::find($userId);
			if(empty($user1)){
				return response()->json(['error' => true, 'message' => "User Not Found."], 200);
			}
			$user2 = Users::find($followee_id);
			if(empty($user2)){
				return response()->json(['error' => true, 'message' => "User Not Found."], 200);
			} 
			
			$followExits = Follows::where('follower_id', $user1->id)
								  ->where('followee_id', $user2->id)
								  ->delete();
			/* if(empty($followExits)){
				return response()->json(['error' => true, 'message' => "Record Not Found."], 200);
			}
			*/
			
			if($followExits){
				return response()->json(['error' => false, 'message' =>'User Successfully Unfollowed'], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'Oops! Something went wrong while Unfollowing.'], 200);
			} 
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
    }
	
	public function followers(Request $request)
    {
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'id.required' => 'The User Id field is required.',
				'id.integer' => 'The User Id field should be integer'
			];
			
			$validationRules = array(
				'id' => 'required | integer'
			);

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$id = $input_data->id;
			$pagination = config('constants.DEFAULT_PAGINATION');
			
			if(isset($input_data->pagination)){
				$pagination = $input_data->pagination;
			}
			 
			$user = Users::findOrFail($id);
			$followers = $user->followers()->paginate($pagination);
			$followersCount = $user->followers()->count();
			$followerData = $followers;
			return response()->json(['error' => false, 'message' => "Data Found", "Total" => $followersCount, "Data" => $followerData ], 200);
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
    }

    public function followees(Request $request)
    {
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'id.required' => 'The User Id field is required.',
				'id.integer' => 'The User Id field should be integer'
			];
			
			$validationRules = array(
				'id' => 'required | integer'
			);

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$id = $input_data->id;
			$pagination = config('constants.DEFAULT_PAGINATION');
			
			if(isset($input_data->pagination)){
				$pagination = $input_data->pagination;
			}
			 
			$user = Users::findOrFail($id);
			
			$followees = $user->follows()->paginate($pagination);
			$followeesCount = $user->follows()->count();
			$followeeData = $followees;
			return response()->json(['error' => false, 'message' => "Data Found ", "Total" => $followeesCount, "Data" => $followeeData ], 200);
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200); 
		}
		
       

       
    }
	
	
}
