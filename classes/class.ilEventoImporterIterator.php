<?php declare(strict_types = 1);
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
 * Class ilEventoImporterIterator
 *
 * @author Stephan Winiker <stephan.winiker@hslu.ch>
 */

class ilEventoImporterIterator {
	private int $page;
	private int $page_size;
	
	public function __construct(int $page_size) {
		$this->page = 1;
		$this->page_size = $page_size;
	}
	
	public function nextPage() : int
    {
		return $this->page++;
	}
	
	public function getPage() : int
    {
		return $this->page;
	}

	public function getPageSize() : int
    {
        return $this->page_size;
    }
}