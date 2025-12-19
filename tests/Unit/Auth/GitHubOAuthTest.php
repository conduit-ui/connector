<?php

use ConduitUi\GitHubConnector\Auth\GitHubOAuth;
use Saloon\Http\Auth\TokenAuthenticator;

it('can be instantiated with an access token', function () {
    $auth = new GitHubOAuth('gho_access_token_here');

    expect($auth)->toBeInstanceOf(GitHubOAuth::class);
});

it('returns a TokenAuthenticator with Bearer prefix', function () {
    $accessToken = 'gho_access_token_here';
    $auth = new GitHubOAuth($accessToken);
    $authenticator = $auth->getAuthenticator();

    expect($authenticator)->toBeInstanceOf(TokenAuthenticator::class);
});
