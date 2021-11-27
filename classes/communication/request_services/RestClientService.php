<?php

namespace EventoImport\communication\request_services;

class RestClientService implements RequestClientService
{
    private $base_uri;
    private $timeout_after_request_seconds;

    public function __construct(
        string $base_uri,
        int $timeout_after_request_seconds
    ) {
        //$this->base_uri = "https://$base_url:$port$base_path";
        $this->base_uri = $base_uri;
        $this->timeout_after_request_seconds = $timeout_after_request_seconds;

        if (filter_var($this->base_uri, FILTER_VALIDATE_URL, [FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED, FILTER_FLAG_PATH_REQUIRED]) === false) {
            throw new \InvalidArgumentException('Invalid Base-URI given! ' . $this->base_uri);
        }
    }

    public function sendRequest(string $path, array $request_params)
    {
        $uri = $this->buildAndValidateUrl($path, $request_params);

        $return_value = $this->fetch($uri);

        return $return_value;
    }

    private function buildAndValidateUrl(string $path, array $request_params)
    {
        $url_without_query_params = $this->base_uri . $path;

        if (filter_var($this->base_uri, FILTER_VALIDATE_URL, [FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED, FILTER_FLAG_PATH_REQUIRED]) === false) {
            throw new \InvalidArgumentException('Invalid Base-URI given! ' . $this->base_uri);
        }

        return $url_without_query_params . '?' . http_build_query($request_params);
    }

    private function fetch(string $url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout_after_request_seconds);

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    public function setTimeoutAfterRequestInSeconds(int $timeout_in_seconds)
    {
        $this->timeout_after_request_seconds = $timeout_in_seconds;
    }
}
