<?php

namespace AdactiveSas\Saml2BridgeBundle\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException as SymfonyBadRequestHttpException;

class BadRequestHttpException extends SymfonyBadRequestHttpException implements Exception
{
}
