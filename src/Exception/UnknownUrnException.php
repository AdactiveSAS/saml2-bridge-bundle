<?php

namespace AdactiveSas\Saml2BridgeBundle\Exception;

/**
 * Exception to be thrown when an urn cannot be found in the dictionary.
 */
class UnknownUrnException extends LogicException implements Exception
{
    public function __construct($urn)
    {
        parent::__construct(sprintf('Urn "%s" has not been defined in the AttributeDictionary', $urn));
    }
}
