<?php

namespace EventoImport\communication\request_services;

interface RequestClientService
{
    public function sendRequest(string $path, array $request_params);

    public function setTimeout(int $timeout_in_seconds);
}