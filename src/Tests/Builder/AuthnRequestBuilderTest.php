<?php
namespace AdactiveSas\Saml2BridgeBundle\Tests\Builder;


use AdactiveSas\Saml2BridgeBundle\SAML2\BridgeContainer;
use AdactiveSas\Saml2BridgeBundle\SAML2\Builder\AuthnRequestBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @runTestsInSeparateProcesses
 */
class AuthnRequestBuilderTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        \SAML2_Compat_ContainerSingleton::setContainer(new \SAML2_Compat_MockContainer());
    }

    public function testConstructorWithDefaultValue()
    {
        $authResponse = new AuthnRequestBuilder();

        $now = new \DateTime();
        self::assertInstanceOf(\DateTime::class, $authResponse->getIssueInstant());

        self::assertEquals($now->getTimestamp(), $authResponse->getIssueInstant()->getTimestamp(), '', 0.5);
        self::assertEquals($now->getTimezone(), new \DateTimeZone('UTC'));
    }

    public function testConstructorWithDateTime()
    {
        $issueInstant = new \DateTime("2016-01-01");
        $authResponse = new AuthnRequestBuilder($issueInstant);

        self::assertInstanceOf(\DateTime::class, $authResponse->getIssueInstant());
        self::assertEquals($issueInstant, $authResponse->getIssueInstant());
    }

    public function testIssuer()
    {
        $authResponse = new AuthnRequestBuilder();
        $issuer = "issuer";


        $authResponse->setIssuer($issuer);
        $response = $authResponse->getRequest();
        self::assertEquals($issuer, $response->getIssuer());
    }

    public function testDestination()
    {
        $authResponse = new AuthnRequestBuilder();
        $destination = "destination";


        $authResponse->setDestination($destination);
        $response = $authResponse->getRequest();
        self::assertEquals($destination, $response->getDestination());
    }

    public function testRelayState()
    {
        $authResponse = new AuthnRequestBuilder();
        $relayState = "relayState";


        $authResponse->setRelayState($relayState);
        $response = $authResponse->getRequest();
        self::assertEquals($relayState, $response->getRelayState());
    }
}