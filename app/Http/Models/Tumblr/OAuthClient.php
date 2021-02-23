<?php

namespace App\Http\Models\Tumblr;

use OAuth\OAuth1\Token\StdOAuth1Token;

class OAuthClient
{
	protected $servive;

	public function __construct()
	{
		$this->service = $this->getOauthService();
	}

	protected function getOauthService()
	{
        $token = new StdOAuth1Token();
        $token->setRequestToken( env("TUMBLR_ACCESS_TOKEN") );
        $token->setRequestTokenSecret( env("TUMBLR_ACCESS_TOKEN_SECRET") );
        $token->setAccessToken( env("TUMBLR_ACCESS_TOKEN") );
        $token->setAccessTokenSecret( env("TUMBLR_ACCESS_TOKEN_SECRET") );

		$service = app('oauth')->consumer('Tumblr');
		$service->getStorage()->storeAccessToken('Tumblr', $token);

		return $service;
	}

	public function getUserinfo()
	{
		$result = json_decode($this->service->request('user/info','GET'), true);

		return $result;
	}
}
