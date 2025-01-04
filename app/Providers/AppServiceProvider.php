<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $configs = DB::table('configs')->get();
		//dd($configs);
		foreach ($configs as $key => $value) {
			$key = strtoupper($key); // Example: Convert 'app_name' to 'APP_NAME'
           // $value = $value;

            // Define the constant if not already defined
            if (!defined($key)) {
                define($key, $value);
            }
		}
    }
}
