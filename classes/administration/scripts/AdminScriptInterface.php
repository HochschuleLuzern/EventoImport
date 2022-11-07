<?php declare(strict_types=1);

namespace EventoImport\administration\scripts;

use ILIAS\UI\Component\Input\Container\Form\Form;
use ILIAS\UI\Factory;
use ILIAS\UI\Component\Modal\Modal;

interface AdminScriptInterface
{
    public function getTitle() : string;
    public function getScriptId() : string;
    public function getParameterFormUI() : \ilPropertyFormGUI;
    public function getResultModalFromRequest(string $cmd, Factory $f) : Modal;
}