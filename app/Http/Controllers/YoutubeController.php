<?php

namespace App\Http\Controllers;

use App\Services\YouTubeService;

use Illuminate\Http\Request;

class YoutubeController extends Controller
{
    //
	protected $youtubeService;

    public function __construct(YouTubeService $youtubeService)
    {
        $this->youtubeService = $youtubeService;
    }

    public function getChannel($channelId)
    {
        $channelData = $this->youtubeService->getChannelData($channelId);

        return response()->json($channelData);
    }
	
	public function getChannelDataByName($username)
    {
		
        $channelData = $this->youtubeService->getChannelDatabyUsername($username);
		
		$channelId = $channelData->id;
		$publishedAt = $channelData->snippet->publishedAt;
		$title = $channelData->snippet->title;
		$country = $channelData->snippet->country;
		
		$subscriberCount = $channelData->statistics->subscriberCount;
		$videoCount = $channelData->statistics->videoCount;
		$viewCount = $channelData->statistics->viewCount;
		//echo "Channel Id =".$channelId;
		//die;

        return response()->json($channelData);
    }

    public function getVideo($videoId)
    {
        $videoData = $this->youtubeService->getVideoData($videoId);

        return response()->json($videoData);
    }
}
