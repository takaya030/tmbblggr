<?php

namespace App\Http\Controllers;

use \Illuminate\Http\Request;
use \App\Http\Models\Tumblr\PostFactory;
use \App\Http\Models\Tumblr\PostSubscriber;
use App\Http\Models\Google\Datastore;
use \App\Http\Models\Google\Blogger;
use App\Http\Models\Google\Datastore\Entity;

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


		$subscriber = new PostSubscriber();
		$raw_posts = $subscriber->getPostsBySpan( $start_time, $end_time, $limit );
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

		if( $is_error == false )
		{
			$next_start_time = $subscriber->getLastTimestamp();
		}

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
}
