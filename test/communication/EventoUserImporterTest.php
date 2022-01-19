<?php

use EventoImport\communication\EventoUserImporter;
use PHPUnit\Framework\TestCase;

class EventoUserImporterTest extends TestCase
{
    private function boolToJsonString(bool $value) : string
    {
        return $value ? 'true' : 'false';
    }

    private function getJsonExample(bool $success = true, bool $has_more_data = true, string $message = 'OK') : string
    {
        return '{ "success":' . $this->boolToJsonString($success) . ',
                        "hasMoreData":' . $this->boolToJsonString($has_more_data) . ',
                        "message":"' . $message . '",
                        "data":[{"This data does not matter for the unit test": ""},{"This data does not matter for the unit test": ""}]}';
    }
    
    /**
     * @test
     */
    public function smokeTest()
    {
        // Arrange
        $iterator = $this->createStub(ilEventoImporterIterator::class);
        $iterator->method('getPage')->willReturn(1);
        $iterator->method('getPageSize')->willReturn(100);

        $request = $this->createMock(\EventoImport\communication\request_services\RequestClientService::class);
        $request->method('sendRequest')
                ->with($this->equalTo('GetAccounts'), $this->equalTo(array('take' => 100, 'skip' => 0)))
                ->willReturn($this->getJsonExample(true, false));

        $settings = $this->createStub(ilSetting::class);
        $logger = $this->createStub(ilEventoImportLogger::class);
        $obj = new EventoUserImporter($iterator, $settings, $logger, $request);

        // Act
        $o = $obj->fetchNextUserDataSet();

        // Assert
        $this->assertEquals(2, count($o));
        $this->assertFalse($obj->hasMoreData());
    }
}
