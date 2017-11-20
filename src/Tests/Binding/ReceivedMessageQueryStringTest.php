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

namespace AdactiveSas\Saml2BridgeBundle\Tests\Binding;


use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\InvalidReceivedMessageQueryStringException;
use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\ReceivedMessageQueryString;
use PHPUnit\Framework\TestCase;

class ReceivedMessageQueryStringTest extends TestCase
{
    protected $samlMessage = 'pVJNjxMxDP0ro9yn88FOtRu1lcpWiEoLVNvCgQtKE6eNlHGG2IHl35OZLmLZQy+crNh+z88vXpDq/SDXic/4CN8TEBdPvUeSU2EpUkQZFDmSqHogyVru1x8eZDur5RADBx28eAG5jlBEENkFFMV2sxTfzO0dmKa11namPuoc39hba+fqpqlbM6/b5mZ+1LWtj6L4ApEycikyUYYTJdgisULOqbrpyqYt67tD28iulV33VRSbvI1DxRPqzDyQrCrAk0OYUYpWB4QnnqGvVN4fkJ2emitnhoocnjyU5E5YjnrXf6TfB6TUQ9xD/OE0fH58+EueHbHOv2Yn1w8eRneqPpiU68M5DxjfdIltqTRNWQNWJc8lDaLYPfv71qHJaq5be7w0kXx/OOzK3af9QawWI7ecrIqr/9HYAyujWL2SuKheDlhcbuljlrbd7IJ3+lfxLsRe8XXlY8aZ0k6tkqNCcvkzsuXeh5/3ERTDUnBMIKrVZeS/F7v6DQ==';

    public function testParseUnSignedQueryString()
    {
        $samlMessage = $this->samlMessage;
        $params = ['SAMLRequest' => $samlMessage];
        $query = ReceivedMessageQueryString::parse($params);

        $stringXml = <<<EOT
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_d89ed12fff5d0bcfff3f8ff6a4102d602146bc0f0b" Version="2.0" IssueInstant="2015-12-09T21:52:55Z" Destination="https://engine.surfconext.nl/authentication/idp/single-sign-on" AssertionConsumerServiceURL="https://profile.surfconext.nl/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://profile.surfconext.nl/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/></samlp:AuthnRequest>
EOT;

        self::assertEquals($samlMessage, $query->getSamlMessage());
        self::assertEquals($stringXml, $query->getDecodedSamlRequest());
        self::assertFalse($query->isSigned());
        self::assertEquals(null, $query->getSignature());
        self::assertEquals(null, $query->getSignatureAlgorithm());
        self::assertFalse($query->hasRelayState());
        self::assertEquals(null, $query->getRelayState());
    }

    public function testParseUnSignedQueryStringWithRelayState()
    {
        $samlMessage = $this->samlMessage;
        $params = ['SAMLRequest' => $samlMessage, 'RelayState' => urldecode('https%3A%2F%2Fprofile.surfconext.nl%2F')];
        $query = ReceivedMessageQueryString::parse($params);

        $stringXml = <<<EOT
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_d89ed12fff5d0bcfff3f8ff6a4102d602146bc0f0b" Version="2.0" IssueInstant="2015-12-09T21:52:55Z" Destination="https://engine.surfconext.nl/authentication/idp/single-sign-on" AssertionConsumerServiceURL="https://profile.surfconext.nl/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://profile.surfconext.nl/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/></samlp:AuthnRequest>
EOT;

        self::assertEquals($samlMessage, $query->getSamlMessage());
        self::assertEquals($stringXml, $query->getDecodedSamlRequest());
        self::assertFalse($query->isSigned());
        self::assertEquals(null, $query->getSignature());
        self::assertEquals(null, $query->getSignatureAlgorithm());
        self::assertTrue($query->hasRelayState());
        self::assertEquals("https://profile.surfconext.nl/", $query->getRelayState());
    }

