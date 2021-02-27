<?php

namespace App\Http\Models\Tumblr;

class Reblog extends OAuthClient
{
	protected $blog_id = '';

	public function __construct( string $blog_id = '' )
	{
		$this->blog_id = ($blog_id === '')? env('KONOHANA_ID') : $blog_id;

		parent::__construct();
	}

	public function doReblog( $raw_post )
	{
		$body = [
			'id' => $raw_post->id,
			'reblog_key' => $raw_post->reblog_key,
		];

		$result = json_decode($this->service->request('blog/'. $this->blog_id .'.tumblr.com/post/reblog','POST', $body), true);
		sleep(1);

		return( $result['meta']['status'] == 201 );
	}
}
