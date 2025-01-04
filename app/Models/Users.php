<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class Users extends Model
{
    use HasFactory;
	use Notifiable;
	
	public function userSavedTasks()
    {
        return $this->hasMany(UserSavedTasks::class);
    }
	
	public function follows()
    {
        return $this->belongsToMany(Users::class, 'follows', 'follower_id', 'followee_id')
                    ->withTimestamps();
    }

    /**
     * The users that follow this user.
     */
    public function followers()
    {
        return $this->belongsToMany(Users::class, 'follows', 'followee_id', 'follower_id')
                    ->withTimestamps();
    }
	
	// Check if the current user is following another user
    public function isFollowing($user)
    {
        return $this->follows()->where('followee_id', $user->id)->exists();
    }
	
	protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            do {
                $user->profile_key = Str::uuid()->toString();
            } while (Users::where('profile_key', $user->profile_key)->exists());
        });
    }
}
