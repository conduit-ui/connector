<?php

use ConduitUi\GitHubConnector\Auth\TokenAuthentication;
use Saloon\Http\Auth\TokenAuthenticator;

it('can be instantiated with a token', function () {
    $auth = new TokenAuthentication('ghp_test_token');

    expect($auth)->toBeInstanceOf(TokenAuthentication::class);
});

it('returns a TokenAuthenticator', function () {
    $auth = new TokenAuthentication('ghp_test_token');
    $authenticator = $auth->getAuthenticator();

    expect($authenticator)->toBeInstanceOf(TokenAuthenticator::class);
});
