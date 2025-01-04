<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Users;
use Illuminate\Support\Str;


class GenerateUserProfileKeys extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:generate-profile-keys';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate profile keys for all users';

    /**
     * Execute the console command.
     *
     * @return int
     */
	 public function __construct()
    {
        parent::__construct();
    }
	
    public function handle()
    {
        $users = Users::whereNull('profile_key')->get();

        foreach ($users as $user) {
            do {
                $profileKey = Str::uuid()->toString();
            } while (Users::where('profile_key', $profileKey)->exists());

            $user->profile_key = $profileKey;
            $user->save();
        }

        $this->info('Profile keys generated for all users.');
	}
	
}
