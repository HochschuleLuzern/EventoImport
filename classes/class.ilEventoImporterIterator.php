<?php
class ilEventoImporterIterator {
	private $page;
	
	public function __construct() {
		$this->page = 1;
	}
	
	public function nextPage() {
		$this->page++;
	}
	
	public function getPage() {
		return $this->page;
	}
}