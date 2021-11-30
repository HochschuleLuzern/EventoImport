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
    protected $has_more_data;

    public function __construct(
        ilEventoImportLogger $logger
    ) {
        //Get Settings from dbase
        $this->evento_logger = $logger;
    }

    public function hasMoreData() : bool
    {
        return $this->has_more_data;
    }
}
