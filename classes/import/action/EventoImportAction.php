<?php

namespace EventoImport\import\action;

/**
 * Interface EventoImportAction
 * @package EventoImport\import\action
 */
interface EventoImportAction
{
    /**
     * Executes the action to the given Evento Object
     * @return void
     */
    public function executeAction() : void;
}