    public function testParseSignedQueryString()
    {
        $samlMessage = $this->samlMessage;
        $signature = "AFSvUfNsWzJikhTJ3sY5vEh7PyLQTGL4XOJg2WQG3WGmbPRZKJBNigX01U/5GpmSjU1X53QOs0qp2PmputKyycg8eBZi78CkmkFxRU0Wj37OHAg1r1EQn/yGSJuRKPHmx0LPbX3TxiDe01jZWAmIhsD6R7w6YaGU8/GDEOFcnXDTSuRu/R6LKNaaib4FaH9TxKkuGJR79otMBsOjiK1hA4A/oLuG0jeQrN0M7J8R/JF7i3iZzmzbEzSvmRuDl4Gt05VJ55XcJyAW8hBCuJrwgeZps71EdJaWpLVjF+oAhactWV+Ak5gpm4RSpew7Pw0VJs65hLCJCoywqlsxq12acw==";
        $signAlg = "http%3A%2F%2Fwww.w3.org%2F2000%2F09%2Fxmldsig%23rsa-sha1";
        $relayState = "https%3A%2F%2Fprofile.surfconext.nl%2F";

        $params = [
            'SAMLRequest' => $samlMessage,
            'RelayState' => urldecode($relayState),
            'SigAlg' => urldecode($signAlg),
            'Signature' => $signature,
        ];

        $query = ReceivedMessageQueryString::parse($params);

        $stringXml = <<<EOT
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_d89ed12fff5d0bcfff3f8ff6a4102d602146bc0f0b" Version="2.0" IssueInstant="2015-12-09T21:52:55Z" Destination="https://engine.surfconext.nl/authentication/idp/single-sign-on" AssertionConsumerServiceURL="https://profile.surfconext.nl/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://profile.surfconext.nl/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/></samlp:AuthnRequest>
EOT;
        $decodedSignatureAlg = urldecode($signAlg);
        $decodedRelayState = urldecode($relayState);

        self::assertEquals($samlMessage, $query->getSamlMessage());
        self::assertEquals($stringXml, $query->getDecodedSamlRequest());
        self::assertTrue($query->isSigned());
        self::assertEquals($signature, $query->getSignature());
        self::assertEquals(base64_decode($signature), $query->getDecodedSignature());
        self::assertEquals(
            "SAMLRequest=".urlencode($samlMessage).
            "&RelayState=".urlencode($decodedRelayState).
            "&SigAlg=".urlencode($decodedSignatureAlg),
            $query->getSignedQueryString()
        );
        self::assertEquals($decodedSignatureAlg, $query->getSignatureAlgorithm());
        self::assertTrue($query->hasRelayState());
        self::assertEquals($decodedRelayState, $query->getRelayState());
    }

    public function testParseUnsignedQueryStringWithAdditionalParameterWithoutValue(){
        $samlMessage = $this->samlMessage;
        $qs = "SAMLRequest=$samlMessage&bool";
        $params = [
            'SAMLRequest' => $samlMessage,
            'bool' => null,
        ];
        $query = ReceivedMessageQueryString::parse($params);

        $stringXml = <<<EOT
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_d89ed12fff5d0bcfff3f8ff6a4102d602146bc0f0b" Version="2.0" IssueInstant="2015-12-09T21:52:55Z" Destination="https://engine.surfconext.nl/authentication/idp/single-sign-on" AssertionConsumerServiceURL="https://profile.surfconext.nl/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://profile.surfconext.nl/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/></samlp:AuthnRequest>
EOT;

        self::assertEquals($samlMessage, $query->getSamlMessage());
        self::assertEquals($stringXml, $query->getDecodedSamlRequest());
        self::assertFalse($query->isSigned());
        self::assertEquals(null, $query->getSignature());
        self::assertEquals(null, $query->getSignatureAlgorithm());
        self::assertFalse($query->hasRelayState());
        self::assertEquals(null, $query->getRelayState());
    }

