<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class InstagramController extends Controller
{
    //
	public function redirect()
    {
        $query = http_build_query([
            'client_id' => env('INSTAGRAM_APP_ID'),
            'redirect_uri' => env('INSTAGRAM_REDIRECT_URI'),
            'scope' => 'user_profile,user_media',
            'response_type' => 'code',
        ]);
		$instagramAuthUrl =  "https://api.instagram.com/oauth/authorize?" . $query;
		Log::info('Redirecting to Instagram OAuth URL: ' . $instagramAuthUrl);
        return redirect($instagramAuthUrl);
    }
	public function callback(Request $request)
    {
		 $response = Http::asForm()->post('https://api.instagram.com/oauth/access_token', [
            'client_id' => env('INSTAGRAM_APP_ID'),
            'client_secret' => env('INSTAGRAM_APP_SECRET'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => env('INSTAGRAM_REDIRECT_URI'),
            'code' => $request->code,
        ]);
		
		
       $accessToken = $response->json()['access_token'];
	   
	   if ($response->successful() && isset($response->json()['access_token'])) {
            $accessToken = $response->json()['access_token'];
            $userId = $response->json()['user_id'];

            // Store the access token and user ID in the session or database
            session(['instagram_access_token' => $accessToken]);
            session(['instagram_user_id' => $userId]);

            // Redirect to a page that shows user media or confirms successful connection
            return redirect('/instagram/media')->with('success', 'Connected to Instagram!');
        } else {
            // Handle error response
            $error = $response->json()['error_message'] ?? 'Unknown error';
            return redirect('/')->with('error', 'Failed to connect to Instagram: ' . $error);
        }
    }
	
	 public function getUserMedia()
    {
        $accessToken = session('instagram_access_token'); // or however you stored the token

        $response = Http::get('https://graph.instagram.com/me', [
            'fields' => 'id,username,account_type,media_count,followers_count',
            'access_token' => $accessToken,
        ]);

        $media = $response->json();
		
		dd($media);

        return view('instagram.media', compact('media'));
    }
	public function deauthorize(Request $request)
    {
        // Validate the request
        $data = $request->validate([
            'signed_request' => 'required|string',
        ]);

        // Parse the signed request
        list($encodedSig, $payload) = explode('.', $data['signed_request'], 2);

        // Decode the data
        $sig = base64_decode(strtr($encodedSig, '-_', '+/'));
        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        // Validate the signature
        $expectedSig = hash_hmac('sha256', $payload, env('INSTAGRAM_APP_SECRET'), true);
        if ($sig !== $expectedSig) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle deauthorization logic here
        // For example, delete or mark the user's data as deauthorized
        $userId = $data['user_id'];
        // Your logic to handle deauthorization (e.g., delete user data from database)

        return response()->json(['success' => true]);
    }
	public function dataDeletion(Request $request)
    {
        // Validate the request
        $data = $request->validate([
            'signed_request' => 'required|string',
        ]);

        // Parse the signed request
        list($encodedSig, $payload) = explode('.', $data['signed_request'], 2);

        // Decode the data
        $sig = base64_decode(strtr($encodedSig, '-_', '+/'));
        $data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        // Validate the signature
        $expectedSig = hash_hmac('sha256', $payload, env('INSTAGRAM_APP_SECRET'), true);
        if ($sig !== $expectedSig) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // Handle data deletion logic here
        $userId = $data['user_id'];
        // Your logic to delete user data from the database

        // Log the data deletion request for debugging purposes
        Log::info('Data deletion request for user_id: ' . $userId);

        // Return a JSON response confirming the deletion
        return response()->json([
            'url' => 'https://your-app-url.com/confirmation', // URL where the user can confirm the deletion
            'confirmation_code' => 'unique-confirmation-code', // Unique code confirming the deletion
        ]);
    }

}
