<?php

namespace EventoImport\communication\request_services;

class RestClientService implements RequestClientService
{
    private $base_uri;
    private $timeout_in_seconds;

    public function __construct(string $base_url, int $port, string $base_path)
    {
        $this->base_uri = "https://$base_url:$port$base_path";
        $this->timeout_in_seconds = 5;

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

        // Might be used for auth like: ["authentication" => "lorem ipsum"]
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $this->params['HEADER']);

        $output = curl_exec($ch);
        curl_close($ch);

        return $output;
    }

    public function setTimeout(int $timeout_in_seconds)
    {
        $this->timeout_in_seconds = $timeout_in_seconds;
        // TODO: Implement setTimeout() method.
    }
}
