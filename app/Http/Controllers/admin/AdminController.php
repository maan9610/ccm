<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

use App\Models\Tasks;
use App\Models\Campaigns;
use App\Models\Advertisers;
use App\Models\WithdrawRequests;

class AdminController extends Controller
{
	public function createTask(Request $request){
		$messages = [
            'taskName.required' => 'The task Name field is required.',
            'taskLogo.required' => 'The Task logo Field is required',
			'taskDesc.required' => 'The Task Description Field is required',
			'taskPrice.required' => 'The Task Price Field is required',
			'taskStatus.required' => 'The Task Status Field is required',
			'genre.required' => 'The genre Field is required',
			'artistNames.required' => 'The Artist Field is required',
        ];
		
		$validationRules = array(
	        'taskName' => 'required',
			'taskLogo' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
			'taskDesc' => 'required',
			'taskPrice' => 'required | integer',
			'taskStatus' => 'required',
			'genre' => 'required',
			'artistNames' => 'required'
			
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
			
			$tasks->taskName = $request['taskName'];
			if ($request->file('taskLogo')) {
				$image = $request->file('taskLogo');
				$path = $image->store('images', 'public'); // Store in the 'public/images' directory
			}
			$tasks->taskLogo = $path;
			$tasks->taskDesc = $request['taskDesc'];
			$tasks->taskPrice = $request['taskPrice'];
			$tasks->taskStatus = $request['taskStatus'];
			$tasks->genre = $request['genre'];
			$tasks->artistNames = $request['artistNames'];
			$tasks->languages = $request['languages'];
			
			if($tasks->save()){
				return response()->json(['error' => false, 'message' => 'Task Created Successfully'], 200);
			}else{
				return response()->json(['error' => true, 'message' => 'Oops! Something went wrong.'], 200);
			}
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
	public function createCampaign(Request $request){
		$messages = [
            'advertiserId.required' => 'The Advertiser Id field is required.',
            'planType.required' => 'The Plan Type Field is required',
			'campaignName.required' => 'The Campaign Name Field is required',
			'campDesc.required' => 'The Campaign Description Field is required',
			'recordLabelName.required' => 'Therecord Label Name Field is required',
			'genre.required' => 'The Genre Field is required',
			'membersList.required' => 'The Member List Field is required',
			'referenceLinks.required' => 'The Reference Links Field is required',
			'isAssetDownloadable.required' => 'The Asset Downloadable Field is required',
			'selectedLang.required' => 'The Selected Language Field is required',
			'creatorCategory.required' => 'The Creator Category Field is required',
			'gender.required' => 'The Gender Field is required',
			'ageLimits.required' => 'The Age Limits Field is required',
			'targetedLocations.required' => 'The Targeted Locations Field is required',
			'taskId.required' => 'The Task Id Field is required',
			'campAmount.required' => 'The Campaign Amount Field is required',
			'estimatedReturnValues.required' => 'The Estimated Return Value Field is required',
			'startDate.required' => 'The Start Date Field is required',
			'endDate.required' => 'The End Date Field is required',
        ];
		
		$validationRules = array(
			'advertiserId' => 'required',
			'planType' => 'required',
			'campaignName' => 'required',
			'campDesc' => 'required',
			'recordLabelName' => 'required',
			'membersList' => 'required',
			'referenceLinks' => 'required',
			'isAssetDownloadable' => 'required',
			'selectedLang' => 'required',
			'genre' => 'required',
			'creatorCategory' => 'required',
			'gender' => 'required',
			'ageLimits' => 'required',
			'targetedLocations' => 'required',
			'taskId' => 'required',
			
			'campAmount' => 'required',
			'estimatedReturnValues' => 'required',
			'startDate' => 'required',
			'endDate' => 'required'
	    );
		
		$validator = Validator::make($request->all(), $validationRules, $messages);

		if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }
		$campaignName = $request['campaignName'];
		$advertiserId = $request['advertiserId'];
		
		$campExits = Campaigns::where('campaignName', $campaignName)
								->where('advertiserId', $advertiserId)
								->exists();
                            
		
		if(!empty($campExits)){
			return response()->json(['error' => true, 'message' => 'This Campaign is already exits'], 200);
		}
		
		$campaigns = New Campaigns();
		
		$advertiserCheck = Advertisers::find($request['advertiserId']);
		if(empty($advertiserCheck)){
			return response()->json(['error' => true, 'message' => 'This Advertiser does not exits'], 200);
		}
		
		
		$campaigns->advertiserId = $request['advertiserId'];
		$campaigns->planType = $request['planType'];
		$campaigns->campaignName = $request['campaignName'];
		$campaigns->url_key  = $campaigns->generateUniqueUrlKey($request['campaignName']);
		
		$campThumbPaths = array();
		
		if ($request->hasfile('campaignsThumbnails')) {
            foreach ($request->file('campaignsThumbnails') as $image) {
                $path = $image->store('campaigns', 'public');
                $campThumbPaths[] = basename($path);
            }
        }
		$campaigns->campaignThumnails = implode(', ', $campThumbPaths); 
		$campaigns->campDesc = $request['campDesc'];
		$campaigns->recordLabelName = $request['recordLabelName'];
		$campaigns->membersList = $request['membersList'];
		
		$campAssets = array();
		if ($request->hasfile('asset')) {
            foreach ($request->file('asset') as $image) {
                $assetPath = $image->store('campaigns', 'public'); 
                $campAssets[] = basename($assetPath);
            }
        }
		
		$campaigns->assets = implode(', ', $campAssets); 
		$campaigns->referenceLinks = $request['referenceLinks'];
		$campaigns->isAssetDownloadable = $request['isAssetDownloadable'];
		$campaigns->selectedLanguage = $request['selectedLanguage'];
		$campaigns->genre = $request['genre'];
		$campaigns->gender = $request['gender'];
		$campaigns->ageLimits = $request['ageLimits'];
		$campaigns->creatorCategory = $request['creatorCategory'];
		$campaigns->targetedLocations = $request['targetedLocations'];
		$campaigns->taskId = $request['taskId'];
		if($request['planType'] == 2){
			$campaigns->taskdestination = $request['taskdestination'];
		}
		
		$campaigns->campaignAmount = $request['campAmount'];
		$campaigns->estimateReturnvalues = $request['estimatedReturnValues'];
		$campaigns->startDate = $request['startDate'];
		$campaigns->endDate = $request['endDate'];
		
		if($campaigns->save()){
			return response()->json(['error' => false, 'message' => 'Campaign Successfully Created'], 200);
		}else{
			return response()->json(['error' => true, 'message' => 'Oops! Something went wrong in creating Campaign.'], 200);
		}
		
		
	}
	
	public function withdrawRequestChangeStatus(Request $request){
		$messages = [ 
            'requestId.required' => 'The Request Id field is required.',
			'requestId.integer' => 'The Request Id field should be integer.',
			'status.required' => 'The status field is required'
        ];
		
		$validationRules = array(
			'requestId' => 'required | integer',
			'status' => 'required'
	    );

		$validator = Validator::make($request->all(), $validationRules, $messages);

		if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }
		
		$requestId = $request['requestId'];
		
		$requestData = WithdrawRequests::find($requestId);
		
		if(empty($requestData)){
			return response()->json(['error' => true, 'message' => 'Withdraw Request Data Not Found.'], 200);
		}
		
		$requestData->status = $request['status'];
		if($request['status'] == 2){
			$requestData->rejectReason = $request['r'];
		}
		
		if($requestData->save()){
			return response()->json(['error' => false, 'message' => 'Withdraw Request Status Successfully Changed'], 200);
		}else{
			return response()->json(['error' => true, 'message' => 'Oops! Something went wrong in Changing Withdraw Request Status.'], 200);
		}
	}
	public function getAllTasks(Request $request){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			if(isset($input_data->taskType)){
				$tasks = Tasks::where('status', 1)
								->where('taskType', $input_data->taskType)
								->get();
			}else{
				
				$tasks = Tasks::where('status', 1)->get();
			}
			
			if(empty($tasks)){
				return response()->json(['error' => true, 'message' => 'No Data Found.'], 200);
			}
			
			
			
			$tasksArr = array();
			foreach($tasks as $details){
				$arr = array();
				
				if($details->taskType == 1){
					$arr['taskType'] = "Basic";
				}else{
					$arr['taskType'] = "Premium";
				}
				$arr['taskName'] = $details['taskName'];
				if($details->isLink == 1){
					$arr['link'] = "Link Required";
				}else{
					$arr['link'] = "Link Not Required";
				}
				if($details->isVideo == 1){
					$arr['video'] = "Video Required";
				}else{
					$arr['video'] = "Video Not Required";
				}
				if($details->isDestinationReq == 1){
					$arr['destination'] = "Destination Required";
				}else{
					$arr['destination'] = "Destination Not Required";
				}
				
				$tasksArr[] = $arr;
			}
			
			
			return response()->json(['error' => false, 'message' => "Data Found", 'data' => $tasksArr], 200);
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
}
