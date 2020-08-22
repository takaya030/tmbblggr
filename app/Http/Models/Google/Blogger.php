<?php

namespace App\Http\Models\Google;

use App\Http\Models\Tumblr\PostBase as TumblrPostItem;

class Blogger extends OAuthClient
{
	public function __construct( bool $is_refresh_token = true )
	{
		parent::__construct( $is_refresh_token );
	}

	public function insertPost( TumblrPostItem $item )
	{
		$params = [
			'kind'		=> 'blogger#post',
			'id'		=> env('BLOGGER_BLOG_ID'),
			'published'	=> $item->getPublished(),
			'title'		=> $item->getTitle(),
			'content'	=> $item->getContent(),
			'labels'	=> $item->getTags(),
		];

		$googleService = $this->getOauthService();

		// insert blog post
		try {
			$result = json_decode(
				$googleService->request(
					'https://www.googleapis.com/blogger/v3/blogs/' . env('BLOGGER_BLOG_ID') . '/posts',
					'POST',
					json_encode($params),
					[ 'Content-type' => 'application/json' ]
				),
				true
			);
			sleep(1);

			return isset($result['id']);
		}
		catch(Exception $e)
		{
			//return 'An error occurred: ' . $e->getMessage();
		}

		return false;
	}
}
