<?php declare(strict_types = 1);

namespace EventoImport\communication;

class ImporterApiSettings
{
    private string $url;
    private string $api_key;
    private string $api_secret;
    private int $page_size;
    private int $max_pages;
    private int $timeout_after_request;
    private int $timeout_failed_request;
    private int $max_retries;

    public function __construct(\ilSetting $settings)
    {
        $this->url = $settings->get(\ilEventoImportCronConfig::CONF_API_URI);
        $this->api_key = $settings->get(\ilEventoImportCronConfig::CONF_API_AUTH_KEY, '');
        $this->api_secret = $settings->get(\ilEventoImportCronConfig::CONF_API_AUTH_SECRET, '');
        $this->page_size = (int) $settings->get(\ilEventoImportCronConfig::CONF_API_PAGE_SIZE, 500);
        $this->max_pages = (int) $settings->get(\ilEventoImportCronConfig::CONF_API_MAX_PAGES, -1);
        $this->timeout_after_request = (int) $settings->get(\ilEventoImportCronConfig::CONF_API_TIMEOUT_AFTER_REQUEST, 60);
        $this->timeout_failed_request = (int) $settings->get(\ilEventoImportCronConfig::CONF_API_TIMEOUT_FAILED_REQUEST, 60);
        $this->max_retries = (int) $settings->get(\ilEventoImportCronConfig::CONF_API_MAX_RETRIES, 3);
    }

    public function getUrl() : string
    {
        return $this->url;
    }

    public function setUrl($url) : void
    {
        $this->url = $url;
    }

    public function getApikey() : string
    {
        return $this->api_key;
    }

    public function setApikey($api_key) : void
    {
        $this->api_key = $api_key;
    }

    public function getApiSecret() : string
    {
        return $this->api_secret;
    }

    public function getPageSize() : int
    {
        return $this->page_size;
    }

    public function setPageSize(int $page_size) : void
    {
        $this->page_size = $page_size;
    }

    public function getMaxPages() : int
    {
        return $this->max_pages;
    }

    public function setMaxPages(int $max_pages) : void
    {
        $this->max_pages = $max_pages;
    }

    public function getTimeoutAfterRequest() : int
    {
        return $this->timeout_after_request;
    }

    public function setTimeoutAfterRequest(int $timeout_after_request) : void
    {
        $this->timeout_after_request = $timeout_after_request;
    }

    public function getTimeoutFailedRequest() : int
    {
        return $this->timeout_failed_request;
    }

    public function setTimeoutFailedRequest(int $timeout_failed_request) : void
    {
        $this->timeout_failed_request = $timeout_failed_request;
    }

    public function getMaxRetries() : int
    {
        return $this->max_retries;
    }

    public function setMaxRetries(int $max_retries) : void
    {
        $this->max_retries = $max_retries;
    }
}
