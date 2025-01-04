<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\AdvFundRequests;
use App\Models\Advertisers;

class FundController extends Controller
{
    //
	
	public function fundRequestStatusUpdate(){
		$json = file_get_contents('php://input');
		$input_data = json_decode($json);
		$app_key=isset($input_data->app_key)?trim($input_data->app_key):'';
		
		if($this->checkRequestAuth($app_key)!=0)
		{
			// fund request Id 
			// check that advertiser exits 
			// if approve then add amount to DB 
			// if rejected then update the status in adv_fund table. 
			
			$fundId = trim($input_data->fundRequestId);
			$status = trim($input_data->status);
			
			if($status == 2){
				$rejectReason = trim($input_data->rejectReason);
			}
			
			$fundRequestData = AdvFundRequests::find($fundId);
			
			
			if(empty($fundRequestData)){
				return response()->json(['error' => true, 'message' => "Oops! Fund Request Id Not Found."], 200);
			}
			
			$advertiserId = $fundRequestData->advertiserId;
			$amount = $fundRequestData->amount;
			
			
			$advData = Advertisers::find($advertiserId);
			if(empty($advData)){
				return response()->json(['error' => true, 'message' => "Oops! Advertiser Not Found"], 200);
			}
			
			if($status == 1){ // payment approved
				if($fundRequestData->status == 1){
					return response()->json(['error' => true, 'message' => "Request Already Approved."], 200);
				}
				
				$advWallet = $advData->walletBalance;
				
				$updatedBal = $advWallet + $amount;
				
				$advData->walletBalance = $updatedBal;
				
				if($advData->save()){
					$fundRequestData->status = 1;
					$fundRequestData->save();
					return response()->json(['error' => false, 'message' =>'Fund Request Successfully Approved'], 200);
				}else{
					return response()->json(['error' => true, 'message' =>'Oops! Something went wrong'], 200);
				}
			}else{ // payment rejected
				if($fundRequestData->status == 1){ // if status is changing from approve to reject
					// deduct amount from adv Wallet Balance 
					$advWallet = $advData->walletBalance;
				
					$updatedBal = $advWallet - $amount;
					
					$advData->walletBalance = $updatedBal;
					
					if($advData->save()){
						$fundRequestData->status = 2;
						$fundRequestData->rejectReason = $rejectReason;
						$fundRequestData->save();
						return response()->json(['error' => false, 'message' =>'Fund Request Successfully Rejected'], 200);
					}else{
						return response()->json(['error' => true, 'message' =>'Oops! Something went wrong'], 200);
					}
				}else{ // if sttaus is changing from pending to reject
					$fundRequestData->status = 2;
					$fundRequestData->rejectReason = $rejectReason;
					$fundRequestData->save();
					return response()->json(['error' => true, 'message' =>'Fund Request Successfully Rejected'], 200);
				}
			}
			
			
			
		}else{
			return response()->json(['error' => true, 'message' => " UnAuthorized request "], 200);
		}
	}
}
