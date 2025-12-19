<?php

use ConduitUi\GitHubConnector\Auth\DefaultDeviceFlowHttpClient;
use ConduitUi\GitHubConnector\Auth\DeviceFlowHttpClient;

it('implements DeviceFlowHttpClient interface', function () {
    $client = new DefaultDeviceFlowHttpClient;

    expect($client)->toBeInstanceOf(DeviceFlowHttpClient::class);
});
