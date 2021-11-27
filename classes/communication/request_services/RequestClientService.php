<?php

namespace EventoImport\communication\request_services;

interface RequestClientService
{
    public function sendRequest(string $path, array $request_params);

    public function setTimeoutAfterRequestInSeconds(int $timeout_in_seconds);
}
