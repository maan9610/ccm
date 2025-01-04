<?php

namespace App\Http\Controllers\advertiser;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

use App\Models\Advertisers;
use App\Models\AdvertiserCategory;
use App\Models\Languages;
use App\Models\Genres;
use App\Models\Creators;
use App\Models\CampaignLanguages;
use App\Models\CampaignGenres;
use App\Models\CampaignCreators;
use App\Models\Campaigns;
use App\Models\StatesList;
use App\Models\CityList;
use App\Models\Configs;
use App\Models\AdvFundRequests;
use App\Models\TaskRateLists;


class AdvertiserController extends Controller
{
    public function advertiserSignUp(Request $request){
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
				'password.required' => 'The Password field is required'
			];

			$validationRules = array(
				'fullName' => 'required | string',
				'phoneNumber' => 'required | integer | digits:10',
				'password' => 'required'
			); 

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}

			$fullName = $input_data->fullName;
			$phoneNumber = $input_data->phoneNumber;
		
			$password = $input_data->password;

			$advertiserExits = Advertisers::where('phoneNumber', $phoneNumber)->exists();
			
			if(!empty($advertiserExits)){
				return response()->json(['error' => true, 'message' =>' Advertiser Already exists'], 200);
			}
			
			$advertiser = New Advertisers();

			$advertiser->fullName = $fullName;
			$advertiser->phoneNumber = $phoneNumber;
			
			$advertiser->password = bcrypt($password);
			
			
			$otp = random_int(100000, 999999);
			$advertiser->otpValue = $otp;
			
			

			if($advertiser->save()){
				$advertiserId = $advertiser->id;
				sendMessage($phoneNumber, $otp);
				
				return response()->json(['error' => false, 'message' =>' Advertiser Registered', "advertiserId" => $advertiserId], 200);
				
			}else{
				return response()->json(['error' => true, 'message' =>'Oops! Something went wrong'], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
    }
	
	public function verifyOtp(Request $request){ 
	
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
				'advertiserId' => 'required | integer',
				'otp' => 'required | integer | digits:6',
			);

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$advertiserId = $input_data->advertiserId;
			$advertiserOtp = $input_data->otp;
			
			$advertiserData = Advertisers::find($advertiserId);
			
			if(!$advertiserData){
				 return response()->json(['error' => true, 'message' =>' Advertiser Not Found'], 200);
			}  
			
			$otp = $advertiserData->otpValue;
			
			if($advertiserOtp == $otp){
				$advertiserData->otpValue = NULL;
				$advertiserData->isOtpVerified = 1;
				$advertiserData->status = 1;
				
				$advertiserData->save();
				
				return response()->json(['error' => false, 'message' =>' OTP Successfully verified'], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'OTP Not Verified. Please check again.'], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function login(Request $request){
		
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key = isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'phoneNumber.required' => 'The username field is required.',
				'password.required' => 'The password field is required', 
			];
			
			$validationRules = array(
				'phoneNumber' => 'required',
				'password' => 'required'
			);

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}

			$phoneNumber = $input_data->phoneNumber;
			$password = $input_data->password;
			
			$advertiser = Advertisers::where('phoneNumber', $phoneNumber)
						->first();
			
			if ($advertiser && Hash::check($password, $advertiser->password)) {
				$advertiserId = $advertiser->id;
				if($advertiser->isOtpVerified == 0){
					return response()->json(['error' => false, 'message' =>' Advertiser Successfully Found', "advertiserId"=> $advertiserId, "otpVerified" => 0], 200);
				}else{
					$isVerified = 0;
					if($advertiser->isBusinessEmailVerified == 1 && !empty($advertiser->businessDoc1) && !empty($advertiser->businessDoc2) ){
						$isVerified = 2; // email submitted but document not verified. 
					}else if($advertiser->isBusinessEmailVerified == 1 && $advertiser->isBusinessDoc1Verified == 1 && $advertiser->isBusinessDoc2Verified == 1){
						$isVerified = 1; // email and document both are verified. 
					}
					$otpVerified = $advertiser->isOtpVerified;
					return response()->json(['error' => false, 'message' =>' Advertiser Successfully Found ', "advertiserId"=> $advertiserId, "isVerified" => $isVerified , "otpVerified" => 1 ], 200);
				}
				
			} else {
				return response()->json(['error' => true ,'message' => 'Invalid credentials or Advertiser not found'], 404);
			}

			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
    }
	
	public function getAdvertiserCategory(){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$advertiserCategory = AdvertiserCategory::all();
			
		
			if(empty($advertiserCategory)){
				return response()->json(['error' => true, 'message' => " Data Not Found"], 200);
			}
			
			$jsonData = array();
			
			foreach($advertiserCategory as $item){
				$arr['categoryId']  = $item['id'];
				$arr['categoryName']  = $item['categoryName'];
				$jsonData[] = $arr;
			}
			return response()->json(['error' => false, 'message' => "Data Found", "Data" => $jsonData], 200);
			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function selectAdvertiserCategory(Request $request){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'advertiserId.required' => 'The Advertiser Id field is required.',
				'advertiserId.integer' => 'The Advertiser Id field should be integer.',
				'categoryId.required' => 'The Advertiser Id field is required.',
				'advertiserId.integer' => 'The Advertiser Id field should be integer.',
			];
			
			$validationRules = array(
				'advertiserId' => 'required | integer',
				'categoryId' => 'required | integer'
			);

			$validator = Validator::make($request->all(), $validationRules, $messages);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$advertiserId = $input_data->advertiserId;
			$categoryId = $input_data->categoryId;
			
			$advertiserExits = Advertisers::find($advertiserId);
			if(empty($advertiserExits)){
				return response()->json(['error' => true, 'message' => "Advertiser Not Found"], 200);
			}
			
			$categoryExits = AdvertiserCategory::find($categoryId);
			if(empty($categoryExits)){
				return response()->json(['error' => true, 'message' => "Category Not Found"], 200);
			}
			
			$advertiserExits->categoryId = $categoryId;
			if($advertiserExits->save()){
				return response()->json(['error' => false, 'message' =>'Advertiser Category Successfully Updated'], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'Oops! Something went wrong'], 200);
			}			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function getAdvertiserDetails(){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		if($this->checkRequestAuth($app_key)!=0)
		{
			if(!isset($input_data->advertiserId)){
				return response()->json(['error' => true, 'message' => "Please Provide a Advertiser Id."], 200);
			}
			
			$advertiserId = $input_data->advertiserId;
			
			$advertiserDetails = Advertisers::find($advertiserId);
			
			if(empty($advertiserDetails)){
				return response()->json(['error' => true, 'message' => "Oops! Provided Advertiser Id not found. Please check again."], 200);
			}
			
			$data = array();
			
			$data['advertiserId'] = $advertiserDetails->id;
			$data['fullName'] = $advertiserDetails->fullName;
			$data['phoneNumber'] = $advertiserDetails->phoneNumber;
			$baseUrl = URL::to('/');
			if(!empty($advertiserDetails->profilePic)){
				$data['profilePic'] =  $baseUrl."/images/advertisers/".$advertiserDetails->profilePic;
			}else{
				$data['profilePic'] =  $baseUrl."/images/user_dummy_image.png";
			}
			
			$categoryDetails = AdvertiserCategory::select('categoryName')
												->where('id', $advertiserDetails->categoryId)
												->first();
												
			
			if(empty($categoryDetails)){
				$categoryName = "";
			}else{
				$categoryName = $categoryDetails->categoryName;
			}
			$data['category'] = $categoryName;
			$data['walletBalance'] = $advertiserDetails->walletBalance;
			if($advertiserDetails->status == 0){
				$data['status'] = "Inactive";
			}else{
				$data['status'] = "Active";
			}
			if($advertiserDetails->isBusinessEmailVerified == 1){
				$data['businessEmailVerified'] = True;
			}else{
				$data['businessEmailVerified'] = False;
			}
			
			$data['labelName'] = $advertiserDetails->labelName;
			$data['aboutLabel'] = $advertiserDetails->aboutLabel;
			$data['labelRelation'] = $advertiserDetails->labelRelation;
			$data['businessEmail'] = $advertiserDetails->businessEmail;
			$data['businessAddress'] = $advertiserDetails->businessAddress;
			$data['businessLocation'] = $advertiserDetails->businessLocation;
			$data['alternateMobile'] = $advertiserDetails->alternateMobile;
			if(!empty($advertiserDetails->businessDoc1)){
				$data['businessDoc1'] =  $baseUrl."/images/advertisers/businessDoc/".$advertiserDetails->businessDoc1;
			}else{
				$data['businessDoc1'] = NULL;
			}
			if(!empty($advertiserDetails->businessDoc2)){
				$data['businessDoc2'] =  $baseUrl."/images/advertisers/businessDoc/".$advertiserDetails->businessDoc2;
			}else{
				$data['businessDoc2'] = NULL;
			}
			$data['instaProfileLink'] = $advertiserDetails->instaProfileLink;
			$data['youtubeProfileLink'] = $advertiserDetails->youtubeProfileLink;
			
			
			return response()->json(['error' => false, "message" => "Data Found", 'data' => $data], 200);
			
		}
	}
	
	public function updateProfile(Request $request){
		//$json = file_get_contents('php://input');
		$json = json_encode($request->all());
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		if($this->checkRequestAuth($app_key)!=0)
		{
			if(!isset($input_data->advertiserId)){
				return response()->json(['error' => true, 'message' => "Please Provide a Advertiser Id."], 200);
			}
			
			$advertiserId = $input_data->advertiserId;
			
			$advertiserDetails = Advertisers::find($advertiserId);
			
			if(empty($advertiserDetails)){
				return response()->json(['error' => true, 'message' => "Oops! Provided Advertiser Id not found. Please check again."], 200);
			}
			
			$advertiserDetails->fullName = $input_data->fullName;
			$advertiserDetails->phoneNumber = $input_data->phoneNumber;
			
			
			if ($request->file('profilePic')) {
				$image = $request->file('profilePic');
				$path = $image->store('advertisers', 'public'); // Store in the 'public/images' directory
				$advertiserDetails->profilePic = basename($path);
			}
			
			$advertiserDetails->labelName = $input_data->labelName; 
			$advertiserDetails->aboutLabel = $input_data->aboutLabel;
			$advertiserDetails->labelRelation = $input_data->labelRelation;
			$advertiserDetails->businessEmail = $input_data->businessEmail;
			$advertiserDetails->businessAddress = $input_data->businessAddress;
			$advertiserDetails->businessLocation = $input_data->businessLocation;
			$advertiserDetails->alternateMobile = $input_data->alternateMobile;
			$advertiserDetails->instaProfileLink = $input_data->instaProfileLink; 
			$advertiserDetails->youtubeProfileLink = $input_data->youtubeProfileLink; 
			
			
			
			if ($request->file('businessDoc1')) {
				$BDimage1 = $request->file('businessDoc1');
				$BDpath1 = $BDimage1->store('advertisers/businessDoc', 'public'); // Store in the 'public/images' directory
				$advertiserDetails->businessDoc1 = basename($BDpath1);
			}
			if ($request->file('businessDoc2')) {
				$BDimage2 = $request->file('businessDoc2');
				$BDpath2 = $BDimage2->store('advertisers/businessDoc', 'public'); // Store in the 'public/images' directory
				$advertiserDetails->businessDoc2 = basename($BDpath2);
			}
			
			if($advertiserDetails->save()){
				return response()->json(['error' => false, 'message' =>'Advertiser Successfully Updated'], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'Oops! Something went wrong'], 200);
			}	
		}
	}
	
	public function getAllLanguages(Request $request){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$languagesData = Languages::all();
			
			if(empty($languagesData)){
				return response()->json(['error' => true, 'message' => " Data Not Found"], 200);
			}
			
			$jsonData = [];
			
			foreach($languagesData as $item){
				$arr['languageId']  = $item['id'];
				$arr['languageName']  = $item['languageName'];
				$jsonData[] = $arr;
			}
			return response()->json(['error' => false, 'message' => "Data Found", "Data" => $jsonData], 200);
			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function getAllGenres(Request $request){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$genresData = Genres::all();
			
			if(empty($genresData)){
				return response()->json(['error' => true, 'message' => " Data Not Found"], 200);
			}
			
			$jsonData = [];
			
			foreach($genresData as $item){
				$arr['genreId']  = $item['id'];
				$arr['genreName']  = $item['genreName'];
				$jsonData[] = $arr;
			}
			return response()->json(['error' => false, 'message' => "Data Found", "Data" => $jsonData], 200);
			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function getAllCreators(Request $request){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$creatorsData = Creators::all();
			
			if(empty($creatorsData)){
				return response()->json(['error' => true, 'message' => " Data Not Found"], 200);
			}
			
			$jsonData = [];
			
			foreach($creatorsData as $item){
				$arr['creatorId']  = $item['id'];
				$arr['creatorName']  = $item['creatorName'];
				$jsonData[] = $arr;
			}
			return response()->json(['error' => false, 'message' => "Data Found", "Data" => $jsonData], 200);
			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	
	
	public function createCampaign(Request $request){
		$json = json_encode($request->all());
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
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
				'selectedLanguage' => 'required',
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
			$campaignName = $request->campaignName;
			$advertiserId = $request->advertiserId;
			
			$campExits = Campaigns::where('campaignName', $campaignName)
									->where('advertiserId', $advertiserId)
									->exists();
								
			
			if(!empty($campExits)){
				return response()->json(['error' => true, 'message' => 'This Campaign is already exits'], 200);
			}
			
			$configsData = Configs::all();
			 
			foreach($configsData as $item){
				$totalERVDeduction = $item->totalERVDeduction;
			}
			
			$campaigns = New Campaigns();
			
			$advertiserCheck = Advertisers::find($advertiserId);
			if(empty($advertiserCheck)){
				return response()->json(['error' => true, 'message' => 'This Advertiser does not exits'], 200);
			}
			
			
			$campaigns->advertiserId = $advertiserId;
			$campaigns->planType = $request->planType;
			$campaigns->campaignName = $request->campaignName;
			$campaigns->url_key  = $campaigns->generateUniqueUrlKey($request->campaignName);
			
			$campThumbPaths = array();
			
			if ($request->hasfile('campaignsThumbnails')) {
				foreach ($request->file('campaignsThumbnails') as $image) {
					$path = $image->store('campaigns', 'public');
					$campThumbPaths[] = basename($path);
				}
			}
			$campaigns->campaignThumnails = implode(', ', $campThumbPaths); 
			$campaigns->campDesc = $request->campDesc;
			$campaigns->recordLabelName = $request->recordLabelName;
			$campaigns->membersList = $request->membersList;
			
			$campAssets = array();
			if ($request->hasfile('asset')) {
				foreach ($request->file('asset') as $image) {
					$assetPath = $image->store('campaigns', 'public'); 
					$campAssets[] = basename($assetPath);
				}
			}
			
			$campaigns->assets = implode(', ', $campAssets); 
			$campaigns->referenceLinks = $request->referenceLinks;
			$campaigns->isAssetDownloadable = $request->isAssetDownloadable;
			
			
			
			$campaigns->gender = $request->gender;
			$campaigns->ageLimits = $request->ageLimits;
			
			$campaigns->targetedLocations = $request->targetedLocations;
			$campaigns->taskId = $request->taskId;
			if($request['planType'] == 2){
				$campaigns->taskdestination = $request->taskdestination;
			}
			
			$campaigns->campaignAmount = $request->campAmount;
			$campaignAmount = $request->campAmount;
			$taxDeduction = ($campaignAmount * $totalERVDeduction)/100;
			$afterDeduction = $campaignAmount - $taxDeduction;
			$totalCreatorCount = countItemsInCommaSeparatedString($request->creatorCategory);
			$amountPerCategory = $afterDeduction/$totalCreatorCount;
			
			if (isset($request->creatorCategory)) {
				
				if (strpos($request->creatorCategory, ',') !== false) {
					$creatorArr = explode(",", $request->creatorCategory);
				}else{
					$creatorArr = str_split($request->creatorCategory);
				}
				$totalEstimatedTasks = 0;
				$taskRateListsData = TaskRateLists::where('taskId', $request->taskId)->first();
				
				foreach($creatorArr as $item){
					$avgValue = 0;
					$creatorsData = Creators::find($item);
					$creatorName = $creatorsData->creatorName;
					switch($creatorName){
						case "Micro" : $avgValue = $taskRateListsData->AvgValueMicro;
						break;
						case "Mini" : $avgValue = $taskRateListsData->AvgValueMini;
						break;
						case "Platinum" : $avgValue = $taskRateListsData->AvgValuePlatinum;
						break;
						case "Gold" : $avgValue = $taskRateListsData->AvgValueGold;
						break;
						case "Nano" : $avgValue = $taskRateListsData->AvgValueNano;
						break;
						case "Pico" : $avgValue = $taskRateListsData->AvgValuePico;
						break;
						case "Mega" : $avgValue = $taskRateListsData->AvgValueMega;
						break;
						case "Micro Start or Shine" : $avgValue = $taskRateListsData->AvgValueShine;
						break;
						case "Star" : $avgValue = $taskRateListsData->AvgValueStar;
						break;
						case "Celebrity" : $avgValue = $taskRateListsData->AvgValueCelebrity;
						break;
					}
					$totalEstimatedTasks += round($amountPerCategory / $avgValue) ;
				}
			} 
			
			$totalEstimatedTasks = ($totalEstimatedTasks - ($totalEstimatedTasks/2)). " To ".($totalEstimatedTasks + ($totalEstimatedTasks/2)). " Tasks ";
			
			/* echo "<br> After Deduction =".$afterDeduction;
			echo "<br> Total Creator Category Count =".$totalCreatorCount;
			echo "<br> Amount per Category =".$amountPerCategory;
			echo "Total Estimated Tasks =".$totalEstimatedTasks;
			die;
			 */
			
			$campaigns->estimateReturnvalues = $totalEstimatedTasks;
			$campaigns->startDate = $request->startDate;
			$campaigns->endDate = $request->endDate;
			
			if($campaigns->save()){
				$campaignId = $campaigns->id;

				if(isset($request->selectedLanguage)){
					
					if (strpos($request->selectedLanguage, ',') !== false) {
						$langArr = explode(",", $request->selectedLanguage);
						foreach($langArr as $item){
							$campLang = New CampaignLanguages();
							$campLang->campaignId = $campaignId;
							$campLang->languageId = $item;
							$campLang->save();
						}
					} else {
						$campLang = New CampaignLanguages();
						$campLang->campaignId = $campaignId;
						$campLang->languageId = $request->selectedLanguage;
						$campLang->save();
					}
					
					
				}
				
				
				if(isset($request->genre)){
					
					if (strpos($request->genre, ',') !== false) {
						$genreArr = explode(",", $request->genre);
						foreach($genreArr as $item){
							$campGenre = New CampaignGenres();
							
							$campGenre->campaignId = $campaignId;
							$campGenre->genreId = $item;
							$campGenre->save();
						}
					} else {
						$campGenre = New CampaignGenres();
						$campGenre->campaignId = $campaignId;
						$campGenre->genreId = $request->genre;
						$campGenre->save();
					}
				}
				
				if(isset($request->creatorCategory)){
					
					if (strpos($request->creatorCategory, ',') !== false) {
						$creatorArr = explode(",", $request->creatorCategory);
						foreach($creatorArr as $item){
							$campCreators = New CampaignCreators();
							$campCreators->campaignId = $campaignId;
							$campCreators->creatorId = $item;
							$campCreators->save();
						}
					} else {
						$campCreators = New CampaignCreators();
						$campCreators->campaignId = $campaignId;
						$campCreators->creatorId = $request->creatorCategory;
						$campCreators->save();
					}
					
					
				}
				
				
				return response()->json(['error' => false, 'message' => 'Campaign Successfully Created'], 200);
			}else{
				return response()->json(['error' => true, 'message' => 'Oops! Something went wrong in creating Campaign.'], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
		
		
		
	}
	
	public function updateCampaign(Request $request){
		
		$json = json_encode($request->all());
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$messages = [
				'campaignId.required' => 'The Campaign Id Field is required',
				'campaignId.integer' => 'The Campaign Id Field should be integer',
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
				'campaignId' =>  'required | integer',
				'campaignName' => 'required | string',
				'campDesc' => 'required | string',
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
			
			$campaignId = trim($input_data->campaignId);
			
			$campaignDetails = Campaigns::find($campaignId);
			
			if(empty($campaignDetails)){
				return response()->json(['error' => true, 'message' => "Oops! Campaign Details Not Found."], 200);
			}
			
			$campaignDetails->campaignName = trim($input_data->campaignName);
			$campaignDetails->url_key = $campaignDetails->generateUniqueUrlKey(trim($input_data->campaignName));
			$campaignDetails->campDesc = trim($request['campDesc']);
			$campaignDetails->recordLabelName = trim($request['recordLabelName']);
			$campaignDetails->membersList = trim($request['membersList']);
			
			$campThumbPaths = array();
		
			if ($request->hasfile('campaignsThumbnails')) {
				foreach ($request->file('campaignsThumbnails') as $image) {
					$path = $image->store('campaigns', 'public');
					$campThumbPaths[] = basename($path);
				}
				$campaigns->campaignThumnails = implode(', ', $campThumbPaths); 
			}
			
			$campAssets = array();
			if ($request->hasfile('asset')) {
				foreach ($request->file('asset') as $image) {
					$assetPath = $image->store('campaigns', 'public'); 
					$campAssets[] = basename($assetPath);
				}
				$campaigns->assets = implode(', ', $campAssets); 
			}
			
			$campaignDetails->referenceLinks = trim($request['referenceLinks']);
			$campaignDetails->isAssetDownloadable = trim($request['isAssetDownloadable']);
			$campaignDetails->gender = trim($request['gender']);
			$campaignDetails->ageLimits = trim($request['ageLimits']);
			$campaignDetails->targetedLocations = trim($request['targetedLocations']);
			$campaignDetails->taskId = trim($request['taskId']);
			if($campaignDetails->planType == 2){
				$campaignDetails->taskdestination = trim($input_data->taskdestination);
			}
			$campaignDetails->campaignAmount = trim($input_data->campAmount);
			$campaignDetails->estimateReturnvalues = trim($input_data->estimatedReturnValues);
			$campaignDetails->startDate = $input_data->startDate;
			$campaignDetails->endDate = $input_data->endDate;
			$campaignDetails->status = trim($input_data->status);
			$campaignDetails->campCategory = trim($input_data->campCategory);
			
			if(isset($request['selectedLang'])){
				
				CampaignLanguages::where('campaignId', $campaignId)->delete();
				
				
				if (strpos($request['selectedLang'], ',') !== false) {
					$langArr = explode(",", $request['selectedLang']);
					foreach($langArr as $item){
						$campLang = New CampaignLanguages();
						
						$campLang->campaignId = $campaignId;
						$campLang->languageId = $item;
						
						$campLang->save();
					}
				} else {
					$campLang = New CampaignLanguages();
					
					$campLang->campaignId = $campaignId;
					$campLang->languageId = $request['selectedLang'];
					
					$campLang->save();
				}
				
				
			}
			
			
			if(isset($request->genre)){
				CampaignGenres::where('campaignId', $campaignId)->delete();
				
				
				if (strpos($request->genre, ',') !== false) {
					
					$genreArr = explode(",", $request->genre);
					foreach($genreArr as $item){
						$campGenre = New CampaignGenres();
						
						$campGenre->campaignId = $campaignId;
						$campGenre->genreId = $item;
						
						$campGenre->save();
					}
				} else {
					$campGenre = New CampaignGenres();
					
					$campGenre->campaignId = $campaignId;
					$campGenre->genreId = $request->genre;
					$campGenre->save();
				}
				
				
			}
			
			if(isset($request['creatorCategory'])){
				CampaignCreators::where('campaignId', $campaignId)->delete();
				
				
				if (strpos($request['creatorCategory'], ',') !== false) {
					$creatorArr = explode(",", $request['creatorCategory']);
					foreach($creatorArr as $item){
						$campCreators = New CampaignCreators();
						
						$campCreators->campaignId = $campaignId;
						$campCreators->creatorId = $item;
						$campCreators->save();
					}
				} else {
					$campCreators = New CampaignCreators();
					
					$campCreators->campaignId = $campaignId;
					$campCreators->creatorId = $request['creatorCategory'];
					
					$campCreators->save();
				}
				
				
			}
			if($campaignDetails->save()){
				return response()->json(['error' => false, 'message' =>'Campaign Successfully Updated.'], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'Oops! Something went wrong'], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function getDashboardData(Request $request){
		// $json = json_encode($request->all());
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$advertiserId = trim($input_data->advertiserId);
			
			$advertiserDetails = Advertisers::find($advertiserId);
			if(empty($advertiserDetails)){
				return response()->json(['error' => true, 'message' => "Oops! Advertiser Not Found."], 200);
			}
			
			$data = array();
			
			$campaignsCount = Campaigns::where('advertiserId', $advertiserId)->count();
			
			$completeCampaigns = Campaigns::where('advertiserId', $advertiserId)
										->where('endDate' , '<' , Carbon::today())
										->where('isApproved', 1)
										->count();
										
			$runningCampaigns = Campaigns::where('advertiserId', $advertiserId)
										->where('startDate', '<=', Carbon::today())
										->where('endDate' , '>=' , Carbon::today())
										->where('isApproved', 1)
										->count();
			
			$recentCamps = [];
			
			
			
			// last one month campaigns
			$recentCampaigns = Campaigns::where('advertiserId', $advertiserId)
										->where('startDate', '>', Carbon::now()->subMonth()->toDateString())
										->where('status', 1)
										->get();
			
			if(!empty($recentCampaigns)){
				$baseUrl = URL::to('/');
				foreach($recentCampaigns as $campDetails){
					$arr = [];
					
					$arr['campaignName'] = $campDetails->campaignName;
					
					
					$campThumbArr = [];
					if(!empty($campDetails->campaignThumnails)){
						$thumbnailsArr = explode(", ", $campDetails->campaignThumnails);
						
						foreach($thumbnailsArr as $item){
							$campThumbArr[] = $baseUrl."/images/campaigns/".$item;
						}
					}
					$arr['campaignThumbnails'] = $campThumbArr;
					$campLanguages = [];
					
					$campLangs = CampaignLanguages::join('languages', 'campaign_languages.languageId', '=', 'languages.id')
												->where('campaign_languages.campaignId', $campDetails->id)
												->select('languages.languageName')
												->get();
					
					if(!empty($campLangs)){
						foreach($campLangs as $langDetails){
							$campLanguages[] = $langDetails->languageName;
						}
					}
					
					$arr['campLanguages'] = $campLanguages;
					if($campDetails->campStatus == 1){
						$arr['campStatus'] = "Running";
					}else if($campDetails->campStatus == 2){
						$arr['campStatus'] = "Completed";
					}else if($campDetails->campStatus == 3){
						$arr['campStatus'] = "Paused";
					}else{
						$arr['campStatus'] = "Pending";
					}
					if($campDetails->status == 1){
						$arr['status'] = "Active";
					}else{
						$arr['status'] = "Inactive";
					}
					if($campDetails->isApproved == 1){
						$arr['approved'] = "Approved";
					}else{
						$arr['approved'] = "Not Approved";
					}
					
					$currentDate = date("Y-m-d");
					$date1 = Carbon::parse($currentDate);
					$date2 = Carbon::parse($campDetails->endDate);

					// Calculate the difference in days
					$differenceInDays = $date1->diffInDays($date2);
					$arr['daysLeft'] = $differenceInDays. " Days Left!";
					
					$recentCamps[] = $arr;
				}
			}
			
			
			
			$data['walletBalance'] = $advertiserDetails->walletBalance;
			$data['campaignCount'] = $campaignsCount;
			$data['completedCampaigns'] = $completeCampaigns;
			$data['runningCampaigns'] = $runningCampaigns;
			$data['recentCampaigns'] = $recentCamps;
			
			
			return response()->json(['error' => false, 'message' =>'Data Found.', 'data' => $data], 200);
		}
		else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function getCampaignLists(Request $request){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$validationRules = array(
				'type' =>  'required | integer',
				'advertiserId' => 'required | integer',
			);
			
			$validator = Validator::make($request->all(), $validationRules);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$type = trim($input_data->type);
			$advertiserId = trim($input_data->advertiserId);
			
			if($type == 1){ // total campaigns
				$campaignsData = Campaigns::where('advertiserId', $advertiserId)
											->get();
			}else if($type == 2){ // running campaigns
				$campaignsData = Campaigns::where('advertiserId', $advertiserId)
										->where('startDate', '<=', Carbon::today())
										->where('endDate' , '>=' , Carbon::today())
										->where('isApproved', 1)
										->get();
			} else if($type == 3){ // completed campaigns
				$campaignsData = Campaigns::where('advertiserId', $advertiserId)
										->where('endDate' , '<' , Carbon::today())
										->where('isApproved', 1)
										->get();
			}
			
			if(empty($campaignsData)){
				return response()->json(['error' => true, 'message' => "Oops! Data Not Found."], 200);
			}
			$baseUrl = URL::to('/');
			$data = array();
			foreach($campaignsData as $campDetails){
				
				$arr = array();
				
				$arr['campaignId'] = $campDetails->id;
				$arr['campaignName'] = $campDetails->campaignName;
				$arr['url_key'] = $campDetails->url_key;
				
				$campThumbArr = [];
				if(!empty($campDetails->campaignThumnails)){
					$thumbnailsArr = explode(", ", $campDetails->campaignThumnails);
					
					foreach($thumbnailsArr as $item){
						$campThumbArr[] = $baseUrl."/images/campaigns/".$item;
					}
				}
				$arr['campaignThumbnails'] = $campThumbArr;
				
				$arr['campDesc'] = $campDetails->campDesc;
				$arr['recordLabelName'] = $campDetails->recordLabelName;
				$arr['membersList'] = $campDetails->membersList;
				
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
				
				$arr['referenceLinks'] = $campDetails->referenceLinks;
				$arr['isAssetDownloadable'] = $campDetails->isAssetDownloadable;
				$campLanguages = [];
					
				$campLangs = CampaignLanguages::join('languages', 'campaign_languages.languageId', '=', 'languages.id')
											->where('campaign_languages.campaignId', $campDetails->id)
											->select('languages.languageName')
											->get();
				
				if(!empty($campLangs)){
					foreach($campLangs as $langDetails){
						$campLanguages[] = $langDetails->languageName;
					}
				}
				$arr['campLanguages'] = $campLanguages;
				
				$campGenres = [];
					
				$campaignGenres = campaignGenres::join('genres', 'campaign_genres.genreId', '=', 'genres.id')
											->where('campaign_genres.campaignId', $campDetails->id)
											->select('genres.genreName')
											->get();
				
				if(!empty($campaignGenres)){
					foreach($campaignGenres as $genreDetails){
						$campGenres[] = $genreDetails->genreName;
					}
				}
				$arr['campGenres'] = $campGenres;
				
				$campCreators = [];
					
				$campaignCreators = CampaignCreators::join('creators', 'campaign_creators.creatorId', '=', 'creators.id')
											->where('campaign_creators.campaignId', $campDetails->id)
											->select('creators.creatorName')
											->get();
				
				if(!empty($campaignCreators)){
					foreach($campaignCreators as $creatorDetails){
						$campCreators[] = $creatorDetails->creatorName;
					}
				}
				$arr['campCreators'] = $campCreators;
				$arr['targetedLocations'] = $campDetails->targetedLocations;
				$arr['campaignAmount'] = $campDetails->campaignAmount;
				$arr['estimateReturnvalues'] = $campDetails->estimateReturnvalues;
				$arr['startDate'] = date("d M Y", strtotime($campDetails->startDate));
				$arr['endDate'] = date("d M Y", strtotime($campDetails->endDate));
				
				$currentDate = date("Y-m-d");
				$date1 = Carbon::parse($currentDate);
				$date2 = Carbon::parse($campDetails->endDate);

				// Calculate the difference in days
				$differenceInDays = $date1->diffInDays($date2);
				$arr['daysLeft'] = $differenceInDays. " Days Left!";
				
				$arr['campCategory'] = NULL;
				if($campDetails->campCategory == 1){
					$arr['campCategory'] = "Trending";
				}else if($campDetails->campCategory == 2){
					$arr['campCategory'] = "Latest";
				} else if($campDetails->campCategory == 3){
					$arr['campCategory'] = "Recommended";
				}
				
				if($campDetails->campStatus == 1){
					$arr['campCategory'] = "Running";
				}else if($campDetails->campStatus == 2){
					$arr['campCategory'] = "Completed";
				} else if($campDetails->campStatus == 3){
					$arr['campCategory'] = "Paused";
				}else{
					$arr['campCategory'] = "Pending";
				}
				
				$arr['approvalStatus'] = NULL;
				if($campDetails->isApproved == 0){
					$arr['approvalStatus'] = "Not Approved";
				}else {
					$arr['approvalStatus'] = "Approved";
				} 
				
				$data[] = $arr;
				
			}
			
			return response()->json(['error' => true, 'message' => "Data Found", "Data" => $data], 200);
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function deleteCampaign(Request $request){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$validationRules = array(
				'advertiserId' => 'required | integer',
				'campaignId' => 'required | integer'
			);
			
			$validator = Validator::make($request->all(), $validationRules);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$advertiserId = trim($input_data->advertiserId);
			$campaignId = trim($input_data->campaignId);
			
			
			$campDetails = Campaigns::find($campaignId);
			
			if(empty($campDetails)){
				return response()->json(['error' => true, 'message' => "Data Not Found"], 400);
			}
			
			if($campDetails->advertiserId != $advertiserId){
				return response()->json(['error' => true, 'message' => "Oops! Campaign Advertiser Not Matched."], 400);
			}
			
			$campDetails->status = 0;
			
			if($campDetails->save()){
				return response()->json(['error' => false, 'message' =>'Campaign Successfully Deleted.'], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'Oops! Something went wrong'], 200);
			}
			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function pauseCampaign(Request $request){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$validationRules = array(
				'advertiserId' => 'required | integer',
				'campaignId' => 'required | integer',
				'campStatus' => 'required | integer'
			);
			
			$validator = Validator::make($request->all(), $validationRules);

			if ($validator->fails()) {
				return response()->json(['error' => $validator->errors()->first()], 400);
			}
			
			$advertiserId = trim($input_data->advertiserId);
			$campaignId = trim($input_data->campaignId);
			$campStatus = trim($input_data->campStatus);
			
			
			$campDetails = Campaigns::find($campaignId);
			
			if(empty($campDetails)){
				return response()->json(['error' => true, 'message' => "Data Not Found"], 400);
			}
			
			if($campDetails->advertiserId != $advertiserId){
				return response()->json(['error' => true, 'message' => "Oops! Campaign Advertiser Not Matched."], 400);
			}
			
			if($campStatus == 3){
				$msgContent = "Campaign Successfully Paused.";
			}else if($campStatus == 1){
				$msgContent = "Campaign Started Again.";
			} else if($campStatus == 2){
				$msgContent = "Campaign Successfully Competed.";
			}
			$campDetails->campStatus = $campStatus;
			
			if($campDetails->save()){
				return response()->json(['error' => false, 'message' => $msgContent], 200);
			}else{
				return response()->json(['error' => true, 'message' =>'Oops! Something went wrong'], 200);
			}
			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function sendBusinessEmail($name, $email, $verificationUrl){
    $toEmail = $email; // Replace with recipient's email
    $data = [
        'name' => $name, // Dynamic data
        'verificationUrl' => $verificationUrl,
    ];

    // Send email using closure-based view
    Mail::send('emails.businessemail', $data, function ($message) use ($toEmail) {
        $message->to($toEmail)
                ->subject('Verify Your Business Email');
        $message->from('info@contentcrownmedia.in', 'Content Crown Media');
    });

    return response()->json(['message' => 'Email sent successfully!']);
}
	
	public function sendBusinessEmailVerifyMail(){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$advertiserEmail = trim($input_data->advertiserEmail);
			$advertiserId = trim($input_data->advertiserId);
			
			$advDetails = Advertisers::find($advertiserId);
			
			if(empty($advDetails)){
				return response()->json(['error' => true, 'message' => "Advertiser Id Not Found."], 200);
			}
			if($advDetails->isBusinessEmailVerified == 1){
				return response()->json(['error' => true, 'message' => "Business Email Already Verified."], 200);
			}
			$advName = $advDetails->fullName;
			
			$verification_token = Str::random(64);
			$advDetails->verification_token = $verification_token;
			$advDetails->save();
			
			$verificationUrl = route('business.verify', ['token' => $verification_token]);
			
			$this->sendBusinessEmail($advName, $advertiserEmail, $verificationUrl);
			
			return response()->json(['error' => false, 'message' => "Business Email Sent Successfully."], 200);
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function getAllStates(){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$stateData = StatesList::all();
			
			if(empty($stateData)){
				return response()->json(['error' => true, 'message' => "Data Not Found"], 200);
			}
			
			$jsonData = [];
			
			foreach($stateData as $item){
				$arr['stateId']  = $item['id'];
				$arr['stateName']  = $item['state_name'];
				$jsonData[] = $arr;
			}
			return response()->json(['error' => false, 'message' => "Data Found", "Data" => $jsonData], 200);
			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function getAllStateCities(){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			$stateId = trim($input_data->stateId);
			
			$stateData = StatesList::find($stateId);
			if(empty($stateData)){
				return response()->json(['error' => true, 'message' => "State Not Found"], 200);
			}
			
			$cityData = CityList::where('stateId', $stateId)->get();
			if(empty($cityData)){
				return response()->json(['error' => true, 'message' => "Data Not Found"], 200);
			}
			
			
			$jsonData = [];
			
			foreach($cityData as $item){
				$arr['cityId']  = $item['id'];
				$arr['stateName']  = $item['cityName'];
				$jsonData[] = $arr;
			}
			return response()->json(['error' => false, 'message' => "Data Found", "Data" => $jsonData], 200);
			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function resendOtp(){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			// take advertiser phone Number
			// check that advertiser exits or not 
			// send the otp to that advertiser phone number 
			// update the database
			
			$phoneNumber = trim($input_data->phoneNumber);
			
			$advertiser = Advertisers::where('phoneNumber', $phoneNumber)->first();
			
			if(empty($advertiser)){
				return response()->json(['error' => true, 'message' =>'Oops Advertiser does not exists'], 200);
			}
			
			
			if($advertiser->isOtpVerified == 1){
				return response()->json(['error' => false, 'message' =>' Advertiser Already Verified']);
			}else{
				$otp = random_int(100000, 999999);
				$advertiser->otpValue = $otp;
				
				if($advertiser->save()){
					$advertiserId = $advertiser->id;
					sendMessage($phoneNumber, $otp);
					
					return response()->json(['error' => false, 'message' =>'OTP Successfully resend to Advertiser Phone Number', "advertiserId" => $advertiserId], 200);
					
				}else{
					return response()->json(['error' => true, 'message' =>'Oops! Something went wrong'], 200);
				}
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	public function addFundRequest(){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			// advertiser Id 
			// Amount
			// check advertiser id exits 
			// check advertiser amount should be greater than miniumum amount
			
			$advertiserId = trim($input_data->advertiserId);
			$amount = trim($input_data->amount);
			
			if(empty($advertiserId) || empty($amount)){
				return response()->json(['error' => true, 'message' => "Required Field Missing."], 200);
			}
			
			$advertiserData = Advertisers::find($advertiserId);
			if(empty($advertiserData)){
				return response()->json(['error' => true, 'message' => "Oops! Advertiser Not Found."], 200);
			}
			
			$configData = Configs::all();
			
			foreach($configData as $item){
				$minAddfund = $item->minAddFund;
			}
			
			if($amount < $minAddfund){
				return response()->json(['error' => true, 'message' => "Amount should be greater than ".$minAddfund], 200);
			}
			
			$adv_fund = New AdvFundRequests();
			
			$adv_fund->advertiserId = $advertiserId;
			$adv_fund->amount = $amount;
			
			if($adv_fund->save()){
				return response()->json(['error' => false, 'message' =>'Adding Fund Request Successfully Submitted.'],  200);
				
			}else{
				return response()->json(['error' => true, 'message' =>'Oops! Something went wrong'], 200);
			}
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
	
	
}
