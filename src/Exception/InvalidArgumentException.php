<?php

namespace AdactiveSas\Saml2BridgeBundle\Exception;

use InvalidArgumentException as CoreInvalidArgumentException;

class InvalidArgumentException extends CoreInvalidArgumentException implements Exception
{
    public static function invalidType($expectedType, $parameter, $value)
    {
        return new self(sprintf(
            'Invalid Argument, parameter "%s" should be of type "%s", "%s" given',
            $parameter,
            $expectedType,
            is_object($value) ? get_class($value) : gettype($value)
        ));
    }
}
