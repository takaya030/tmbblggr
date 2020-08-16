<?php

namespace App\Http\Models\Tumblr;

use \Carbon\Carbon;

/**
 * Tumblr posts subscriber
 */
class PostSubscriber
{
	private $base_uri = 'https://api.tumblr.com';
	protected $client;		// \GuzzleHttp\Client
	protected $posts = [];

	protected $data = null;
	protected $subject;			// mail subject
	protected $timestamp;		// rfc2822 string
	protected $tags = [];


	public function __construct()
	{
		$this->client = new \GuzzleHttp\Client([
			'base_uri' => $this->base_uri,
		]);
	}

    /**
     * @param int $timestamp [require] Returns posts published earlier than a specified Unitx timestamp.
	 * @param int $limit [optional] The number of posts to return: 1-20, inclusive.
	 * @return array $posts
     */
	public function retrievePosts( int $timestamp, int $limit = 20 )
	{
		$response = $this->client->request('GET', '/v2/blog/'.env('TUMBLR_USER_ID').'.tumblr.com/posts', [ 'query' => [
			'api_key'		=> env('TUMBLR_API_KEY'),
			'before'		=> $timestamp,
			'limit'			=> $limit,
		]]);

		$response_body = (string)$response->getBody();
		$result = json_decode( $response_body );

		if( $result->meta->status != 200 || $result->meta->msg !== "OK" )
		{
			// fail to retrieve
			return [];
		}

		return ( is_array( $result->response->posts ) )? $result->response->posts : [];
	}
}
