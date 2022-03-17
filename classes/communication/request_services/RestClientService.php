<?php declare(strict_types = 1);

namespace EventoImport\communication\request_services;

/**
 * Class RestClientService
 * @package EventoImport\communication\request_services
 */
class RestClientService implements RequestClientService
{
    private string $base_uri;
    private int $timeout_after_request_seconds;
    private string $api_key;
    private string $api_secret;

    public function __construct(
        string $base_uri,
        int $timeout_after_request_seconds,
        string $api_key,
        string $api_secret
    ) {
        //$this->base_uri = "https://$base_url:$port$base_path";
        $this->base_uri = $base_uri;
        $this->timeout_after_request_seconds = $timeout_after_request_seconds;
        $this->api_key = $api_key;
        $this->api_secret = $api_secret;

        if (filter_var($this->base_uri, FILTER_VALIDATE_URL, [FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED, FILTER_FLAG_PATH_REQUIRED]) === false) {
            throw new \InvalidArgumentException('Invalid Base-URI given! ' . $this->base_uri);
        }
    }

    /**
     * @param string $path
     * @param array  $request_params
     * @return bool|string
     */
    public function sendRequest(string $path, array $request_params) : string
    {
        $uri = $this->buildAndValidateUrl($path, $request_params);

        $return_value = $this->fetch($uri);

        if (is_bool($return_value) && $return_value === false) {
            throw new \ilException('Request failed');
        }

        return $return_value;
    }

    /**
     * @param string $path
     * @param array  $request_params
     * @return string
     */
    private function buildAndValidateUrl(string $path, array $request_params) : string
    {
        $url_without_query_params = $this->base_uri . $path;

        if (filter_var($this->base_uri, FILTER_VALIDATE_URL, [FILTER_FLAG_SCHEME_REQUIRED, FILTER_FLAG_HOST_REQUIRED, FILTER_FLAG_PATH_REQUIRED]) === false) {
            throw new \InvalidArgumentException('Invalid Base-URI given! ' . $this->base_uri);
        }

        $request_params['CampusApiKey'] = $this->api_key;
        $request_params['CampusApiSecret'] = $this->api_secret;

        return $url_without_query_params . '?' . http_build_query($request_params);
    }

    /**
     * @param string $url
     * @return bool|string
     */
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
}
