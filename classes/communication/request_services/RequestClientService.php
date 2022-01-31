<?php declare(strict_types = 1);

namespace EventoImport\communication\request_services;

/**
 * Interface RequestClientService
 * @package EventoImport\communication\request_services
 */
interface RequestClientService
{
    public function sendRequest(string $path, array $request_params);
}
