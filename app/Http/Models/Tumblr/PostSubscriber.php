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

    /**
     * @param int $tstart_at [require] Returns posts published earlier than a specified Unitx timestamp. (newest timestamp)
     * @param int $end_at [require] Returns posts published later than a specified Unitx timestamp. (oldest timestamp)
	 * @param int $limit [optional] The number of posts to return.
	 * @return array $posts
     */
	public function getPostsBySpan( int $start_at, int $end_at, int $limit = 100 )
	{
		$next_time = $start_at;
		$remain = $limit;
		$all_posts = [];

		while( $next_time > $end_at && $remain > 0 )
		{
			$result = $this->retrievePosts( $next_time );
			if( empty($result) )
			{
				// no more posts
				$remain = 0;
			}
			else
			{
				$all_posts = array_merge( $all_posts, $result );
				$remain -= count($result);

				$last_post = array_pop( $result );
				$next_time = $last_post->timestamp;

				if( $next_time > $end_at && $remain > 0 )
				{
					sleep(2);
				}
			}
		}

		// drop posts older than end_at
		$all_posts = array_filter( $all_posts, function($v) use ($end_at) { return( $v->timestamp >= $end_at); });

		if( count($all_posts) > $limit )
		{
			$all_posts = array_slice( $all_posts, 0, $limit );
		}

		return $all_posts;
	}
}
