<?php

use PHPUnit\Framework\TestSuite;

class EventoImportTestSuite extends TestSuite
{
    public static function suite()
    {
        $suite = new EventoImportTestSuite();
        $suite->addTestSuite(EventoEventImporterTest::class);

        return $suite;
    }
}
