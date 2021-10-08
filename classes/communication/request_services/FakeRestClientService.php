<?php

namespace EventoImport\communication\request_services;

class FakeRestClientService extends RestClientService
{
    private $base_uri;
    private $timeout_in_seconds;
    private $file_path;
    private $has_more;

    public function __construct(string $base_url, int $port, string $base_path)
    {
        // We don't care about the given URL
        $this->file_path = "/var/www/json_mocks/";
        $this->has_more = 'false';
    }

    public function sendRequest(string $path, array $request_params)
    {
        if($path == 'getUsers') {

        } else if($path == 'getEvents') {

        }

        $file_content = file_get_contents($this->file_path . "users.json");
        $file_content = '{"success":true,"hasMoreData":'.$this->has_more.',"message":"OK","data":' . $file_content . '}';
        $this->has_more = 'false';
        return $file_content;
    }

    public function setTimeout(int $timeout_in_seconds)
    {
        $this->timeout_in_seconds = $timeout_in_seconds;
    }
}