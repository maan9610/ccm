<?php

namespace App\Services;

use Google\Client;
use Google\Service\YouTube;

class YouTubeService
{
    protected $client;
    protected $youtube;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setApplicationName(config('app.name'));
        $this->client->setDeveloperKey(env('YOUTUBE_API_KEY'));

        $this->youtube = new YouTube($this->client);
    }

    // Get channel data by channel ID
    public function getChannelData($channelId)
    {
        $response = $this->youtube->channels->listChannels('id,snippet,statistics,brandingSettings,contentDetails,topicDetails,contentOwnerDetails', [
            'id' => $channelId,
        ]);

        return $response->items[0] ?? null;
    }

    // Get video details by video ID
    public function getVideoData($videoId)
    {
        $response = $this->youtube->videos->listVideos('snippet,statistics', [
            'id' => $videoId,
        ]);

        return $response->items[0] ?? null;
    }

    // You can add more methods to interact with other YouTube API resources
	
	public function getChannelDatabyUsername($username)
    {
		$response = $this->youtube->search->listSearch('snippet', [
			'q' => $username, // Search query (can be username or partial name)
			'type' => 'channel',
		]);

		// Get the channel ID from the response
		if (!empty($response['items'])) {
			$channelId = $response['items'][0]['id']['channelId'];
			
			$response = $this->youtube->channels->listChannels('id,snippet,statistics,brandingSettings,contentDetails,topicDetails,contentOwnerDetails', [
				'id' => $channelId,
			]);

			return $response->items[0] ?? null;
		}
		return $response->items[0] ?? null;
         //return $response->items[0] ?? null;
    }
} 