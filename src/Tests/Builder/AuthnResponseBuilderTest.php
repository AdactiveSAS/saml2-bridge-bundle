<?php

namespace AdactiveSas\Saml2BridgeBundle\Tests\Builder;


use AdactiveSas\Saml2BridgeBundle\SAML2\BridgeContainer;
use AdactiveSas\Saml2BridgeBundle\SAML2\Builder\AssertionBuilder;
use AdactiveSas\Saml2BridgeBundle\SAML2\Builder\AuthnResponseBuilder;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @runTestsInSeparateProcesses
 */
class AuthnResponseBuilderTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        \SAML2_Compat_ContainerSingleton::setContainer(new \SAML2_Compat_MockContainer());
    }

    public function testConstructorWithDefaultValue()
    {
        $authResponse = new AuthnResponseBuilder();

        $now = new \DateTime();
        self::assertInstanceOf(\DateTime::class, $authResponse->getIssueInstant());

        self::assertEquals($now->getTimestamp(), $authResponse->getIssueInstant()->getTimestamp(), '', 0.5);
        self::assertEquals($now->getTimezone(), new \DateTimeZone('UTC'));
        self::assertEquals([], $authResponse->getAssertionBuilders());
    }

    public function testConstructorWithDateTime()
    {
        $issueInstant = new \DateTime("2016-01-01");
        $authResponse = new AuthnResponseBuilder($issueInstant);

        self::assertInstanceOf(\DateTime::class, $authResponse->getIssueInstant());
        self::assertEquals($issueInstant, $authResponse->getIssueInstant());
        self::assertEquals([], $authResponse->getAssertionBuilders());
    }

    public function testGetResponseWithoutAssertionBuilders()
    {
        $authResponse = new AuthnResponseBuilder();
        $response = $authResponse->getResponse();

        self::assertInstanceOf(\SAML2_Response::class, $response);
        self::assertEquals([], $response->getAssertions());
    }

    public function testSetStatus()
    {
        $authResponse = new AuthnResponseBuilder();

        $code = "code";
        $subCode = "subcode";
        $message = "message";

        $authResponse->setStatus($code, $subCode, $message);
        $response = $authResponse->getResponse();

        self::assertEquals(
            [
                "Code" => $code,
                "SubCode" => $subCode,
                "Message" => $message
            ],
            $response->getStatus()
        );
    }

    public function testSetStatusWithDefaultValues()
    {
        $authResponse = new AuthnResponseBuilder();

        $code = "code";

        $authResponse->setStatus($code);
        $response = $authResponse->getResponse();

        self::assertEquals(
            [
                "Code" => $code,
                "SubCode" => null,
                "Message" => null
            ],
            $response->getStatus()
        );
    }

    public function testIssuer()
    {
        $authResponse = new AuthnResponseBuilder();
        $issuer = "issuer";


        $authResponse->setIssuer($issuer);
        $response = $authResponse->getResponse();
        self::assertEquals($issuer, $response->getIssuer());
    }

    public function testDestination()
    {
        $authResponse = new AuthnResponseBuilder();
        $destination = "destination";


        $authResponse->setDestination($destination);
        $response = $authResponse->getResponse();
        self::assertEquals($destination, $response->getDestination());
    }

    public function testInResponseTo()
    {
        $authResponse = new AuthnResponseBuilder();
        $inResponseTo = "inResponseTo";


        $authResponse->setInResponseTo($inResponseTo);
        $response = $authResponse->getResponse();
        self::assertEquals($inResponseTo, $response->getInResponseTo());
    }

    public function testRelayState()
    {
        $authResponse = new AuthnResponseBuilder();
        $relayState = "relayState";


        $authResponse->setRelayState($relayState);
        $response = $authResponse->getResponse();
        self::assertEquals($relayState, $response->getRelayState());
    }

    public function testAssertionBuilders()
    {
        $authResponse = new AuthnResponseBuilder();

        self::assertSame([], $authResponse->getAssertionBuilders());

        /** @var AssertionBuilder $assertionBuilder1 */
        $assertionBuilder1 = $this->createMock(AssertionBuilder::class);
        /** @var AssertionBuilder $assertionBuilder2 */
        $assertionBuilder2 = $this->createMock(AssertionBuilder::class);
        /** @var AssertionBuilder $assertionBuilder3 */
        $assertionBuilder3 = $this->createMock(AssertionBuilder::class);

        $authResponse->addAssertionBuilder($assertionBuilder1);

        self::assertSame([$assertionBuilder1], $authResponse->getAssertionBuilders());

        $authResponse->addAssertionBuilder($assertionBuilder1);

        self::assertSame([$assertionBuilder1], $authResponse->getAssertionBuilders());

        $authResponse->addAssertionBuilder($assertionBuilder2);

        self::assertSame([$assertionBuilder1, $assertionBuilder2], $authResponse->getAssertionBuilders());

        $authResponse->setAssertionBuilders([$assertionBuilder2, $assertionBuilder3]);
        self::assertSame([$assertionBuilder2, $assertionBuilder3], $authResponse->getAssertionBuilders());

        $authResponse->setAssertionBuilders([]);
        self::assertSame([], $authResponse->getAssertionBuilders());
    }

    public function testGetResponseWitAssertionBuildersWithoutSignatureKey()
    {
        $authResponse = new AuthnResponseBuilder();

        $assertionBuilder1 = $this->createMock(AssertionBuilder::class);

        $assertion1 = $this->createMock(\SAML2_Assertion::class);
        $assertionBuilder1->expects($this->once())
            ->method("getAssertion")
            ->willReturn($assertion1);
        $assertion1->expects($this->never())
            ->method("setSignatureKey");

        /** @var AssertionBuilder $assertionBuilder2 */
        $assertionBuilder2 = $this->createMock(AssertionBuilder::class);

        $assertion2 = $this->createMock(\SAML2_Assertion::class);
        $assertionBuilder2->expects($this->once())
        ->method("getAssertion")
        ->willReturn($assertion2);

        $assertion2->expects($this->never())
            ->method("setSignatureKey");

        $response = $authResponse->getResponse();
        self::assertInstanceOf(\SAML2_Response::class, $response);
        self::assertEquals([], $response->getAssertions());

        $authResponse->setAssertionBuilders([$assertionBuilder1, $assertionBuilder2]);


        $response = $authResponse->getResponse();
        self::assertInstanceOf(\SAML2_Response::class, $response);
        self::assertCount(2, $response->getAssertions());
        self::assertSame($assertion1, $response->getAssertions()[0]);
        self::assertSame($assertion2, $response->getAssertions()[1]);
    }
}