    public function testParseUnsignedQueryStringWithAdditionalParameter(){
        $samlMessage = $this->samlMessage;
        $params = [
            'SAMLRequest' => $samlMessage,
            'value' => 'yo',
        ];
        $query = ReceivedMessageQueryString::parse($params);

        $stringXml = <<<EOT
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_d89ed12fff5d0bcfff3f8ff6a4102d602146bc0f0b" Version="2.0" IssueInstant="2015-12-09T21:52:55Z" Destination="https://engine.surfconext.nl/authentication/idp/single-sign-on" AssertionConsumerServiceURL="https://profile.surfconext.nl/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://profile.surfconext.nl/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/></samlp:AuthnRequest>
EOT;

        self::assertEquals($samlMessage, $query->getSamlMessage());
        self::assertEquals($stringXml, $query->getDecodedSamlRequest());
        self::assertFalse($query->isSigned());
        self::assertEquals(null, $query->getSignature());
        self::assertEquals(null, $query->getSignatureAlgorithm());
        self::assertFalse($query->hasRelayState());
        self::assertEquals(null, $query->getRelayState());
    }

    /**
     * @expectedException AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\InvalidReceivedMessageQueryStringException
     * @expectedExceptionMessage Invalid ReceivedMessage query params ([]): neither parameter "SAMLRequest" nor parameter "SAMLResponse" found
     */
    public function testParseEmptyQueryString(){
        ReceivedMessageQueryString::parse([]);
    }

    /**
     * @expectedException AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\InvalidReceivedMessageQueryStringException
     * @expectedExceptionMessage Invalid ReceivedMessage query ("{"SAMLRequest":"yo","SAMLResponse":"yop"}"): message must be either a request or a response but found parameters "SAMLRequest" and "SAMLResponse"
     */
    public function testParseInvalidQueryStringWithBothRequestAndResponseAttribute(){
        ReceivedMessageQueryString::parse(['SAMLRequest' => 'yo', 'SAMLResponse' => 'yop']);
    }

    public function testParseSignedQueryStringWithoutSignatureAlgorithm()
    {
        $samlMessage = $this->samlMessage;
        $signature = 'AFSvUfNsWzJikhTJ3sY5vEh7PyLQTGL4XOJg2WQG3WGmbPRZKJBNigX01U/5GpmSjU1X53QOs0qp2PmputKyycg8eBZi78CkmkFxRU0Wj37OHAg1r1EQn/yGSJuRKPHmx0LPbX3TxiDe01jZWAmIhsD6R7w6YaGU8/GDEOFcnXDTSuRu/R6LKNaaib4FaH9TxKkuGJR79otMBsOjiK1hA4A/oLuG0jeQrN0M7J8R/JF7i3iZzmzbEzSvmRuDl4Gt05VJ55XcJyAW8hBCuJrwgeZps71EdJaWpLVjF+oAhactWV+Ak5gpm4RSpew7Pw0VJs65hLCJCoywqlsxq12acw==';
        $relayState = "https%3A%2F%2Fprofile.surfconext.nl%2F";
        $params = [
            'SAMLRequest' => $samlMessage,
            'RelayState' => urldecode($relayState),
            'Signature' => $signature,
        ];

        self::expectException(InvalidReceivedMessageQueryStringException::class);
        self::expectExceptionCode(0);
        self::expectExceptionMessage(sprintf(
            'Invalid ReceivedMessage query string ("%s") contains a signature but not a signature algorithm',
            json_encode($params)
        ));

        ReceivedMessageQueryString::parse($params);
    }

    public function testParseSignedQueryStringWithoutSignature()
    {
        $samlMessage = $this->samlMessage;
        $signAlg = "http%3A%2F%2Fwww.w3.org%2F2000%2F09%2Fxmldsig%23rsa-sha1";
        $relayState = "https%3A%2F%2Fprofile.surfconext.nl%2F";

        $params = [
            'SAMLRequest' => $samlMessage,
            'RelayState' => urldecode($relayState),
            'SigAlg' => urldecode($signAlg),
        ];

        self::expectException(InvalidReceivedMessageQueryStringException::class);
        self::expectExceptionCode(0);
        self::expectExceptionMessage(sprintf(
            'Invalid ReceivedMessage query string ("%s") contains a signature algorithm but not a signature',
            json_encode($params)
        ));

        ReceivedMessageQueryString::parse($params);
    }

    protected function buildInvalidReceivedMessageQueryStringException($given)
    {
        return new InvalidReceivedMessageQueryStringException(sprintf(
            'Could not parse query string: expected a non-empty string, %s given',
            $given
        ));
    }
}
