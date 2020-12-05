<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \App\Http\Models\Tumblr\PostSubscriber;
use \App\Http\Models\Tumblr\PostFactory;

class TmbBlggr extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'tmb:blggr
{start_time : Start published datetime (newest) (rfc3339 yyyy-mm-ddThh:mi:ss)}
{end_time : End published datetime (oldest) (rfc3339 yyyy-mm-ddThh:mi:ss)}
{--limit=20 : Limit number of posts}
{--retrieve=40 : Limit number of retrieve}
{--dry-run : enable dry run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send Tumblr posts to Blogger';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
		$carbon = new \Carbon\Carbon();
		$carbon->setTimezone('Asia/Tokyo');
		$start_time = $carbon->createFromFormat( 'Y-m-d\TH:i:s', $this->argument('start_time') )->timestamp;		// from rfc3339 to unix timestamp
		$end_time = $carbon->createFromFormat( 'Y-m-d\TH:i:s', $this->argument('end_time') )->timestamp;		// from rfc3339 to unix timestamp
		$limit = $this->option('limit');
		$retrieve = $this->option('retrieve');
		//$dry_run = $this->option('dry-run');
		$dry_run = true;

        $this->info("start time {$start_time}");
        $this->info("end time {$end_time}");
        $this->info("retrieve {$retrieve}");
        $this->info("limit {$limit}");

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

		$num_objs = count($post_objs);
		$last_timestamp = $start_time;
		for( $i=0; $i<$num_objs; $i++ )
		{
			$id = $i + 1;
			$this->info( "{$id}/{$num_objs} " . $post_objs[$i]->getDebugInfo() );
			$last_timestamp = $post_objs[$i]->getUnixtimestamp();

			if( !$dry_run )
			{
			}
		}

		//$last_timestamp = $subscriber->getLastTimestamp();
		$carbon = new \Carbon\Carbon();
		$time_str = $carbon->createFromTimestamp($last_timestamp, 'Asia/Tokyo')->format('Y-m-d\TH:i:sP T');
		$this->info("last timestamp {$last_timestamp} ({$time_str})");
    }
}
