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

use EventoImport\communication\request_services\RequestClientService;

/**
 * Class ilEventoImporter
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

abstract class ilEventoImporter
{
    protected $data_source;
    protected $seconds_before_retry;
    protected $max_retries;
    protected $evento_logger;
    protected $has_more_data;

    public function __construct(
        RequestClientService $data_source,
        int $seconds_before_retry,
        int $max_retries,
        ilEventoImportLogger $logger
    ) {
        $this->data_source = $data_source;
        $this->seconds_before_retry = $seconds_before_retry;
        $this->max_retries = $max_retries;
        $this->evento_logger = $logger;
    }

    public function hasMoreData() : bool
    {
        return $this->has_more_data;
    }
}
