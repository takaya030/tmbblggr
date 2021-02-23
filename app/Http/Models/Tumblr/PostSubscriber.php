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
	protected $last_timestamp = null;


	public function __construct()
	{
		$this->client = new \GuzzleHttp\Client([
			'base_uri' => $this->base_uri,
		]);
	}

	public function getLastTimestamp()
	{
		return $this->last_timestamp;
	}

    /**
     * @param int $timestamp [require] Returns posts published earlier than a specified Unitx timestamp.
	 * @param int $limit [optional] The number of posts to return: 1-20, inclusive.
	 * @param string $tag [optional] Limits the response to posts with the specified tag
	 * @return array $posts
     */
	public function retrievePosts( int $timestamp, int $limit = 20, string $tag = '' )
	{
		$query = [
			'api_key'		=> env('TUMBLR_API_KEY'),
			'before'		=> $timestamp,
			'limit'			=> $limit,
		];

		if( $tag !== '' )
		{
			$query['tag'] = $tag;
		}

		$response = $this->client->request('GET', '/v2/blog/'.env('TUMBLR_USER_ID').'.tumblr.com/posts', [ 'query' => $query ]);

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
	 * @param string $tag [optional] Limits the response to posts with the specified tag
	 * @return array $posts
     */
	public function getPostsBySpan( int $start_at, int $end_at, int $limit = 40, string $tag = '' )
	{
		$next_time = $start_at;
		$remain = $limit;
		$retrieve_limit = 20;
		$all_posts = [];

		while( $next_time > $end_at && $remain > 0 )
		{
			$result = $this->retrievePosts( $next_time, $retrieve_limit, $tag );
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

		// store last timestamp of posts
		if( count($all_posts) > 0 )
		{
			$last_post = end($all_posts);
			$this->last_timestamp = $last_post->timestamp;
		}
		else
		{
			$this->last_timestamp = $end_at;
		}


		return $all_posts;
	}

    /**
     * @param int $end_at [require] Returns posts published later than a specified Unitx timestamp. (oldest timestamp)
     * @param int $add_sec [require] Offset seconds from $end_at. ($start_at = $end_at + $add_sec)
	 * @param int $limit [optional] The number of posts to return.
	 * @return array $posts
     */
	public function getPostsFromEndAt( int $end_at, int $add_sec, int $limit = 40 )
	{
		$start_at = $end_at + $add_sec + 1;			// for include the post that timestamp is (timestamp == end_at + add_sec)
		$posts = $this->getPostsBySpan( $start_at, $end_at, $limit );

		// drop posts older than end_at or equal
		$posts = array_filter( $posts, function($v) use ($end_at) { return( $v->timestamp > $end_at); });

		return $posts;
	}
}
