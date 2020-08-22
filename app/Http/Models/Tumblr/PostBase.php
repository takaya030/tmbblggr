<?php

namespace App\Http\Models\Tumblr;

use \Carbon\Carbon;

/**
 * Tumblr post
 */
abstract class PostBase
{
	protected $data = null;
	protected $subject;			// blog subject
	protected $timestamp;		// rfc3339 date-time
	protected $tags = [];


    /**
     * @param mixed $post_data [require] A Tumblr post.
     */
	public function __construct( $post_data )
	{
		$this->data = $post_data;
		$this->parse_common();
	}

	private function parse_common()
	{
		$this->timestamp = Carbon::createFromTimestamp($this->data->timestamp, 'Asia/Tokyo')->toRfc3339String();

		if( !empty($this->data->tags) )
		{
			foreach( $this->data->tags as $tag )
			{
				$this->tags[] = $tag;
			}
		}
	}

	protected function makeSubject( string $ptype, string $summary )
	{
		$subject = isset( $summary ) ?  $summary : 'No title';
		if( empty($subject) )
			$subject = 'No title';

		$subject = (mb_strlen($subject,'utf-8') > 32)? 
			mb_substr($subject, 0, 32) . '...' :
			$subject;
		// replace tab,cr to space
		$subject = preg_replace('/[\n\r\t]/', ' ', $subject);
		$subject = preg_replace('/\s+/', ' ', $subject);

		$type_str = $this->getTypeStr();

		return '[' . $type_str . '] ' . $subject;
	}

	private function getPermaLink()
	{
		return '<div><a href="' . $this->data->post_url . '">Permalink</a></div><br />';
	}


	public function getPublished()
	{
		return $this->timestamp;
	}

	public function getTitle()
	{
		return $this->subject;
	}

	public function getTags()
	{
		return $this->tags;
	}

	public function getContent()
	{
		return $this->getPostBody() .
			$this->getPermaLink();
	}


	// custom parser for derived class
	abstract protected function parse();
	abstract protected function getTypeStr();
	abstract protected function getPostBody();
}
