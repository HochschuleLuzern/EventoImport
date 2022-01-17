<?php

namespace EventoImport\communication;

/**
 * Class ApiImporterSettings
 * @package EventoImport\communication
 */
class ApiImporterSettings
{
    /** @var string */
    private string $url;

    /** @var string */
    private string $api_key;

    /** @var string */
    private string $api_secret;

    /** @var int */
    private int $page_size;

    /** @var int */
    private int $max_pages;

    /** @var int */
    private int $timeout_after_request;

    /** @var int */
    private int $timeout_failed_request;

    /** @var int */
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

    /**
     * @return string
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * @param string $url
     */
    public function setUrl($url) : void
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getApikey() : string
    {
        return $this->api_key;
    }

    /**
     * @param string $api_key
     */
    public function setApikey($api_key) : void
    {
        $this->api_key = $api_key;
    }

    /**
     * @return string
     */
    public function getApiSecret() : string
    {
        return $this->api_secret;
    }

    /**
     * @return int
     */
    public function getPageSize() : int
    {
        return $this->page_size;
    }

    /**
     * @param int $page_size
     */
    public function setPageSize(int $page_size) : void
    {
        $this->page_size = $page_size;
    }

    /**
     * @return int
     */
    public function getMaxPages() : int
    {
        return $this->max_pages;
    }

    /**
     * @param int $max_pages
     */
    public function setMaxPages(int $max_pages) : void
    {
        $this->max_pages = $max_pages;
    }

    /**
     * @return int
     */
    public function getTimeoutAfterRequest() : int
    {
        return $this->timeout_after_request;
    }

    /**
     * @param int $timeout_after_request
     */
    public function setTimeoutAfterRequest(int $timeout_after_request) : void
    {
        $this->timeout_after_request = $timeout_after_request;
    }

    /**
     * @return int
     */
    public function getTimeoutFailedRequest() : int
    {
        return $this->timeout_failed_request;
    }

    /**
     * @param int $timeout_failed_request
     */
    public function setTimeoutFailedRequest(int $timeout_failed_request) : void
    {
        $this->timeout_failed_request = $timeout_failed_request;
    }

    /**
     * @return int
     */
    public function getMaxRetries() : int
    {
        return $this->max_retries;
    }

    /**
     * @param int $max_retries
     */
    public function setMaxRetries(int $max_retries) : void
    {
        $this->max_retries = $max_retries;
    }
}
