<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\admin\AdminController; 
use App\Http\Controllers\SuperAdmin\TaskController;
use App\Http\Controllers\SuperAdmin\AdminUserController; 
use App\Http\Controllers\SuperAdmin\FundController; 
use App\Http\Controllers\FollowController;
use App\Http\Controllers\advertiser\AdvertiserController;
use App\Http\Controllers\YoutubeController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'signUp']);
Route::post('/update-profile', [UserController::class, 'updateProfile']);
Route::post('/verify-otp', [UserController::class, 'verifyOtp']);
Route::get('/get-approved-task', [UserController::class, 'getApprovedTask']); 
Route::post('/get-user-details', [UserController::class, 'getUserDetails']); 
Route::post('/save-task', [UserController::class, 'saveTask']);
Route::post('/delete-save-task', [UserController::class, 'deleteSaveTask']);
Route::post('/get-user-save-tasks', [UserController::class, 'getUserSaveTasks']);
Route::post('/get-Campaigns-details', [UserController::class, 'getCampaignDetails']);
Route::post('/start-task', [UserController::class, 'startTask']); 
Route::post('/submit-task', [UserController::class, 'submitTask']); 
Route::post('/withdraw-request', [UserController::class, 'withdrawRequest']);
Route::post('/get-all-users-list', [UserController::class, 'allUsersList']);
Route::post('/follow', [FollowController::class, 'follow']); 
Route::post('/unfollow', [FollowController::class, 'unfollow']);
Route::post('/followers', [FollowController::class, 'followers']);
Route::post('/followees', [FollowController::class, 'followees']);
Route::post('/share-profile', [UserController::class, 'shareProfile']);
Route::post('/get-user-profile-key', [UserController::class, 'userProfileByKey']);
Route::post('/delete-user-account', [UserController::class, 'deleteUserAcoount']);

Route::post('/check-user-campaigns-saved', [UserController::class, 'checkUserCampaignsSaved']);
Route::post('/check-user-data', [UserController::class, 'checkUserData']); 
Route::post('/user-tasks', [UserController::class, 'userTasks']); 
Route::post('/get-user-started-task-details', [UserController::class, 'getUserTaskTrackDetails']); 
Route::post('/search', [UserController::class, 'getSearchData']);  


Route::prefix('admin')->group(function () {
    Route::post('task-create', [AdminController::class, 'createTask']); 
	Route::post('task-update', [AdminController::class, 'updateTask']);
	Route::post('/withdraw-request-change-status', [AdminController::class, 'withdrawRequestChangeStatus']);	
	Route::post('/get-all-tasks', [AdminController::class, 'getAllTasks']);
	// Add more routes as needed
});

Route::prefix('advertiser')->group(function () {
    Route::post('/register', [AdvertiserController::class, 'advertiserSignUp']); 
	Route::post('/verify-otp', [AdvertiserController::class, 'verifyOtp']);
	Route::post('/login', [AdvertiserController::class, 'login']);
	Route::post('/resend-otp', [AdvertiserController::class, 'resendOtp']);
	
	Route::post('/get-advertiser-category', [AdvertiserController::class, 'getAdvertiserCategory']);
	Route::post('/select-advertiser-category', [AdvertiserController::class, 'selectAdvertiserCategory']);
	
	Route::post('/update-profile', [AdvertiserController::class, 'updateProfile']);
	Route::post('/get-advertiser-details', [AdvertiserController::class, 'getAdvertiserDetails']);
	Route::post('/create-campaign', [AdvertiserController::class, 'createCampaign']);
	Route::post('/update-campaign', [AdvertiserController::class, 'updateCampaign']);
	Route::post('/get-all-languages', [AdvertiserController::class, 'getAllLanguages']);
	Route::post('/get-all-genres', [AdvertiserController::class, 'getAllGenres']);
	Route::post('/get-all-creators', [AdvertiserController::class, 'getAllCreators']);
	Route::post('/get-dashboard-data', [AdvertiserController::class, 'getDashboardData']);
	Route::post('/get-campaign-lists', [AdvertiserController::class, 'getCampaignLists']);
	Route::post('/delete-campaign', [AdvertiserController::class, 'deleteCampaign']);
	Route::post('/pause-campaign', [AdvertiserController::class, 'pauseCampaign']);
	Route::post('/send-business-email-verify-mail', [AdvertiserController::class, 'sendBusinessEmailVerifyMail']); 
	Route::post('/get-all-states', [AdvertiserController::class, 'getAllStates']); 
	Route::post('/get-all-state-city', [AdvertiserController::class, 'getAllStateCities']); 
	Route::post('/add-fund-request', [AdvertiserController::class, 'addFundRequest']); 
	
    // Add more routes as needed
});

Route::prefix('SuperAdmin')->group(function () {
    Route::post('task-create', [TaskController::class, 'createTask']); 
	
	Route::post('task-update', [AdminController::class, 'updateTask']);
	
	Route::post('/create-campaign', [AdminController::class, 'createCampaign']);

	Route::post('/withdraw-request-change-status', [AdminController::class, 'withdrawRequestChangeStatus']);	
	Route::post('/user-campaign-approval', [TaskController::class, 'userCampaignApproval']); 
	Route::post('/campaign-approve-reject', [TaskController::class, 'campaignApproveReject']);
	Route::post('/user-documents-status', [AdminUserController::class, 'userDocumentsStatus']);	
	Route::post('/fund-request-status-update', [FundController::class, 'fundRequestStatusUpdate']);	
    // Add more routes as needed
});

Route::get('/youtube/channel/{id}', [YoutubeController::class, 'getChannel']);
Route::get('/youtube/channel/name/{id}', [YoutubeController::class, 'getChannelDataByName']);

Route::get('/youtube/video/{id}', [YoutubeController::class, 'getVideo']);
