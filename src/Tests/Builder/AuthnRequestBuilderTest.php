<?php

/**
 * Copyright 2017 Adactive SAS
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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