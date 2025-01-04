<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Storage;


use App\Models\Users;
use App\Models\Tasks;
use App\Models\UserSavedTasks;
use App\Models\Campaigns;
use App\Models\Advertisers; 
use App\Models\UserTaskTracking;
use App\Models\WithdrawRequests;
use App\Models\UserTaskFiles;
use App\Models\Follows;
use App\Models\UsersDocuments;
use App\Models\UserSocialLinks;

class UserController extends Controller
{
    public function login(Request $request)
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key = isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'username.required' => 'The username field is required.',
				'password.required' => 'The password field is required', 
			];

			$loginType = $input_data->loginType;
			if(empty($loginType)){
				return response()->json(['exists' => false, 'message' => 'The Login Type field is required'], 200);
			}
			if($loginType == 1){ // login with OTP
				
				$validationRules = array(
					'username' => 'required',
				);
			}else if($loginType == 2){ // login with password

				$validationRules = array(
					'username' => 'required',
					'password' => 'required'
				);
			}

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}

			$username = $input_data->username;
			

			if($loginType == 1){
				$user = Users::where(function ($query) use ($username) {
								$query->where('userName', $username)
									  ->orWhere('phoneNumber', $username);
							})
							->where('status', 1)
							->first();

				if (!$user) {
					return response()->json(['exists' => false, 'message' => 'Invalid credentials or user not found'], 200);
				} 
				
				
				$otp = random_int(100000, 999999);
				// Save the OTP to the user's table
				$user->otp = $otp;
				$user->save();
				sendMessage($user->phoneNumber, $otp);
				return response()->json(['exists' => true, 'message' => 'OTP successfully send on your number'], 200);
			}else{
				$password = $input_data->password;
				$user = Users::where(function ($query) use ($username) {
								$query->where('userName', $username)
									  ->orWhere('phoneNumber', $username);
							})
							->where('status', 1)
							->first();
				
				if ($user && Hash::check($password, $user->password)) {
					$userId = $user->id;
					return response()->json(['exists' => true, 'message' =>' User Successfully Found ', "userId"=> $userId], 200);
				} else {
					return response()->json(['message' => 'Invalid credentials or user not found'], 404);
				}
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
    }

    public function signUp(Request $request)
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'fullName.required' => ' The Name field is required',
				'fullName.string' => 'The Name field must be string',
				'phoneNumber.required' => 'The Phone Number field is required',
				'phoneNumber.integer' => 'The Phone Number field must be integer only',
				'phoneNumber.digits' => "The Phone Number field must be of 10 digits",
				'emailAddress.required' => ' The Email Id field is required',
				'password.required' => 'The Password field is required'
			];

			$validationRules = array(
				'fullName' => 'required | string',
				'phoneNumber' => 'required | integer | digits:10',
				'emailAddress' => 'required',
				'password' => 'required'
			); 

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}

			$fullName = $input_data->fullName;
			$phoneNumber = $input_data->phoneNumber;
			$emailAddress = $input_data->emailAddress;
			$password = $input_data->password;

			$usersExits = Users::where('phoneNumber', $phoneNumber)->exists();
			
			if($usersExits){
				return response()->json(['error' => true, 'message' =>' User Already exists'], 200);
				
			}else{
				$users = New Users();

				$users->fullName = $fullName;
				$users->phoneNumber = $phoneNumber;
				$users->emailAddress = $emailAddress;
				$users->password = bcrypt($password);

				if($users->save()){
					$userId = $users->id;
					$usersDocuments = New UsersDocuments();
					
					$usersDocuments->userId = $userId;
					$usersDocuments->save();
					
					$userSocialLinks = New UserSocialLinks();
					$userSocialLinks->userId = $userId;
					$userSocialLinks->save();
					
					return response()->json(['error' => false, 'message' =>' User Registered'], 200);
				}else{
					return response()->json(['error' => true, 'message' =>' Something went wrong'], 200);
				}
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
    }

    public function updateProfile(Request $request)
	{
		
		//$json = file_get_contents('php://input');
		$json = json_encode($request->all());
		//print_r($json);
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'fullName.required' => 'The Full Name field is required',
				'fullName.string' => 'The Full Name must be String',
				'gender.required' => 'The Gender Field is required',
				'gender.string' => 'The Gender Field must be String',
				'dob.required' => 'The dob field is Required',
				'dob.string' => 'The dob field must be string',
				'genre.string' => 'The Genre Field must be String',
				'about.string' => 'The About Field must be String',
				'location.string' => 'The Location must be String',
				'language.string' => 'The language must be String'
			];

			$validationRules = array(
				'fullName' => 'required | string',
				'userName' => 'required | string',
				'gender' => 'required | string',
				'dob' => 'required | string',
				'genre' => ' string',
				'about' => ' string',
				'location' => 'string',
				'language' => 'string',
			);

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}

			if(empty($request['userId'])){
				return response()->json(['error' => true, 'message' =>' User Id Field is Required'], 200);
			}

			$userId = $request['userId'];
			
			$users = Users::find($userId);
			
			if(!$users){
				 return response()->json(['error' => true, 'message' =>' User Not Found'], 200);
			}  
			$gender = 0;
			if($input_data->gender == "Female") {
				$gender = 1;
			} else if($input_data->gender == "Male"){
				$gender = 2;
			} 
			$users->fullName 		= $input_data->fullName;
			$users->userName 		= $input_data->userName;
			$users->gender 			= $gender;
			$users->dob 			= date("Y-m-d", strtotime($request['dob']));
			$users->genre 			= $input_data->genre;
			$users->about 			= $input_data->about;
			$users->city 			= $input_data->location;
			$users->language 		= $input_data->language;
			//$users->links 		= $request['links'];
			if ($request->file('profilePic')) {
				
				$image = $request->file('profilePic');
				$path = $image->store('userProfilePic', 'public'); // Store in the 'public/images' directory
				
				$users->profilePic = basename($path);
			}
			
			$usersDocuments = UsersDocuments::where('userId', $userId)->first();
			
			if(empty($usersDocuments)){
				$usersDocuments = New UsersDocuments();
				
				$usersDocuments->userId = $userId;
				
				$usersDocuments->save();
				
				$uDocId = $usersDocuments->id;  
			}else{
				
				$uDocId = $usersDocuments->id;
			}
			
			$uDocDetails = UsersDocuments::find($uDocId);
			
			$isUploaded = $isSocialLinkUpdated = 1;
			
			
			if ($request->file('panCard')) {
				$panImage = $request->file('panCard');
				
				$panExtension = $panImage->getClientOriginalExtension();
				 
				if (in_array($panExtension, ['jpg', 'png', 'jpeg', 'webp'])) {
					$panPath = $panImage->store('userDocuments', 'public'); // Store in the 'public/images' directory
					$uDocDetails->pan_card = basename($panPath);
				} else {
					$isUploaded = 0;
				}
				
			}
			
			if ($request->file('aadharFront')) {
				$aadharFrontImage = $request->file('aadharFront');
				$aadharFrontExtension = $aadharFrontImage->getClientOriginalExtension();
				
				if (in_array($aadharFrontExtension, ['jpg', 'png', 'jpeg', 'webp'])) {
					$aadharFrontPath = $aadharFrontImage->store('userDocuments', 'public'); // Store in the 'public/images' directory
					$uDocDetails->aadhar_front = basename($aadharFrontPath);
				} else {
					$isUploaded = 0;
				}
			}
			
			if ($request->file('aadharBack')) {
				$aadharBackImage = $request->file('aadharBack');
				$aadharBackExtension = $aadharBackImage->getClientOriginalExtension();
				
				if (in_array($aadharBackExtension, ['jpg', 'png', 'jpeg', 'webp'])) {
					$aadharBackPath = $aadharBackImage->store('userDocuments', 'public'); // Store in the 'public/images' directory
					$uDocDetails->aadhar_back = basename($aadharBackPath);
				} else {
					$isUploaded = 0;
				}
			}
			
			if($isUploaded == 1){
				if($uDocDetails->save()){
					$isUploaded = 1;
				}
			}
			
			if(isset($input_data->facebookLink) || isset($input_data->instagramLink) || isset($input_data->youtubeLink) || isset($input_data->twitterLink) || isset($input_data->linkedinLink) || isset($input_data->telegramLink) || isset($input_data->otherLink)){
				$userSocialLinkExists = UserSocialLinks::where('userId', $userId)->first();
			
				if(empty($userSocialLinkExists)){
					$uSocialLinks = New UserSocialLinks();
					
					$uSocialLinks->userId = $userId;
					
					$uSocialLinks->save();
					
					$uSLId = $uSocialLinks->id;  
				}else{
					$uSLId = $userSocialLinkExists->id;
				}
				
				$userSocialLink = UserSocialLinks::find($uSLId);
				
				if(isset($input_data->facebookLink)){
					$userSocialLink->facebookLink = $input_data->facebookLink;
				}
				
				if(isset($input_data->instagramLink)){
					$userSocialLink->instagramLink = $input_data->instagramLink;
				}
				
				if(isset($input_data->youtubeLink)){
					$userSocialLink->youtubeLink = $input_data->youtubeLink;
				}
				
				if(isset($input_data->twitterLink)){
					$userSocialLink->twitterLink = $input_data->twitterLink;
				}
				
				if(isset($input_data->linkedinLink)){
					$userSocialLink->linkedinLink = $input_data->linkedinLink;
				}
				
				if(isset($input_data->telegramLink)){
					$userSocialLink->telegramLink = $input_data->telegramLink;
				}
				
				if(isset($input_data->otherLink)){
					$userSocialLink->otherLink = $input_data->otherLink;
				}
				
				if($userSocialLink->save()){
					$isSocialLinkUpdated = 1;
				}else{
					$isSocialLinkUpdated = 0;
				}
			}
			
			if($users->save() && $isUploaded == 1 && $isSocialLinkUpdated == 1){
				return response()->json(['error' => false, 'message' =>'User Profile Updated'], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'Something went wrong'], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
    }
	
	public function verifyOtp(Request $request)
	{ 
	
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'otp.required' => 'The OTP field is required.',
				'otp.integer' => 'The OTP field should be integer',
				'otp.digit' => 'The OTP field should be only 6 digit',
			];
			
			$validationRules = array(
				'userId' => 'required | integer',
				'otp' => 'required | integer | digits:6',
			);

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$userId = $input_data->userId;
			$userOtp = $input_data->otp;
			
			$users = Users::find($userId);
			
			if(!$users){
				 return response()->json(['error' => true, 'message' =>' User Not Found'], 200);
			}  
			
			$otp = $users->otp;
			
			if($userOtp == $otp){
				$users->otp = NULL;
				$users->save();
				
				return response()->json(['error' => false, 'message' =>' OTP Successfully verified'], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'OTP Not Verified. Please check again.'], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function getApprovedTask()
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$type =  1;
			if(isset($input_data->type)){
				$type = $input_data->type;   // 1 - all tasks , 2 - upoming tasks
			}
			
			if($type == 1){ // all tasks
				if(isset($input_data->taskcategory)){
					$taskCategory = $input_data->taskcategory;
					$tasks = Campaigns::where('isApproved', 1)
									->where('campCategory', $taskCategory)
									->get();
				}else{
					$tasks = Campaigns::where('isApproved', 1)->get();
				}
			}else { // upcoming tasks
				if(isset($input_data->taskcategory)){
					$taskCategory = $input_data->taskcategory;
					$tasks = Campaigns::where('isApproved', 1)
									->where('campCategory', $taskCategory)
									->where('startDate', '>', Carbon::today()) 
									->get();
				}else{
					$tasks = Campaigns::where('isApproved', 1)
										->where('startDate', '>', Carbon::today()) 
										->get();
				}
			}
			
			
			if(empty($tasks)){
				return response()->json(['error' => false, 'message' => 'There are no approved tasks.'], 200);
			}else{
				$taskArray = array();
				foreach($tasks as $taskDetails){
					$arr = array();
					
					$arr['campaignId'] = $taskDetails['id'];
					$arr['campaignName'] = $taskDetails['campaignName'];
					$baseUrl = URL::to('/');
					$campThumbArr = [];
					if(!empty($taskDetails->campaignThumnails)){
						$thumbnailsArr = explode(", ", $taskDetails->campaignThumnails);
						
						foreach($thumbnailsArr as $item){
							$campThumbArr[] = $baseUrl."/images/campaigns/".$item;
							
						}
					}
					$arr['campaignThumnails'] = $campThumbArr; 
					
					$arr['campDesc'] = $taskDetails['campDesc'];
					$arr['campaignAmount'] = $taskDetails['campaignAmount'];
					if($taskDetails['status'] == 1){
						$arr['campStatus'] = "Active";
					}else{
						$arr['campStatus'] = "Inactive";
					}
					$arr['campCategory'] = "";
					if($taskDetails['campCategory'] == 1){
						$arr['campCategory'] = "Trending";
					}else if($taskDetails['campCategory'] == 2){
						$arr['campCategory'] = "Latest";
					}else if($taskDetails['campCategory'] == 3){
						$arr['campCategory'] = "Recommended";
					} 
					
					$arr['genre'] = $taskDetails['genre'];
					$arr['membersList'] = $taskDetails['membersList'];
					$currentDate = date("Y-m-d");
					$date1 = Carbon::parse($currentDate);
					$date2 = Carbon::parse($taskDetails->endDate);

					// Calculate the difference in days
					$differenceInDays = $date1->diffInDays($date2);
					$arr['daysLeft'] = $differenceInDays. " Days Left!";
					$arr['selectedLanguage'] = $taskDetails->selectedLanguage;
					
					$taskArray[] = $arr;
					
				}
				return response()->json(['error' => false, 'approvedTask' => $taskArray], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function getUserDetails(Request $request)
	{
		
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'userId.required' => 'The OTP field is required.',
				'userId.integer' => 'The User Id field should be integer.'
			];
			
			$validationRules = array(
				'userId' => 'required | integer',
			);

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$userId = $input_data->userId;
			
			$users = Users::find($userId);
			if(!$users){
				return response()->json(['error' => true, 'message' => "Users Details Not Found."], 200);
			}
			
			$followeesCount = $users->follows()->count();
			
			$usersDocs = UsersDocuments::where('userId', $userId)->first();
			
			$userData = array();
			
			$userData['fullName'] = $users->fullName;
			$userData['userName'] = $users->userName;
			$userData['phoneNumber'] = $users->phoneNumber;
			$userData['emailAddress'] = $users->emailAddress;
			$userData['gender'] = $users->gender;
			$userData['dob'] = date("d M Y", strtotime($users->dob));
			$userData['about'] = $users->about;
			$socialLinks = [];
			if(!empty($users->socialLinks)){
				$socialArr = explode(",", $users->socialLinks);
				foreach($socialArr as $item){
					$socialLinks[] = $item;
				}
			}
			$userData['socialLinks'] = $socialLinks;
			$userData['language'] = $users->language;
			
			$userData['isCreator'] = 0;
			$userData['isPanVerified'] = 0;
			$userData['isAadharFrontVerified'] = 0;
			$userData['isAadharBackVerified'] = 0;
			
			if(!empty($usersDocs)){
				$userData['isPanVerified'] = $usersDocs->isPanVerified;
				$userData['isAadharFrontVerified'] = $usersDocs->isAadharFrontVerified;
				$userData['isAadharBackVerified'] = $usersDocs->isAadharBackVerified;
				
				if($usersDocs->isPanVerified == 1 && $usersDocs->isAadharFrontVerified == 1 && $usersDocs->isAadharBackVerified == 1){
					$userData['isCreator'] = 1;
				}
			}
			
			$baseUrl = URL::to('/');
			$userData['profilePic'] = $baseUrl."/images/userProfilePic/".$users->profilePic; ;
			if($users->isVerified == 1){
				$userData['isVerified'] = "Verified";
			}else{
				$userData['isVerified'] = "Not Verified";
			}
			if($users->status == 1){
				$userData['status'] = "Active";
			}else{
				$userData['status'] = "Inactive";
			}
			$userData['walletBalance'] = $users->walletBalance;
			$userData['followCount'] = $followeesCount;
			$userData['genre'] = $users->genre;
			$userData['location'] = $users->city;
			
			return response()->json(['error' => false, 'userDetails' => $userData], 200);
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function saveTask()
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$userId = $input_data->userId;
			$taskId = $input_data->taskId;
			
			$userCheck = Users::find($userId);
			if(!$userCheck){
				return response()->json(['error' => false, 'message' => "User Not Found."], 200);
			}
			
			$taskCheck = Campaigns::find($taskId);			
			if(!$taskCheck){
				return response()->json(['error' => false, 'message' => "Task Not Found."], 200);
			}
			
			$userTaskExits = UserSavedTasks::where('userId', $userId)
											->where('campaignId', $taskId)
											->exists();
			if(!empty($userTaskExits)){
				return response()->json(['error' => false, 'message' => "This task already exits."], 200);
			}
			
			$userTask = New UserSavedTasks();
			
			$userTask->userId = $userId;
			$userTask->campaignId = $taskId;
			
			if($userTask->save()){
				return response()->json(['error' => false, 'message' =>'Task Successfully Saved'], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'Oops! Something went wrong while adding task.'], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function deleteSaveTask()
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$id = $input_data->id;
			
			$userTask = UserSavedTasks::find($id);
			
			if(!$userTask){
				return response()->json(['error' => true, 'message' =>'User did not saved this task.'], 200);
			}else{
				$userTask->delete();
				return response()->json(['error' => false, 'message' =>'Successfully removed from Saved Task.'], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	public function getUserSaveTasks()
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$userId = $input_data->userId;
			
			
			
			$userSavedTasks = UserSavedTasks::with(['campaigns'])
										->where('userId', $userId)
										->join('campaigns', 'user_saved_tasks.campaignId', '=', 'campaigns.id')
										->select('user_saved_tasks.*', 'campaigns.*', 'user_saved_tasks.id')
										->get();
			
			
			
			$savedTaskss = array();
			
			foreach($userSavedTasks as $savedTasks){
				$arr = array();
				$arr['uId'] = $savedTasks->id;
				$arr['campaignId'] = $savedTasks->campaignId;
				$arr['campaignName'] = $savedTasks->campaignName;
				$baseUrl = URL::to('/');
				$arr['campaignSlug'] = $baseUrl."/tasks/".$savedTasks->url_key;
				$arr['selectedLanguage'] = $savedTasks->selectedLanguage;
				if($savedTasks->status == 1){
					$arr['status'] = "Active";
				}else{
					$arr['status'] = "Inactive";
				}
				
				$currentDate = date("Y-m-d");
				 // Parse dates using Carbon
				$date1 = Carbon::parse($currentDate);
				$date2 = Carbon::parse($savedTasks->endDate);

				// Calculate the difference in days
				$differenceInDays = $date1->diffInDays($date2);
				$arr['daysLeft'] = $differenceInDays. "Days Left!";
				$arr['campaignAmount'] = $savedTasks->campaignAmount;
				
				$campThumbArr = [];
				if(!empty($savedTasks->campaignThumnails)){
					$thumbnailsArr = explode(", ", $savedTasks->campaignThumnails);
					foreach($thumbnailsArr as $item){
						$campThumbArr[] = $baseUrl."/images/campaigns/".$item;
					}
				}
				$arr['campaignThumnails'] = $campThumbArr; 
				$savedTaskss[] = $arr;
			}
			
			return response()->json(['error' => true, 'message' => " Data Found", 'savedTasks' => $savedTaskss], 200);
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	public function getCampaignDetails(Request $request)
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key = isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'campaignId.required' => 'The Campaign Id field is required.',
				'campaignId.integer' => 'The Campaign Id field should be integer.'
			];
			
			$validationRules = array(
				'campaignId' => 'required | integer',
			);
			
			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			$campaignId = $input_data->campaignId;
			
			$campDetails = Campaigns::find($campaignId);
			
			if(!$campDetails){
				return response()->json(['error' => true, 'message' =>'Campaigns Not Found.'], 200);
			}
			
			//dd($campaignsData);
			$arr = array();
			
				$advertiserData = Advertisers::find($campDetails->advertiserId);
				
				$arr['advertiserId'] = $advertiserData->fullName;
				if($campDetails->planType == 1){
					$arr['planType'] = "Basic";
				} else if($campDetails->planType == 2){
					$arr['planType'] = "Premium";
				}
				$arr['campaignId'] = $campDetails->id ;
				$arr['campaignName'] = $campDetails->campaignName;
				$baseUrl = URL::to('/');
				$arr['campaignSlug'] = $baseUrl."/tasks/".$campDetails->url_key;
				$campThumbArr = [];
				if(!empty($campDetails->campaignThumnails)){
					
					$thumbnailsArr = explode(", ", $campDetails->campaignThumnails);
					
					foreach($thumbnailsArr as $item){
						$campThumbArr[] = $baseUrl."/images/campaigns/".$item;
						
					}
				}
				$arr['campaignThumnails'] = $campThumbArr;
				$arr['campDesc'] = $campDetails->campDesc;
				$arr['recordLabelName'] = $campDetails->recordLabelName;
				$arr['membersList'] = $campDetails->membersList;
				
				$arr['referenceLinks'] = $campDetails->referenceLinks;
				if($campDetails->isAssetDownloadable == 1){
					$arr['isAssetDownloadable'] = "Yes";
				}else{
					$arr['isAssetDownloadable'] = "No";
				}
				$arr['isAssetDownloadable'] = $campDetails->isAssetDownloadable;
				$campAssets = [];
				if(!empty($campDetails->assets)){
					
					$assetsArr = explode(", ", $campDetails->assets);
					
					foreach($assetsArr as $item){
						$arr2 = [];
						
						$arr2['fileUrl'] = $baseUrl."/images/campaigns/".$item;
						$arr2['fileType'] = pathinfo($item, PATHINFO_EXTENSION);
						$campAssets[] = $arr2;
						
					}
				}
				$arr['assets'] = $campAssets;
				$arr['selectedLanguage'] = $campDetails->selectedLanguage;
				$arr['genre'] = $campDetails->genre;
				$arr['gender'] = $campDetails->gender;
				$arr['ageLimits'] = $campDetails->ageLimits;
				$arr['creatorCategory'] = $campDetails->creatorCategory;
				$arr['targetedLocations'] = $campDetails->targetedLocations;
				
				$tasksData = Tasks::find($campDetails->taskId);
				
				$arr['task'] = $tasksData->taskName;
				$arr['taskdestination'] = $campDetails->taskdestination;
				$arr['campaignAmount'] = $campDetails->campaignAmount;
				$arr['estimateReturnvalues'] = $campDetails->estimateReturnvalues;
				$arr['startDate'] = date("d M Y", strtotime($campDetails->startDate));
				$arr['endDate'] = date("d M Y", strtotime($campDetails->endDate));
				if($campDetails->status == 1){
					$arr['status'] = "Active";
				}else{
					$arr['status'] = "Inactive";  
				}
				
				$usersTaskTracking = UserTaskTracking::join('users', 'user_task_trackings.userId', '=', 'users.id')
											->where('user_task_trackings.campaignId', $campaignId)  
											->distinct()
											->select('users.fullName', 'users.about', 'users.profilePic')  
											->get();
				$associateCreator = [];
				if(!empty($usersTaskTracking)){
					
					foreach($usersTaskTracking as $item){
						$arr1 = [];
						$arr1['fullName'] = $item['fullName'];
						$arr1['about'] = $item['about'];
						$arr1['profilePic'] = $baseUrl."/images/userProfilePic/".$item['profilePic'];
						
						$associateCreator[] = $arr1;
					}
				}
				$arr['associateCreator'] = $associateCreator;
				$currentDate = date("Y-m-d");
				$date1 = Carbon::parse($currentDate);
				$date2 = Carbon::parse($campDetails->endDate);

				// Calculate the difference in days
				$differenceInDays = $date1->diffInDays($date2);
				$arr['daysLeft'] = $differenceInDays. " Days Left!";
				
				$arr['campUsersCount'] = "238/1000"; 
				if($campDetails->isApproved == 1){
					$arr['approvalStatus'] = "Approved";
				}else{
					$arr['approvalStatus'] = "Not Approved";  
				}
				
			return response()->json(['error' => true, 'message' => " Data Found", "campDetails" => $arr], 200);
			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function startTask(Request $request)
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key = isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'campaignId.required' => 'The Campaign Id field is required.',
				'campaignId.integer' => 'The Campaign Id field should be integer.',
				'userId.required' => 'The User Id field is required.',
				'userId.integer' => 'The User Id field should be integer.'
			];
			
			$validationRules = array(
				'userId' => 'required | integer',
				'campaignId' => 'required | integer'
			);
			
			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			$userId = $input_data->userId;
			$campaignId = $input_data->campaignId;
			
			$userCheck = Users::find($userId);
			if(!$userCheck){
				return response()->json(['error' => true, 'message' => "User Not Found."], 200);
			}
			
			$taskCheck = Campaigns::find($campaignId);			
			if(!$taskCheck){
				return response()->json(['error' => true, 'message' => "Task Not Found."], 200);
			}
			
			if($taskCheck->isApproved == 0){
				return response()->json(['error' => true, 'message' => "Campaigns is not Approved Yet. You cannot start this."], 200);
			}
			if($taskCheck->status == 0){
				return response()->json(['error' => true, 'message' => "Campaigns is Inactive. You cannot start this."], 200);
			}
			
			$duplicacyCheck = UserTaskTracking::where('userId', $userId)
												->where('campaignId', $campaignId)
												->exists();
			
			if(!empty($duplicacyCheck)){
				return response()->json(['error' => true, 'message' => "User Already Started or Completed this Task."], 200);
				die;
			}
			
			$userTrack = New UserTaskTracking();
			
			$userTrack->userId = $userId;
			$userTrack->campaignId = $campaignId;
			$userTrack->startTime = date("Y-m-d H:i:s");
			$userTrack->status = 0;
			
			if($userTrack->save()){
				$lastInsertedId = $userTrack->id;
				return response()->json(['error' => true, 'message' => "Task Successfully Started.", "trackingUniqueId" => $lastInsertedId], 200);
			}else{
				return response()->json(['error' => true, 'message' => "Oops! Something went wrong in Starting Task."], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
		
		
	}
	
	public function submitTask(Request $request){
		
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key = isset($request['app_key'])?trim($request['app_key']):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'id.integer' => 'The Id field should be integer.',
				'type.required' => 'The Type field is required.',
				'type.integer' => 'The Type field should be integer.',
				'campaignId.integer' => 'The Campaign Id field should be integer.',
				'userId.integer' => 'The User Id field should be integer.'
			];
			
			$validationRules = array(
				'id' => 'integer',
				'type' => 'required | integer',
				'campaignId' => 'integer',
				'userId' => 'integer'
			);
			
			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$type 			= trim($request['type']);
			
			
			if($type == 1){
				$taskTrackId = trim($request['id']);
				$userTaskTrack = UserTaskTracking::find($taskTrackId);
			}else{
				$campaignId 	= trim($request['campaignId']);
				$userId 		= trim($request['userId']);
			
				$userTaskTrack = UserTaskTracking::where('campaignId', $campaignId)
													->where('userId', $userId)
													->first();
				$taskTrackId = $userTaskTrack->id;
			}
			
			//dd($userTaskTrack);
			
			if(empty($userTaskTrack)){
				return response()->json(['error' => true, 'message' => "User did not started this task."], 200);
			}
			
			if($userTaskTrack->status == 2){
				return response()->json(['error' => true, 'message' => "You have already completed this task."], 200);
			}
			
			$campaignId = $userTaskTrack->campaignId;
			
			$campDetails = Campaigns::find($campaignId);
			
			if(empty($campDetails)){
				return response()->json(['error' => true, 'message' => "Campaign Not Found."], 200);
			}
			
			$today = Carbon::today();
			$endDate = Carbon::parse($campDetails->endDate);

			// Compare the dates
			if ($today->greaterThan($endDate)) { 
				return response()->json(['error' => true, 'message' => "You cannot submit this task as Campaign Already Ended."], 200);
			}
			
			$taskId = $campDetails->taskId;
			
			$taskDetails = Tasks::find($taskId);
			
			if(empty($taskDetails)){
				return response()->json(['error' => true, 'message' => "Task Not Found."], 200);
			}
			
			if($taskDetails->isLink == 1){
				// check if field has multiple links 
				// if yes then loop through every link , check pattern and fire insert query 
				
				
				if(isset($request['instagramLinks']) && !empty($request['instagramLinks'])){
					$instaPattern = "/^(https?:\/\/)?(www\.)?instagram\.com\/[a-zA-Z0-9_\.]+\/?$/";
					if (strpos($request['instagramLinks'], ",") === false) {
						$instaLinksArr = str_split($request['instagramLinks']);
					}else{
						$instaLinksArr = explode(",", $request['instagramLinks']);
					}
					foreach($instaLinksArr as $instaLink){
						
						$instaLink = trim($instaLink);
						if(!empty($instaLink)){
							if(preg_match($instaPattern, $instaLink) !== 1){
								return response()->json(['error' => true, 'message' => "Please enter a valid Instagram URL."], 200);
							}
							$taskSubmission = New UserTaskFiles();
							$taskSubmission->type = 4;
							$taskSubmission->taskTrackId = $taskTrackId;
							$taskSubmission->link_text = $instaLink;
							$taskSubmission->save();
						}
					}
				}
				if(isset($request['youTubeLinks']) && !empty($request['youTubeLinks'])){
					$youtubePattern = "/^(https?:\/\/)?(www\.)?(youtube\.com\/watch\?v=|youtu\.be\/)[a-zA-Z0-9_-]+$/";
					if (strpos($request['youTubeLinks'], ",") === false) {
						$youtubeLinksArr = str_split($request['youTubeLinks']);
					}else{
						$youtubeLinksArr = explode(",", $request['youTubeLinks']);
					}
					foreach($youtubeLinksArr as $youTubeLink){
						$youTubeLink = trim($youTubeLink);
						if(!empty($youTubeLink)){
							if(preg_match($youtubePattern, $youTubeLink) !== 1){
								return response()->json(['error' => true, 'message' => "Please enter a valid Youtube URL."], 200);
							}
							$taskSubmission = New UserTaskFiles();
							$taskSubmission->type = 5;
							$taskSubmission->taskTrackId = $taskTrackId;
							$taskSubmission->link_text = $youTubeLink;
							$taskSubmission->save();
						}
					}
				}
				if(isset($request['otherLinks']) && !empty($request['otherLinks'])){
					$otherPattern = "/^(https?:\/\/)?([a-zA-Z0-9-]+\.)+[a-zA-Z]{2,}(\/[^\s]*)?$/";
					if (strpos($request['otherLinks'], ",") === false) {
						$otherLinksArr = str_split($request['otherLinks']);
					}else{
						$otherLinksArr = explode(",", $request['otherLinks']);
					}
					foreach($otherLinksArr as $otherLink){
						$otherLink = trim($otherLink);
						if(!empty($otherLink)){
							if(preg_match($otherPattern, $otherLink) !== 1){
								return response()->json(['error' => true, 'message' => "Please enter a valid Link."], 200);
							}
							$taskSubmission = New UserTaskFiles();
							$taskSubmission->type = 6;
							$taskSubmission->taskTrackId = $taskTrackId;
							$taskSubmission->link_text = $otherLink;
							$taskSubmission->save();
						}
					}
				}
			}
			
			if($taskDetails->isVideo == 1){
				
				if ($request->hasFile('fileName')) {
					$maxFileSize = 10;
					$file = $request->file('fileName');
					
					$extension = $file->extension();
					$fileExtArr = array('mp4', 'webm', 'avi', 'mov', 'mkv', 'wmv', 'mp3', 'wav', 'ogg', 'flac', 'aac', 'm4a','pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg', 'webp' );
					if(!in_array($extension, $fileExtArr)){
						return response()->json(['error' => true, 'message' => "Please upload a valid File Format."], 200);
					}else{
						if($extension == 'mp4' || $extension == 'webm' || $extension == 'avi' || $extension == 'mov' || $extension == 'mkv' || $extension == 'wmv'){
							$fileType = 1;
							$maxFileSize = 200;
						} else if($extension == 'mp3' || $extension == 'wav' || $extension == 'ogg' || $extension == 'flac' || $extension == 'aac' || $extension == 'm4a'){
							$fileType = 2;
							$maxFileSize = 100;
						} else if($extension == 'pdf' || $extension == 'doc' || $extension == 'docx' || $extension == 'xls' || $extension == 'xlsx' || $extension == 'ppt' || $extension == 'pptx' || $extension == 'txt'){
							$fileType = 3;
							$maxFileSize = 10;
						} else if($extension == 'jpeg' || $extension == 'jpg' || $extension == 'png' || $extension == 'gif' || $extension == 'bmp' || $extension == 'svg' || $extension == 'webp'){
							$fileType = 4;
							$maxFileSize = 10;
						}
					}
					
					$sizeInBytes = $file->getSize();
					$sizeInMB = $sizeInBytes / 1024 / 1024;
					$formattedSizeInMB = number_format($sizeInMB, 2);
					if($formattedSizeInMB > $maxFileSize){
						return response()->json(['error' => true, 'message' => "Filesize is greater than ".$maxFileSize." MB."], 200);
					}
					
					$path = $file->store('userTaskFiles', 'public'); // 'uploads' is the directory within 'storage/app/public'
					
					$taskFiles = New UserTaskFiles();
					
					$taskFiles->taskTrackId = $taskTrackId;
					$taskFiles->filename = $path;
					$taskFiles->type = $fileType;
					
					if(!$taskFiles->save()){
						return response()->json(['error' => true, 'message' => "Oops! Something went wrong in Uploading Files."], 200);
					}
				} else {
					return response()->json(['error' => true, 'message' => "Oops! File didn't exits."], 200);
				}
			}
			
			$userTaskTrack->status = 2;
			$userTaskTrack->endTime = date("Y-m-d H:i:s");
			
			
			if($userTaskTrack->save()){
				return response()->json(['error' => true, 'message' => "Task Successfully Submitted."], 200);
			}else{
				return response()->json(['error' => true, 'message' => "Oops! Something went wrong in Submitting the  Task."], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
		
	}
	public function withdrawRequest(Request $request)
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'userId.required' => 'The User Id field is required.',
				'userId.integer' => 'The User Id field should be integer.',
				'amount.required' => 'The Amount field is required.',
				'amount.integer' => 'The Amount field should be integer.',
			];
			
			$validationRules = array(
				'userId' => 'required | integer',
				'amount' => 'required | integer'
			);
			
			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$userId = $input_data->userId;
			$amount = $input_data->amount;
			
			$users = Users::find($userId);
			
			if(empty($users)){
				return response()->json(['error' => true, 'message' => " User Not Found"], 200);
			}
			
			$userWallet = $users->walletBalance;
			
			if($amount > $walletBalance){
				return response()->json(['error' => true, 'message' => " You dont have sufficient Balance to Withdraw."], 200);
			}
			
			$withdrawRequest = New WithdrawRequests();
			
			$withdrawRequest->userId = $userId;
			$withdrawRequest->amount = $amount;
			
			if($withdrawRequest->save()){
				return response()->json(['error' => false, 'message' => "Withdraw Request Successfully Submitted."], 200);
			}else{
				return response()->json(['error' => true, 'message' => "Oops! Something went wrong in Submitting the Withdraw request."], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function shareProfile(Request $request)
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'userId.required' => 'The User Id field is required.',
				'userId.integer' => 'The User Id field should be integer.'
			];
			
			$validationRules = array(
				'userId' => 'required | integer'
			);
			
			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$userId = $input_data->userId;
			
			$usersData = Users::find($userId);
			
			if(empty($usersData)){
				return response()->json(['error' => true, 'message' => "Data Not Found. "], 200);
			}
			$baseUrl = URL::to('/');
			$profile_Url = $baseUrl."/profile/".$usersData->profile_key;
			
			return response()->json(['error' => false, 'message' => "Data Found", "Data" => $profile_Url], 200);
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function userProfileByKey(Request $request)
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'profile_key.required' => 'The User Id field is required.',
			];
			
			$validationRules = array(
				'profile_key' => 'required'
			);
			
			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$profile_key = $input_data->profile_key;
			
			$users = Users::where('profile_key' , $profile_key)->first();
			
			if(empty($users)){
				return response()->json(['error' => true, 'message' => "Data Not Found. "], 200);
			}
			//dd($users);
			
			$userData = array();
			
			$userData['fullName'] = $users->fullName;
			$userData['phoneNumber'] = $users->phoneNumber;
			$userData['emailAddress'] = $users->emailAddress;
			if($users->gender == 1){
				$userData['gender'] = "Female";
			} else {
				$userData['gender']= "Male";
			}
			$userData['dob'] = date("d M Y", strtotime($users->dob));
			$userData['about'] = $users->about;
			$userData['socialLinks'] = $users->socialLinks;
			$userData['language'] = $users->language;
			$userData['adharCardFront'] = $users->adharCardFront;
			$userData['adharCardBack'] = $users->adharCardBack;
			$userData['panCard'] = $users->panCard;
			//$userData['profilePic'] = $users->profilePic;
			
			$baseUrl = URL::to('/');
			$userData['profilePic'] = $baseUrl."/images/userProfilePic/".$users['profilePic'];
			
			if($users->isVerified == 1){
				$userData['isVerified'] = "Verified";
			}else{
				$userData['isVerified'] = "Not Verified";
			}
			if($users->status == 1){
				$userData['status'] = "Active";
			}else{
				$userData['status'] = "Inactive";
			}
			$userData['walletBalance'] = $users->walletBalance;
			
			return response()->json(['error' => false, 'message' => "Data Found", "Data"=> $userData], 200);
			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function deleteUserAcoount(Request $request)
	{
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'userId.required' => 'The User Id field is required.',
				'userId.required' => 'The User Id field should be integer.',
			];
			
			$validationRules = array(
				'userId' => 'required | integer'
			);
			
			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$users = Users::find($input_data->userId);
			
			if(empty($users)){
				return response()->json(['error' => true, 'message' => "Data Not Found"], 200);
			}
			
			$users->status = 0;
			if($users->save()){
				return response()->json(['error' => false, 'message' =>'User Successfully Deleted'], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'Oops! Something went wrong while Deleting.'], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function allUsersList(){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$usersList = Users::all();
			
			if(empty($usersList)){
				return response()->json(['error' => true, 'message' => "No Data Found"], 200);
			}
			
			
			
			$usersListArr = array();
			foreach($usersList as $users){
				$userData = array();
				
				$userData['userId'] = $users->id;
				$userData['fullName'] = $users->fullName;
				$userData['phoneNumber'] = $users->phoneNumber;
				$userData['emailAddress'] = $users->emailAddress;
				if($users->gender == 1){
					$userData['gender'] = "Female";
				} else {
					$userData['gender']= "Male";
				}
				if(!empty($users->dob)){
					$userData['dob'] = date("d M Y", strtotime($users->dob));
				}else{
					$userData['dob'] = NULL;
				}
				
				$userData['about'] = $users->about;
				
				 $socialLinks = [];
				if(!empty($users->socialLinks)){
					$socialArr = explode(",", $users->socialLinks);
					foreach($socialArr as $item){
						$socialLinks[] = $item;
					}
				}
				$userData['socialLinks'] = $socialLinks;
				
				$userData['language'] = $users->language;
				
				$baseUrl = URL::to('/');
				if(empty($users->profilePic)){
					$userData['profilePic'] = '';
				}else{
					$userData['profilePic'] = $baseUrl."/images/userProfilePic/".$users->profilePic;
				}
				
				if($users->isVerified == 1){
					$userData['isVerified'] = "Verified";
				}else{
					$userData['isVerified'] = "Not Verified";
				}
				if($users->status == 1){
					$userData['status'] = "Active";
				}else{
					$userData['status'] = "Inactive";
				}
				$userData['walletBalance'] = $users->walletBalance;
				$userData['profile_key'] = $users->profile_key;
				
 				$usersListArr[] = $userData;  
			}
			
			return response()->json(['error' => false, 'message' => " Data Found", 'savedTasks' => $usersListArr], 200);
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function checkUserCampaignsSaved(){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$userId = $input_data->userId;
			$campaignId = $input_data->campaignId;
			
			$alreadyExits = UserSavedTasks::where('userId', $userId)
											->where('campaignId', $campaignId)
											->first();
			if(empty($alreadyExits)){
				return response()->json(['error' => false, 'message' => "User hasn't saved this task.", "Data"=> "0"], 200);
			}else{
				return response()->json(['error' => false, 'message' => "User already saved this task.", "Data"=> "1"], 200);
			}
			
		}else
		{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function checkUserData(){
		$json = file_get_contents('php://input'); 
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$type = $input_data->type;
			
			switch ($type){
				case 1:  // user campaign saved check 
						$userId = $input_data->userId;
						$campaignId = $input_data->campaignId;
						
						$alreadyExits = UserSavedTasks::where('userId', $userId)
														->where('campaignId', $campaignId)
														->first();
						if(empty($alreadyExits)){
							return response()->json(['error' => false, 'message' => "User hasn't saved this task.", "Data"=> "0"], 200);
						}else{
							return response()->json(['error' => false, 'message' => "User already saved this task.", "Data"=> "1"], 200);
						}
						break;
				case 2:  // user started the task or not 
						$userId = $input_data->userId;
						$campaignId = $input_data->campaignId;
						
						$taskStarted = UserTaskTracking::where('userId', $userId)
												->where('campaignId', $campaignId)
												->first();
						if(!empty($taskStarted)){
							if($taskStarted->status == 0){
								return response()->json(['error' => false, 'message' => "User has already started this task."], 200);
							} elseif($taskStarted->status == 2){
								return response()->json(['error' => false, 'message' => "User already comleted this task."], 200);
							}
						}else{
							return response()->json(['error' => false, 'message' => "User hasn't started this task."], 200);
						}
						break;
				case 3: // check already Follows
						$userId = $input_data->userId;
						$otherUserId = $input_data->otherUserId;
						
						$user = Users::find($userId); // The authenticated user
						$otherUser = Users::find($otherUserId); // The user we want to check

						if ($user->isFollowing($otherUser)) {
							$msg = "You are already following";
							$alreadyFollowing = 1;
						} else {
							$msg = "You are not following";
							$alreadyFollowing = 0;
						}
						return response()->json(['error' => false, 'message' => $msg, "alreadyFollowing" => $alreadyFollowing], 200);
						break;
			}
		}else
		{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function userTasks(){
		$json = file_get_contents('php://input'); 
		$input_data = json_decode($json);
		$app_key = isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$userId = $input_data->userId;
			$type = $input_data->type;   // 1 - comleted , 2 - Failed
			
			$usersTasks = UserTaskTracking::join('campaigns', 'user_task_trackings.campaignId', '=', 'campaigns.id')
											->where('user_task_trackings.status', 2)
											->where('user_task_trackings.isApproved', $type)  
											->select('campaigns.*', 'user_task_trackings.rejectReason', 'user_task_trackings.isApproved')
											->get();
			//dd($usersTasks);
			
			if(empty($usersTasks)){
				return response()->json(['error' => false, 'message' => 'Data Not Available.'], 200);
			}else{
				$taskArray = array();
				$i = 1;
				foreach($usersTasks as $taskDetails){
					$arr = array();
					$arr['index'] = $i;
					$arr['campaignId'] = $taskDetails['id'];
					$arr['campaignName'] = $taskDetails['campaignName'];
					$baseUrl = URL::to('/');
					$campThumbArr = [];
					if(!empty($taskDetails->campaignThumnails)){
						$thumbnailsArr = explode(", ", $taskDetails->campaignThumnails);
						
						foreach($thumbnailsArr as $item){
							$campThumbArr[] = $baseUrl."/images/campaigns/".$item;
							
						}
					}
					$arr['campaignThumnails'] = $campThumbArr; 
					
					$arr['campDesc'] = $taskDetails['campDesc'];
					$arr['campaignAmount'] = $taskDetails['campaignAmount'];
					if($taskDetails['status'] == 1){
						$arr['campStatus'] = "Active";
					}else{
						$arr['campStatus'] = "Inactive";
					}
					$arr['campCategory'] = "";
					if($taskDetails['campCategory'] == 1){
						$arr['campCategory'] = "Trending";
					}else if($taskDetails['campCategory'] == 2){
						$arr['campCategory'] = "Latest";
					}else if($taskDetails['campCategory'] == 3){
						$arr['campCategory'] = "Recommended";
					} 
					
					$arr['genre'] = $taskDetails['genre'];
					$arr['membersList'] = $taskDetails['membersList'];
					$currentDate = date("Y-m-d");
					$date1 = Carbon::parse($currentDate);
					$date2 = Carbon::parse($taskDetails->endDate);

					// Calculate the difference in days
					$differenceInDays = $date1->diffInDays($date2);
					$arr['daysLeft'] = $differenceInDays. " Days Left!";
					$arr['selectedLanguage'] = $taskDetails->selectedLanguage;
					
					if($taskDetails->isApproved == 2){
						$arr['rejectReason'] = $taskDetails->rejectReason;
					}
					
					$taskArray[] = $arr;
					$i++;
					
				}
				if(empty($taskArray)){
					return response()->json(['error' => false, 'msg' => "Data Not Available", 'approvedTask' => $taskArray], 200);
				}else{
					return response()->json(['error' => false, 'msg' => "Data Found" , 'approvedTask' => $taskArray], 200);
				}
				
			}
			
			
			
		}else
		{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function getUserTaskTrackDetails(){
		$json = file_get_contents('php://input'); 
		$input_data = json_decode($json);
		$app_key = isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			
			
			$campaignId 	= trim($input_data->campaignId);
			$userId 		= trim($input_data->userId);
			
			$campaignDetails = Campaigns::find($campaignId);
			
			if(empty($campaignDetails)){
				return response()->json(['error' => true, 'message' => "Oops! Campaign Not Found."], 200);
			}
			
			$userDetails = Users::find($userId);
			
			if(empty($userDetails)){
				return response()->json(['error' => true, 'message' => "Oops! User Not Found."], 200);
			}
			
			$userTaskTrack = UserTaskTracking::where('campaignId', $campaignId)
											->where('userId', $userId)
											->get();
			
			if(empty($userTaskTrack)){
				return response()->json(['error' => true, 'message' => "User has not Started this Campaign."], 200);
			}
			
			
			$data = [];
			
			$taskId = $campaignDetails->taskId;
			
			$taskDetails = Tasks::find($taskId);
			
			if(empty($taskDetails)){
				return response()->json(['error' => true, 'message' => "Oops! Task Does Not Exists."], 200);
			}
			
			$isLink = $taskDetails->isLink;
			$isVideo = $taskDetails->isVideo;
			
			foreach($userTaskTrack as $item){
				if($item->status == 0){
						$data['status'] = "Pending";
					}else{
						$data['status'] = "Completed";
					}
					if($item->isApproved == 0){
						$data['isApproved'] = "Not Approved";
					}else if($item->isApproved == 1){
						$data['isApproved'] = "Approved";
					}else{
						$data['isApproved'] = "Rejected";
						$data['rejectReason'] = $item->rejectReason;
					}
				if($isLink == 1){
					$taskDetailsLinks = UserTaskFiles::where('taskTrackId', $item->id)->get();
					$instaLinks = [];
					$youtubeLinks = [];
					$otherLinks = [];
					foreach($taskDetailsLinks as $linkDetails){
						if($linkDetails->type == 4){
							$instaLinks[] = $linkDetails->link_text;
						}else if($linkDetails->type == 5){
							$youtubeLinks[] = $linkDetails->link_text;
						}else if($linkDetails->type == 6){
							$otherLinks[] = $linkDetails->link_text;
						}
					}
					$data['instagramLinks'] = $instaLinks;
					$data['youTubeLinks'] 	= $youtubeLinks;
					$data['otherLinks'] 	= $otherLinks;
				}
				
				if($isVideo == 1){
					$taskTrackId = $item->id;
					$userTaskDetails = UserTaskFiles::find($taskTrackId);
					if(!empty($userTaskDetails)){
						$data['filename'] = $userTaskDetails->filename;
					}else{
						$data['filename'] = Null;
					}
				}
			}
			
			
			return response()->json(['error' => true, 'message' => "Data Found", "Data" => $data], 200);
		}else
		{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function getSearchData(){
		$json = file_get_contents('php://input'); 
		$input_data = json_decode($json);
		$app_key = isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$searchTerm = $input_data->searchTerm;
			if(empty($searchTerm)){
				return response()->json(['error' => true, 'message' => "Search Field Cannot be Empty."], 200);
			}
			
			$searchdata = array();
			$userSearchData = $campaignSearchData =  array();
			$baseUrl = URL::to('/');
			
			$usersData = Users::where("fullName", "LIKE", "%{$searchTerm}%")->get();
			
			if(!empty($usersData)){
				foreach($usersData as $data){
					$arr = array();
					
					$arr['userId'] = $data->id;
					$arr['fullName'] = $data->fullName;
					if(!empty($data->profilePic)){
						$arr['profilePic'] = $baseUrl."/images/userProfilePic/".$data->profilePic; 
					}else{
						$arr['profilePic'] = NULL;
					}
					
					
					$userSearchData[] = $arr;
				}
			}
			
			$campaignData = Campaigns::where('campaignName', 'LIKE', "%{$searchTerm}%")->get();
			
			if(!empty($campaignData)){
				foreach($campaignData as $data){
					$arr = array();
					
					$arr['campaignId'] = $data->id;
					$arr['campaignTitle'] = $data->campaignName;
					$campThumbArr = [];
					if(!empty($data->campaignThumnails)){
						$thumbnailsArr = explode(", ", $data->campaignThumnails);
						foreach($thumbnailsArr as $item){
							$campThumbArr[] = $baseUrl."/images/campaigns/".$item;
						}
					}
					$arr['campaignThumnails'] = $campThumbArr; 
					
					$campaignSearchData[] = $arr;
				}
			}
			
			$searchdata['users'] = $userSearchData;
			$searchdata['campaigns'] = $campaignSearchData;
			
			return response()->json(['error' => true, 'message' => "Data Found", "Data" => $searchdata], 200);
			
			
		}else{
			return response()->json(['error' => true, 'message' => "Unauthorized Request"], 200);
		}
	}
}
