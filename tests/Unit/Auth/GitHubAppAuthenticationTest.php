<?php

use ConduitUi\GitHubConnector\Auth\GitHubAppAuthentication;
use Saloon\Http\Auth\TokenAuthenticator;

it('can be instantiated with a JWT', function () {
    $auth = new GitHubAppAuthentication('eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.test');

    expect($auth)->toBeInstanceOf(GitHubAppAuthentication::class);
});

it('returns a TokenAuthenticator with Bearer prefix', function () {
    $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.test';
    $auth = new GitHubAppAuthentication($jwt);
    $authenticator = $auth->getAuthenticator();

    expect($authenticator)->toBeInstanceOf(TokenAuthenticator::class);
});
