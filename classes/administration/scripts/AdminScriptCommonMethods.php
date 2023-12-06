<?php declare(strict_types=1);

namespace EventoImport\administration\scripts;

use ILIAS\UI\Factory;
use ILIAS\UI\Component\Modal\Modal;
use EventoImport\config\ImporterApiSettings;
use EventoImport\communication\EventoEventImporter;
use EventoImport\communication\request_services\RestClientService;
use EventoImport\communication\ImporterIterator;
use EventoImport\import\Logger;

trait AdminScriptCommonMethods
{
    private function buildModal(string $title, string $content, Factory $f) : Modal
    {
        return $f->modal()->lightbox($f->modal()->lightboxTextPage($content, $title));
    }

    private function buildEventImporter($db)
    {
        $api_settings = new ImporterApiSettings(new \ilSetting('crevento'));
        return new EventoEventImporter(
            new RestClientService(
                $api_settings->getUrl(),
                $api_settings->getTimeoutFailedRequest(),
                $api_settings->getApiKey(),
                $api_settings->getApiSecret()
            ),
            new ImporterIterator($api_settings->getPageSize()),
            new Logger($db),
            $api_settings->getTimeoutAfterRequest(),
            $api_settings->getMaxRetries()
        );
    }
}