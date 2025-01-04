<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Campaigns extends Model
{
    use HasFactory;
	public function userSavedTasks()
    {
        return $this->hasMany(UserSavedTasks::class);
    }
	
	
	
	 public function generateUniqueUrlKey($title)
    {
        $slug = Str::slug($title);
        $count = static::whereRaw("url_key REGEXP '^{$slug}(-[0-9]*)?$'")->count();
        return $count ? "{$slug}-{$count}" : $slug;
    }
}
