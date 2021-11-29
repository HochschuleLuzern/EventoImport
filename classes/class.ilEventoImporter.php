<?php
/**
 * Copyright (c) 2017 Hochschule Luzern
 *
 * This file is part of the EventoImport-Plugin for ILIAS.

 * EventoImport-Plugin for ILIAS is free software: you can redistribute
 * it and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.

 * EventoImport-Plugin for ILIAS is distributed in the hope that
 * it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with EventoImport-Plugin for ILIAS.  If not,
 * see <http://www.gnu.org/licenses/>.
 */

/**
 * Class ilEventoImporter
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

abstract class ilEventoImporter
{
    private $evento_logger;

    private $max_pages;
    private $max_retries;
    private $seconds_before_retry;
    private $token;

    public function __construct(
        \EventoImport\communication\request_services\RequestClientService $data_source,
        \EventoImport\communication\ApiImporterSettings $settings,
        ilEventoImportLogger $logger
    ) {
        //Get Settings from dbase
        $this->max_pages = $settings->getMaxPages();
        $this->max_retries = $settings->getMaxRetries();
        $this->seconds_before_retry = $settings->getTimeoutFailedRequest();
        
        $this->evento_logger = $logger;
        $this->data_source = $data_source;
    }

    public function getDataSource() : \EventoImport\communication\request_services\RequestClientService
    {
        return $this->data_source;
    }

    public function fetchSpecificDataSet(int $skip, int $take)
    {
        $params = array(
            "skip" => $skip,
            "take" => $take
        );

        $json_response = $this->data_source->sendRequest($this->fetch_data_set_method, $params);

        $json_response_decoded = $this->validateResponseAndGetAsJsonStructure($json_response);

        return $json_response_decoded['data'];
    }
}
