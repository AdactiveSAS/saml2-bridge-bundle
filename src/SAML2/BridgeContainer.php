<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2;

use Psr\Log\LoggerInterface;

/**
 * Container that is required so that we can make the SAML2 lib work.
 * This container is set as the container in the AdactiveSasSaml2BridgeBundle::boot() method
 */
class BridgeContainer extends \SAML2_Compat_AbstractContainer
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * Generate a random identifier for identifying SAML2 documents.
     */
    public function generateId()
    {
        return '_' . bin2hex(openssl_random_pseudo_bytes(30));
    }

    public function debugMessage($message, $type)
    {
        $this->logger->debug($message, ['type' => $type]);
    }

    public function redirect($url, $data = array())
    {
        throw new \BadMethodCallException(sprintf(
            "%s:%s may not be called in the Adactive\\Saml2BridgeBundle as it doesn't work with Symfony2",
            __CLASS__,
            __METHOD__
        ));
    }

    public function postRedirect($url, $data = array())
    {
        throw new \BadMethodCallException(sprintf(
            "%s:%s may not be called in the Adactive\\Saml2BridgeBundle as it doesn't work with Symfony2",
            __CLASS__,
            __METHOD__
        ));
    }
}