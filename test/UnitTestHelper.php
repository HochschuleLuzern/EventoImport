<?php

namespace EventoImport\test\UnitTestHelper;

trait UnitTestHelper
{
    public function buildMethodForPrivateMethodTesting($class_name, $method_name) : \ReflectionMethod
    {
        $method = new \ReflectionMethod($class_name, $method_name);
        $method->setAccessible(true);

        return $method;
    }
}
