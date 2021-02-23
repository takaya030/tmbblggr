<?php

namespace App\Http\Controllers;

use \Illuminate\Http\Request;
use \App\Http\Models\Tumblr\PostFactory;
use \App\Http\Models\Tumblr\PostSubscriber;
use \App\Http\Models\Tumblr\OAuthClient;
use \App\Http\Models\Tumblr\Reblog;
use App\Http\Models\Google\Datastore;
use \App\Http\Models\Google\Blogger;
use App\Http\Models\Google\Datastore\Entity;
use \Carbon\Carbon;

class TumblrController extends Controller
{
	public function getSubscribe( Request $request )
	{
		/*
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
		 */

		ini_set("max_execution_time",1800);

		$datastore = new Datastore();
		$entity = $datastore->lookup( env('DATASTORE_KIND'), env('TUMBLR_USER_ID') );
		if( $entity instanceof Entity )
		{
			// start timestamp (newest)
			$start_time  = $next_start_time = (int)$entity->get('start');
			// end timestamp (oldest)
			$end_time = (int)$entity->get('end');
			// limit
			$limit = (int)$entity->get('limit');
		}
		else
		{
			return response()->json([
				'msg' => "fail to lookup Datastore.",
				'kind' => env('DATASTORE_KIND'),
				'key' => env('TUMBLR_USER_ID'),
			]);
		}


		$retrieve = 40;
		$subscriber = new PostSubscriber();
		$raw_posts = $subscriber->getPostsBySpan( $start_time, $end_time, $retrieve );
		$post_objs = [];

		foreach( $raw_posts as $post_item )
		{
			$post_obj = PostFactory::create( $post_item );
			if( !is_null($post_obj) )
			{
				$post_objs[] = $post_obj;
			}
		}

		if( count($post_objs) > $limit )
		{
			$post_objs = array_slice( $post_objs, 0, $limit );
		}

		$blogger = new Blogger();
		$num_objs = count($post_objs);
		$is_error = false;
		for( $i=0; $i<$num_objs; $i++ )
		{
			$result = $blogger->insertPost( $post_objs[$i] );
			if( !$result )
			{
				$is_error = true;
				break;
			}
			else
			{
				$next_start_time = $post_objs[$i]->getUnixtimestamp();
			}
		}

		/*
		if( $is_error == false )
		{
			$next_start_time = $subscriber->getLastTimestamp();
		}
		 */

		// update next start
		if( $start_time > $next_start_time )
		{
			$datastore->upsert( env('DATASTORE_KIND'), env('TUMBLR_USER_ID'), [
				'start' => $next_start_time,
				'end' => $end_time,
				'limit' => $limit,
			] );
		}

		//dd( $post_objs );
		return response()->json([
			'msg' => "success to post to blogger.",
			'next_start' => $next_start_time,
			'end' => $end_time,
			'limit' => $limit,
		]);
	}

	public function getFromend( Request $request )
	{

		ini_set("max_execution_time",1800);

		$now_time = Carbon::now()->getTimestamp();

		$datastore = new Datastore();
		$entity = $datastore->lookup( env('FROMEND_KIND'), env('TUMBLR_USER_ID') );
		if( $entity instanceof Entity )
		{
			// end timestamp (oldest)
			$end_time = (int)$entity->get('end');
			// offset hours from end
			$offset  = (int)$entity->get('offset');
			$offset_sec = $offset * 3600;
			// limit
			$limit = (int)$entity->get('limit');
		}
		else
		{
			return response()->json([
				'msg' => "fail to lookup Datastore.",
				'kind' => env('FROMEND_KIND'),
				'key' => env('TUMBLR_USER_ID'),
			]);
		}


		$retrieve = 40;
		$subscriber = new PostSubscriber();
		$raw_posts = $subscriber->getPostsFromEndAt( $end_time, $offset_sec, $retrieve );
		$post_objs = [];

		foreach( $raw_posts as $post_item )
		{
			$post_obj = PostFactory::create( $post_item );
			if( !is_null($post_obj) )
			{
				$post_objs[] = $post_obj;
			}
		}

		if( count($post_objs) > $limit )
		{
			$post_objs = array_slice( $post_objs, -$limit, $limit );
			$post_objs = array_reverse( $post_objs );
		}

		$blogger = new Blogger();
		$num_objs = count($post_objs);
		$next_end_time = ($num_objs <= 0)? $end_time + $offset_sec : $end_time;
		$is_error = false;
		for( $i=0; $i<$num_objs; $i++ )
		{
			$result = $blogger->insertPost( $post_objs[$i] );
			if( !$result )
			{
				$is_error = true;
				break;
			}
			else
			{
				if( $next_end_time < $post_objs[$i]->getUnixtimestamp() )
					$next_end_time = $post_objs[$i]->getUnixtimestamp();
			}
		}

		if( $now_time < $next_end_time )
			$next_end_time = $now_time;

		// update next end
		if( $end_time < $next_end_time )
		{
			$datastore->upsert( env('FROMEND_KIND'), env('TUMBLR_USER_ID'), [
				'end' => $next_end_time,
				'offset' => $offset,
				'limit' => $limit,
			] );
		}

		//dd( $post_objs );
		return response()->json([
			'msg' => "success to post to blogger.",
			'next_end' => $next_end_time,
			'offset' => $offset,
			'limit' => $limit,
		]);
	}

	public function getRebloggirl( Request $request )
	{
		$start = (int)$request->input('start');
		$end = (int)$request->input('end');
		//$limit = (int)$request->input('limit');
		if(empty($start) )
		{
			return;
		}
		if(empty($end) )
		{
			return;
		}
		/*
		if( empty($limit) )
		{
			return;
		}
		 */

		ini_set("max_execution_time",1800);

		$retrieve = 3;
		$subscriber = new PostSubscriber();
		$raw_posts = $subscriber->getPostsBySpan( $start, $end, $retrieve, 'girl' );

		//dd($raw_posts);

		$reblog = new Reblog();
		$result = $reblog->doReblog( $raw_posts[0] );

		dd($result);
	}

	public function getUserinfo( Request $request )
	{
		$tmb = new OAuthClient();
		$result = $tmb->getUserinfo();
		dd($result);
	}
}
