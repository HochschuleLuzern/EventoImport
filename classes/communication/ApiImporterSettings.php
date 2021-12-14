<?php

namespace EventoImport\communication;

class ApiImporterSettings
{
    private $url;
    private $api_key;
    private $api_secret;
    private $page_size;
    private $max_pages;
    private $timeout_after_request;
    private $timeout_failed_request;
    private $max_retries;

    public function __construct(\ilSetting $settings)
    {
        $this->url = $settings->get(\ilEventoImportCronConfig::CONF_API_URI);
        $this->api_key = $settings->get(\ilEventoImportCronConfig::CONF_API_AUTH_KEY, '');
        $this->api_secret = $settings->get(\ilEventoImportCronConfig::CONF_API_AUTH_SECRET, '');
        $this->page_size = $settings->get(\ilEventoImportCronConfig::CONF_API_PAGE_SIZE, 500);
        $this->max_pages = $settings->get(\ilEventoImportCronConfig::CONF_API_MAX_PAGES, -1);
        $this->timeout_after_request = $settings->get(\ilEventoImportCronConfig::CONF_API_TIMEOUT_AFTER_REQUEST, 60);
        $this->timeout_failed_request = $settings->get(\ilEventoImportCronConfig::CONF_API_TIMEOUT_FAILED_REQUEST, 60);
        $this->max_retries = $settings->get(\ilEventoImportCronConfig::CONF_API_MAX_RETRIES, 3);
    }

    /**
     * @return false|string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param false|string $url
     */
    public function setUrl($url) : void
    {
        $this->url = $url;
    }

    /**
     * @return false|string
     */
    public function getApikey()
    {
        return $this->api_key;
    }

    /**
     * @param false|string $api_key
     */
    public function setApikey($api_key) : void
    {
        $this->api_key = $api_key;
    }

    /**
     * @return string
     */
    public function getApiSecret()
    {
        return $this->api_secret;
    }

    /**
     * @return false|string
     */
    public function getPageSize()
    {
        return $this->page_size;
    }

    /**
     * @param false|string $page_size
     */
    public function setPageSize($page_size) : void
    {
        $this->page_size = $page_size;
    }

    /**
     * @return false|string
     */
    public function getMaxPages()
    {
        return $this->max_pages;
    }

    /**
     * @param false|string $max_pages
     */
    public function setMaxPages($max_pages) : void
    {
        $this->max_pages = $max_pages;
    }

    /**
     * @return false|string
     */
    public function getTimeoutAfterRequest()
    {
        return $this->timeout_after_request;
    }

    /**
     * @param false|string $timeout_after_request
     */
    public function setTimeoutAfterRequest($timeout_after_request) : void
    {
        $this->timeout_after_request = $timeout_after_request;
    }

    /**
     * @return false|string
     */
    public function getTimeoutFailedRequest()
    {
        return $this->timeout_failed_request;
    }

    /**
     * @param false|string $timeout_failed_request
     */
    public function setTimeoutFailedRequest($timeout_failed_request) : void
    {
        $this->timeout_failed_request = $timeout_failed_request;
    }

    /**
     * @return false|string
     */
    public function getMaxRetries()
    {
        return $this->max_retries;
    }

    /**
     * @param false|string $max_retries
     */
    public function setMaxRetries($max_retries) : void
    {
        $this->max_retries = $max_retries;
    }
}
