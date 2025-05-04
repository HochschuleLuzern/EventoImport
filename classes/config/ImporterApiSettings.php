<?php declare(strict_types = 1);

namespace EventoImport\config;

class ImporterApiSettings
{
    private const CONF_API_URI = 'crevento_api_uri';
    private const CONF_API_AUTH_KEY = 'crevento_api_auth_key';
    private const CONF_API_AUTH_SECRET = 'crevento_api_auth_secret';
    private const CONF_API_PAGE_SIZE = 'crevento_api_page_size';
    private const CONF_API_MAX_PAGES = 'crevento_api_max_pages';
    private const CONF_API_TIMEOUT_AFTER_REQUEST = 'crevento_api_timeout_after_request';
    private const CONF_API_TIMEOUT_FAILED_REQUEST = 'crevento_api_timeout_failed_request';
    private const CONF_API_MAX_RETRIES = 'crevento_api_max_retries';

    private $settings;

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
        $this->settings = $settings;

        $this->url = $settings->get(self::CONF_API_URI, '');
        $this->api_key = $settings->get(self::CONF_API_AUTH_KEY, '');
        $this->api_secret = $settings->get(self::CONF_API_AUTH_SECRET, '');
        $this->page_size = (int) $settings->get(self::CONF_API_PAGE_SIZE, '500');
        $this->max_pages = (int) $settings->get(self::CONF_API_MAX_PAGES, '-1');
        $this->timeout_after_request = (int) $settings->get(self::CONF_API_TIMEOUT_AFTER_REQUEST, '60');
        $this->timeout_failed_request = (int) $settings->get(self::CONF_API_TIMEOUT_FAILED_REQUEST, '60');
        $this->max_retries = (int) $settings->get(self::CONF_API_MAX_RETRIES, '3');
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function getApiKey(): string
    {
        return $this->api_key;
    }

    public function setApiKey(string $api_key): void
    {
        $this->api_key = $api_key;
    }

    public function getApiSecret(): string
    {
        return $this->api_secret;
    }

    public function setApiSecret(string $api_secret): void
    {
        $this->api_secret = $api_secret;
    }

    public function getPageSize(): int
    {
        return $this->page_size;
    }

    public function setPageSize(int $page_size): void
    {
        $this->page_size = $page_size;
    }

    public function getMaxPages(): int
    {
        return $this->max_pages;
    }

    public function setMaxPages(int $max_pages): void
    {
        $this->max_pages = $max_pages;
    }

    public function getTimeoutAfterRequest(): int
    {
        return $this->timeout_after_request;
    }

    public function setTimeoutAfterRequest(int $timeout_after_request): void
    {
        $this->timeout_after_request = $timeout_after_request;
    }

    public function getTimeoutFailedRequest(): int
    {
        return $this->timeout_failed_request;
    }

    public function setTimeoutFailedRequest(int $timeout_failed_request): void
    {
        $this->timeout_failed_request = $timeout_failed_request;
    }

    public function getMaxRetries(): int
    {
        return $this->max_retries;
    }

    public function setMaxRetries(int $max_retries): void
    {
        $this->max_retries = $max_retries;
    }

    public function saveCurrentConfigurationToSettings(): void
    {
        $this->settings->set(self::CONF_API_URI, $this->url);
        $this->settings->set(self::CONF_API_AUTH_KEY, $this->api_key);
        $this->settings->set(self::CONF_API_AUTH_SECRET, $this->api_secret);
        $this->settings->set(self::CONF_API_PAGE_SIZE, (string) $this->page_size);
        $this->settings->set(self::CONF_API_MAX_PAGES, (string) $this->max_pages);
        $this->settings->set(self::CONF_API_TIMEOUT_AFTER_REQUEST, (string) $this->timeout_after_request);
        $this->settings->set(self::CONF_API_TIMEOUT_FAILED_REQUEST, (string) $this->timeout_failed_request);
        $this->settings->set(self::CONF_API_MAX_RETRIES, (string) $this->max_retries);
    }
}
