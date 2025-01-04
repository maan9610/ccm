<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
	
	public function checkRequestAuth($appKey)
	{
		
        $validAppKey = config('app.api_key');
	    

        if ($appKey !== $validAppKey) {
           // Log::warning('Unauthorized access attempt', ['ip' => $request->ip()]);
            return 0;
        }else{
			return 1;
		}
	}
}
