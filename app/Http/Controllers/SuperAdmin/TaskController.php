<?php

namespace App\Http\Controllers\SuperAdmin;

use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\UserTaskTracking;
use App\Models\Campaigns;

class TaskController extends Controller
{
    //
	public function createTask(Request $request){
		
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key = isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'taskName.required' => 'The task Name field is required.',
				'taskType.required' => 'The Task Type Field is required',
				'taskType.integer' => 'The Task Type Field should be integer'
			];
			
			$validationRules = array(
				'taskName' => 'required',
				'taskType' => 'required | integer'
			);

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$tasksExits = Tasks::where('taskName', $request['taskName'])->exists();
								
			if(!empty($tasksExits)){
				return response()->json(['error' => true, 'message' => 'This Task Name is already exits'], 200);
			}else{
				$tasks = New Tasks();
				
				$tasks->taskName = $input_data->taskName;
				$tasks->taskType = $input_data->taskType;
				
				
				if($tasks->save()){
					return response()->json(['error' => false, 'message' => 'Task Created Successfully'], 200);
				}else{
					return response()->json(['error' => true, 'message' => 'Oops! Something went wrong.'], 200);
				}
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
		
		
	}
	
	public function updateTask(Request $request){
		$messages = [ 
            'taskId.required' => 'The task Name field is required.',
        ];
		
		$validationRules = array(
			'taskId' => 'required',
	    );

		$validator = Validator::make($request->all(), $validationRules, $messages);

		if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }
		$taskId = $request['taskId'];
		
		$tasks = Tasks::find($taskId);
                            
		
		if(empty($tasks)){
			return response()->json(['error' => true, 'message' => 'This Task does not exits'], 200);
		}
			
		
		$tasks->taskName = $request['taskName'];
		if ($request->file('taskLogo')) {
			$image = $request->file('taskLogo');
			$path = $image->store('images', 'public'); // Store in the 'public/images' directory
			$tasks->taskLogo = $path;
		}
		$tasks->taskDesc = $request['taskDesc'];
		$tasks->taskPrice = $request['taskPrice'];
		$tasks->taskStatus = $request['taskStatus'];
		$tasks->genre = $request['genre'];
		$tasks->artistNames = $request['artistNames'];
		$tasks->languages = $request['languages'];
		
		if($tasks->save()){
			return response()->json(['error' => false, 'message' => 'Task Updated Successfully'], 200);
		}else{
			return response()->json(['error' => true, 'message' => 'Oops! Something went wrong.'], 200);
		}
	}
	
	public function userCampaignApproval(Request $request){
		$messages = [ 
            'taskTrackId.required' => 'The task Track Id field is required.',
        ];
		
		$validationRules = array(
			'taskTrackId' => 'required',
	    );

		$validator = Validator::make($request->all(), $validationRules, $messages);

		if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }
		$taskTrackId = $request['taskTrackId'];
		$isApproved = $request['taskStatus'];
		
		$taskTrackDetails = UserTaskTracking::find($taskTrackId);
		
		if(empty($taskTrackDetails)){
			return response()->json(['error' => true, 'message' => 'Provided Task Tracking Id does not exit. Please check.'], 200);
		}
		$rejectReason = "";
		
		if($isApproved == 2){
			$rejectReason = $request['rejectReason'];
		}
		
		if($isApproved == 1){
			echo "coming this case";
			// call fund Distribution Function 
			$this->fundDistribution($taskTrackDetails->userId);
			die;
		}
		
		$taskTrackDetails->isApproved = $isApproved;
		$taskTrackDetails->rejectReason = $rejectReason;
		
		if($taskTrackDetails->save()){
			return response()->json(['error' => false, 'message' => 'User Task Status Updated Successfully'], 200);
		}else{
			return response()->json(['error' => true, 'message' => 'Oops! Something went wrong.'], 200);
		}
		
	}
	public function fundDistribution($userId){
		echo "User Id =".$userId;
		die;
	}
	
	public function campaignApproveReject(Request $request){
		$messages = [ 
            'campaignId.required' => 'The Campaign Id field is required.',
			'campaignId.integer' => 'The Campaign Id field should be integer type.',
			'campaignApprovalStatus.required' => 'The Campaign Approval Status field is required.',
			'campaignApprovalStatus.integer' => 'The Campaign Approval Status field should be integer type.'
        ];
		
		$validationRules = array(
			'campaignId' 				=> 'required | integer',
			'campaignApprovalStatus'	=> 'required | integer'
	    );

		$validator = Validator::make($request->all(), $validationRules, $messages);

		if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }
		
		$campaignId 			= $request['campaignId'];
		$approveRejectStatus 	= $request['campaignApprovalStatus'];
		
		$campaignDetails = Campaigns::find($campaignId);
		
		if(empty($campaignDetails)){
			return response()->json(['error' => true, 'message' => 'Provided Campaign Id does not exit. Please check.'], 200);
		}
		
		
		$campaignDetails->isApproved = $approveRejectStatus;
		
		
		if($campaignDetails->save()){
			return response()->json(['error' => false, 'message' => 'Campaign Approval Status Updated Successfully'], 200);
		}else{
			return response()->json(['error' => true, 'message' => 'Oops! Something went wrong.'], 200);
		}
	}
}
