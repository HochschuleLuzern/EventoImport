<?php

use PHPUnit\Framework\TestCase;

class ilEventoImportRESTClientTest extends TestCase
{

    /**
     * @test
     * @small
     */
    public function testSendRequest()
    {
        $rest_client = new ilEventoImportRESTClient('');

        $this->assertTrue(true);
    }
}
