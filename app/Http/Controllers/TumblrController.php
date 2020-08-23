<?php

namespace App\Http\Controllers;

use \Illuminate\Http\Request;
use \App\Http\Models\Tumblr\PostFactory;
use \App\Http\Models\Tumblr\PostSubscriber;
use \App\Http\Models\Google\Gmail;
use App\Http\Models\Google\Datastore;
use \App\Http\Models\Google\Blogger;
use App\Http\Models\Google\Datastore\Entity;

class TumblrController extends Controller
{
	private $base_uri = 'https://api.tumblr.com';


	public function getPosts( Request $request )
	{
		$real_offset = -1;
		$real_limit = -1;
		$prev_offset = 0;
		$start_offset = 0;

		$datastore = new Datastore();
		$entity = $datastore->lookup( env('DATASTORE_KIND'), env('TUMBLR_USER_ID') );
		if( $entity instanceof Entity )
		{
			// 前回終了時の offset
			$start_offset = $prev_offset = (int)$entity->get('start_offset');

			// 今回の offset, limit
			list( $real_offset, $real_limit ) = $this->getOffetAndLimit( $start_offset );
		}

		// ポストが増えていなければ終了
		if( $real_limit <= 0 )
		{
			return response()->json([
				'msg' => "New posts are nothing.",
				'offset' => $real_offset,
				'limit' => $real_limit,
			]);
		}


		// 新規ポストの取得
		$client = new \GuzzleHttp\Client([
			'base_uri' => $this->base_uri,
		]);

		$response = $client->request('GET', '/v2/blog/'.env('TUMBLR_USER_ID').'.tumblr.com/posts', [ 'query' => [
			'api_key'		=> env('TUMBLR_API_KEY'),
			'offset'		=> $real_offset,
			'limit'			=> $real_limit,
		]]);

		$response_body = (string)$response->getBody();
		$result = json_decode( $response_body );

		if( $result->meta->status != 200 || $result->meta->msg !== "OK" )
		{
			return response()->json([
				'msg' => "Fail to retrieve posts.",
				'offset' => $real_offset,
				'limit' => $real_limit,
			]);
		}


		// insert mails
		if( is_array( $result->response->posts ) )
		{
			$posts = array_reverse( $result->response->posts );
			$gmail = new Gmail();
			foreach( $posts as $post_item )
			{
				$post_obj = PostFactory::create( $post_item );
				if( !is_null($post_obj) )
				{
					if( $gmail->insertMail( $post_obj ) == false )
						break;
				}
				$start_offset++;
			}
		}


		// 次回の start_offset を保存
		if( $prev_offset < $start_offset )
		{
			$datastore->upsert( env('DATASTORE_KIND'), env('TUMBLR_USER_ID'), ['start_offset' => $start_offset] );
		}


		return response()->json([
			'msg' => "Success to insert mails.",
			'offset' => $real_offset,
			'limit' => $real_limit,
		]);
	}


	private function getOffetAndLimit( $prev_offset )
	{
		$real_offset = 0;
		$real_limit = 0;

		$client = new \GuzzleHttp\Client([
			'base_uri' => $this->base_uri,
		]);

		$response = $client->request('GET', '/v2/blog/'.env('TUMBLR_USER_ID').'.tumblr.com/info', [ 'query' => [
			'api_key'		=> env('TUMBLR_API_KEY'),
		]]);

		$response_body = (string)$response->getBody();
		$result = json_decode( $response_body );

		if( $result->meta->status == 200 && $result->meta->msg === "OK" )
		{
			$total_posts = (int)$result->response->blog->posts;			// 現在のポスト数
			$num_mails = (int)env('TUMBLR_MAX_POSTS');			// 1リクエストで処理するポスト数

			if( $total_posts <= $prev_offset + $num_mails )
			{
				// 先頭から処理する
				//$real_offset = 0;
				$real_limit = $total_posts - $prev_offset;
			}
			else
			{
				// 前回から num_mails 以上ポストされた
				$real_offset = ($total_posts - $prev_offset) - $num_mails;
				$real_limit = $num_mails;
			}
		}

		return [ $real_offset, $real_limit ];
	}

	public function getSubscribe( Request $request )
	{
		$start = (int)$request->input('start');
		$end = (int)$request->input('end');
		$limit = (int)$request->input('limit');
		if(empty($start) )
		{
			return;
		}
		if(empty($end) )
		{
			return;
		}
		if( empty($limit) )
		{
			return;
		}

		ini_set("max_execution_time",1800);

		$subscriber = new PostSubscriber();
		$raw_posts = $subscriber->getPostsBySpan( $start, $end, $limit );
		$post_objs = [];

		foreach( $raw_posts as $post_item )
		{
			$post_obj = PostFactory::create( $post_item );
			if( !is_null($post_obj) )
			{
				$post_objs[] = $post_obj;
			}
		}

		$blogger = new Blogger();
		$num_objs = count($post_objs);
		for( $i=0; $i<$num_objs; $i++ )
		{
			$result = $blogger->insertPost( $post_objs[$i] );
			if( !$result )
			{
				break;
			}
		}

		//dd( $post_objs );
	}
}
