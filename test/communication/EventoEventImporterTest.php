<?php

use EventoImport\communication\EventoEventImporter;
use PHPUnit\Framework\TestCase;

class EventoEventImporterTest extends TestCase
{
    use \EventoImport\test\UnitTestHelper\UnitTestHelper;

    public function setUp() : void
    {
        parent::setUp();
    }

    private function ilSettings_get_mock($key, $default = '')
    {
        return $default;
    }

    /**
     * @test
     */
    public function fetchNextDataSet_fetch3AllCorrect_ReceiveArrayOf3()
    {
        // Arrange
        $number_of_takes = 3;
        $mock_json = '{ "success":true,
                        "hasMoreData":true,
                        "message":"OK",
                        "data":[{"This data does not matter for the unit test": ""},{"This data does not matter for the unit test": ""},{"This data does not matter for the unit test": ""}]}';
        $request = $this->createStub(\EventoImport\communication\request_services\RequestClientService::class);
        $settings = $this->createMock(ilSetting::class);
        $logger = $this->createStub(ilEventoImportLogger::class);

        $request->method('sendRequest')->willReturn($mock_json);
        $settings->method('get')->willReturn(null);
        $obj = new EventoEventImporter(new \ilEventoImporterIterator($number_of_takes), $settings, $logger, $request);

        // Act
        $data = $obj->fetchNextEventDataSet();

        // Assert
        $this->assertEquals($number_of_takes, count($data));
    }

    /**
     * @test
     */
    public function fetchNextDataSet_fetch3HasOnly2_hasMoreDataFlagFalse()
    {
        // Arrange
        $number_of_takes = 3;
        $mock_json = '{ "success":true,
                        "hasMoreData":false,
                        "message":"OK",
                        "data":[{"This data does not matter for the unit test": ""},{"This data does not matter for the unit test": ""}]}';
        $request = $this->createStub(\EventoImport\communication\request_services\RequestClientService::class);
        $settings = $this->createMock(ilSetting::class);
        $logger = $this->createStub(ilEventoImportLogger::class);

        $request->method('sendRequest')->willReturn($mock_json);
        $settings->method('get')->willReturn(null);
        $obj = new EventoEventImporter(new \ilEventoImporterIterator($number_of_takes), $settings, $logger, $request);

        // Act
        $data = $obj->fetchNextEventDataSet();

        // Assert
        $this->assertEquals($number_of_takes - 1, count($data));
        $this->assertFalse($obj->hasMoreData());
    }
}
