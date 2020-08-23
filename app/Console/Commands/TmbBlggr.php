<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

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
{--limit=100 : Limit number of posts}';

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

        $this->info("start time {$start_time}");
        $this->info("end time {$end_time}");
        $this->info("limit {$limit}");
    }
}
