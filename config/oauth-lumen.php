<?php

return [

	/*
	|--------------------------------------------------------------------------
	| oAuth Config
	|--------------------------------------------------------------------------
	*/

	/**
	 * Storage
	 */
	//'storage' => '\\OAuth\\Common\\Storage\\Session',
	'storage' => '\\Takaya030\\OAuth\\OAuthLumenSession',

	/**
	 * Consumers
	 */
	'consumers' => [

		'MyGoogle' => [
			'client_id'     => env('GOOGLE_CLIENT_ID'),
			'client_secret' => env('GOOGLE_CLIENT_SECRET'),
			'scope'         => ['https://www.googleapis.com/auth/blogger'],
		],

	]

];
