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
    /**
     * @dataProvider providerParseNonStringQuery
     */
    public function testParseNonStringQuery($qs, InvalidReceivedMessageQueryStringException $exception)
    {
        self::expectException(get_class($exception));
        self::expectExceptionCode($exception->getCode());
        self::expectExceptionMessage($exception->getMessage());

        ReceivedMessageQueryString::parse($qs);
    }

    public function providerParseNonStringQuery()
    {
        return [
            [null, $this->buildInvalidReceivedMessageQueryStringException("NULL")],
            [new \stdClass, $this->buildInvalidReceivedMessageQueryStringException("stdClass")],
            [1, $this->buildInvalidReceivedMessageQueryStringException("integer")],
        ];
    }

    public function testParseUnSignedQueryString()
    {
        $samlMessage = "pVJNjxMxDP0ro9yn88FOtRu1lcpWiEoLVNvCgQtKE6eNlHGG2IHl35OZLmLZQy%2BcrNh%2Bz88vXpDq%2FSDXic%2F4CN8TEBdPvUeSU2EpUkQZFDmSqHogyVru1x8eZDur5RADBx28eAG5jlBEENkFFMV2sxTfzO0dmKa11namPuoc39hba%2BfqpqlbM6%2Fb5mZ%2B1LWtj6L4ApEycikyUYYTJdgisULOqbrpyqYt67tD28iulV33VRSbvI1DxRPqzDyQrCrAk0OYUYpWB4QnnqGvVN4fkJ2emitnhoocnjyU5E5YjnrXf6TfB6TUQ9xD%2FOE0fH58%2BEueHbHOv2Yn1w8eRneqPpiU68M5DxjfdIltqTRNWQNWJc8lDaLYPfv71qHJaq5be7w0kXx%2FOOzK3af9QawWI7ecrIqr%2F9HYAyujWL2SuKheDlhcbuljlrbd7IJ3%2BlfxLsRe8XXlY8aZ0k6tkqNCcvkzsuXeh5%2F3ERTDUnBMIKrVZeS%2FF7v6DQ%3D%3D";
        $qs = "SAMLRequest=$samlMessage";
        $query = ReceivedMessageQueryString::parse($qs);

        $stringXml = <<<EOT
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_d89ed12fff5d0bcfff3f8ff6a4102d602146bc0f0b" Version="2.0" IssueInstant="2015-12-09T21:52:55Z" Destination="https://engine.surfconext.nl/authentication/idp/single-sign-on" AssertionConsumerServiceURL="https://profile.surfconext.nl/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://profile.surfconext.nl/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/></samlp:AuthnRequest>
EOT;

        self::assertEquals(urldecode($samlMessage), $query->getSamlMessage());
        self::assertEquals($stringXml, $query->getDecodedSamlRequest());
        self::assertFalse($query->isSigned());
        self::assertEquals(null, $query->getSignature());
        self::assertEquals(null, $query->getSignatureAlgorithm());
        self::assertFalse($query->hasRelayState());
        self::assertEquals(null, $query->getRelayState());
    }

    public function testParseUnSignedQueryStringWithRelayState()
    {
        $samlMessage = "pVJNjxMxDP0ro9yn88FOtRu1lcpWiEoLVNvCgQtKE6eNlHGG2IHl35OZLmLZQy%2BcrNh%2Bz88vXpDq%2FSDXic%2F4CN8TEBdPvUeSU2EpUkQZFDmSqHogyVru1x8eZDur5RADBx28eAG5jlBEENkFFMV2sxTfzO0dmKa11namPuoc39hba%2BfqpqlbM6%2Fb5mZ%2B1LWtj6L4ApEycikyUYYTJdgisULOqbrpyqYt67tD28iulV33VRSbvI1DxRPqzDyQrCrAk0OYUYpWB4QnnqGvVN4fkJ2emitnhoocnjyU5E5YjnrXf6TfB6TUQ9xD%2FOE0fH58%2BEueHbHOv2Yn1w8eRneqPpiU68M5DxjfdIltqTRNWQNWJc8lDaLYPfv71qHJaq5be7w0kXx%2FOOzK3af9QawWI7ecrIqr%2F9HYAyujWL2SuKheDlhcbuljlrbd7IJ3%2BlfxLsRe8XXlY8aZ0k6tkqNCcvkzsuXeh5%2F3ERTDUnBMIKrVZeS%2FF7v6DQ%3D%3D";
        $qs = "SAMLRequest=$samlMessage&RelayState=https%3A%2F%2Fprofile.surfconext.nl%2F";

        $query = ReceivedMessageQueryString::parse($qs);

        $stringXml = <<<EOT
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_d89ed12fff5d0bcfff3f8ff6a4102d602146bc0f0b" Version="2.0" IssueInstant="2015-12-09T21:52:55Z" Destination="https://engine.surfconext.nl/authentication/idp/single-sign-on" AssertionConsumerServiceURL="https://profile.surfconext.nl/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://profile.surfconext.nl/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/></samlp:AuthnRequest>
EOT;

        self::assertEquals(urldecode($samlMessage), $query->getSamlMessage());
        self::assertEquals($stringXml, $query->getDecodedSamlRequest());
        self::assertFalse($query->isSigned());
        self::assertEquals(null, $query->getSignature());
        self::assertEquals(null, $query->getSignatureAlgorithm());
        self::assertTrue($query->hasRelayState());
        self::assertEquals("https://profile.surfconext.nl/", $query->getRelayState());
    }

    public function testParseSignedQueryString()
    {
        $samlMessage = "pVJNjxMxDP0ro9yn88FOtRu1lcpWiEoLVNvCgQtKE6eNlHGG2IHl35OZLmLZQy%2BcrNh%2Bz88vXpDq%2FSDXic%2F4CN8TEBdPvUeSU2EpUkQZFDmSqHogyVru1x8eZDur5RADBx28eAG5jlBEENkFFMV2sxTfzO0dmKa11namPuoc39hba%2BfqpqlbM6%2Fb5mZ%2B1LWtj6L4ApEycikyUYYTJdgisULOqbrpyqYt67tD28iulV33VRSbvI1DxRPqzDyQrCrAk0OYUYpWB4QnnqGvVN4fkJ2emitnhoocnjyU5E5YjnrXf6TfB6TUQ9xD%2FOE0fH58%2BEueHbHOv2Yn1w8eRneqPpiU68M5DxjfdIltqTRNWQNWJc8lDaLYPfv71qHJaq5be7w0kXx%2FOOzK3af9QawWI7ecrIqr%2F9HYAyujWL2SuKheDlhcbuljlrbd7IJ3%2BlfxLsRe8XXlY8aZ0k6tkqNCcvkzsuXeh5%2F3ERTDUnBMIKrVZeS%2FF7v6DQ%3D%3D";
        $signature = urlencode("AFSvUfNsWzJikhTJ3sY5vEh7PyLQTGL4XOJg2WQG3WGmbPRZKJBNigX01U/5GpmSjU1X53QOs0qp2PmputKyycg8eBZi78CkmkFxRU0Wj37OHAg1r1EQn/yGSJuRKPHmx0LPbX3TxiDe01jZWAmIhsD6R7w6YaGU8/GDEOFcnXDTSuRu/R6LKNaaib4FaH9TxKkuGJR79otMBsOjiK1hA4A/oLuG0jeQrN0M7J8R/JF7i3iZzmzbEzSvmRuDl4Gt05VJ55XcJyAW8hBCuJrwgeZps71EdJaWpLVjF+oAhactWV+Ak5gpm4RSpew7Pw0VJs65hLCJCoywqlsxq12acw==");
        $signAlg = "http%3A%2F%2Fwww.w3.org%2F2000%2F09%2Fxmldsig%23rsa-sha1";
        $relayState = "https%3A%2F%2Fprofile.surfconext.nl%2F";

        $qs = "SAMLRequest=$samlMessage&RelayState=$relayState&SigAlg=$signAlg&Signature=$signature";

        $query = ReceivedMessageQueryString::parse($qs);

        $stringXml = <<<EOT
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_d89ed12fff5d0bcfff3f8ff6a4102d602146bc0f0b" Version="2.0" IssueInstant="2015-12-09T21:52:55Z" Destination="https://engine.surfconext.nl/authentication/idp/single-sign-on" AssertionConsumerServiceURL="https://profile.surfconext.nl/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://profile.surfconext.nl/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/></samlp:AuthnRequest>
EOT;

        $decodedSamlMessage = urldecode($samlMessage);
        $decodedSignature = urldecode($signature);
        $decodedSignatureAlg = urldecode($signAlg);
        $decodedRelayState = urldecode($relayState);

        self::assertEquals($decodedSamlMessage, $query->getSamlMessage());
        self::assertEquals($stringXml, $query->getDecodedSamlRequest());
        self::assertTrue($query->isSigned());
        self::assertEquals($decodedSignature, $query->getSignature());
        self::assertEquals(base64_decode($decodedSignature), $query->getDecodedSignature());
        self::assertEquals("SAMLRequest=$samlMessage&RelayState=$relayState&SigAlg=$signAlg", $query->getSignedQueryString());
        self::assertEquals($decodedSignatureAlg, $query->getSignatureAlgorithm());
        self::assertTrue($query->hasRelayState());
        self::assertEquals($decodedRelayState, $query->getRelayState());
    }

    public function testParseUnsignedQueryStringWithAdditionalParameterWithoutValue(){
        $samlMessage = "pVJNjxMxDP0ro9yn88FOtRu1lcpWiEoLVNvCgQtKE6eNlHGG2IHl35OZLmLZQy%2BcrNh%2Bz88vXpDq%2FSDXic%2F4CN8TEBdPvUeSU2EpUkQZFDmSqHogyVru1x8eZDur5RADBx28eAG5jlBEENkFFMV2sxTfzO0dmKa11namPuoc39hba%2BfqpqlbM6%2Fb5mZ%2B1LWtj6L4ApEycikyUYYTJdgisULOqbrpyqYt67tD28iulV33VRSbvI1DxRPqzDyQrCrAk0OYUYpWB4QnnqGvVN4fkJ2emitnhoocnjyU5E5YjnrXf6TfB6TUQ9xD%2FOE0fH58%2BEueHbHOv2Yn1w8eRneqPpiU68M5DxjfdIltqTRNWQNWJc8lDaLYPfv71qHJaq5be7w0kXx%2FOOzK3af9QawWI7ecrIqr%2F9HYAyujWL2SuKheDlhcbuljlrbd7IJ3%2BlfxLsRe8XXlY8aZ0k6tkqNCcvkzsuXeh5%2F3ERTDUnBMIKrVZeS%2FF7v6DQ%3D%3D";
        $qs = "SAMLRequest=$samlMessage&bool";
        $query = ReceivedMessageQueryString::parse($qs);

        $stringXml = <<<EOT
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_d89ed12fff5d0bcfff3f8ff6a4102d602146bc0f0b" Version="2.0" IssueInstant="2015-12-09T21:52:55Z" Destination="https://engine.surfconext.nl/authentication/idp/single-sign-on" AssertionConsumerServiceURL="https://profile.surfconext.nl/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://profile.surfconext.nl/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/></samlp:AuthnRequest>
EOT;

        self::assertEquals(urldecode($samlMessage), $query->getSamlMessage());
        self::assertEquals($stringXml, $query->getDecodedSamlRequest());
        self::assertFalse($query->isSigned());
        self::assertEquals(null, $query->getSignature());
        self::assertEquals(null, $query->getSignatureAlgorithm());
        self::assertFalse($query->hasRelayState());
        self::assertEquals(null, $query->getRelayState());
    }

    public function testParseUnsignedQueryStringWithAdditionalParameter(){
        $samlMessage = "pVJNjxMxDP0ro9yn88FOtRu1lcpWiEoLVNvCgQtKE6eNlHGG2IHl35OZLmLZQy%2BcrNh%2Bz88vXpDq%2FSDXic%2F4CN8TEBdPvUeSU2EpUkQZFDmSqHogyVru1x8eZDur5RADBx28eAG5jlBEENkFFMV2sxTfzO0dmKa11namPuoc39hba%2BfqpqlbM6%2Fb5mZ%2B1LWtj6L4ApEycikyUYYTJdgisULOqbrpyqYt67tD28iulV33VRSbvI1DxRPqzDyQrCrAk0OYUYpWB4QnnqGvVN4fkJ2emitnhoocnjyU5E5YjnrXf6TfB6TUQ9xD%2FOE0fH58%2BEueHbHOv2Yn1w8eRneqPpiU68M5DxjfdIltqTRNWQNWJc8lDaLYPfv71qHJaq5be7w0kXx%2FOOzK3af9QawWI7ecrIqr%2F9HYAyujWL2SuKheDlhcbuljlrbd7IJ3%2BlfxLsRe8XXlY8aZ0k6tkqNCcvkzsuXeh5%2F3ERTDUnBMIKrVZeS%2FF7v6DQ%3D%3D";
        $qs = "SAMLRequest=$samlMessage&value=yo";
        $query = ReceivedMessageQueryString::parse($qs);

        $stringXml = <<<EOT
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="_d89ed12fff5d0bcfff3f8ff6a4102d602146bc0f0b" Version="2.0" IssueInstant="2015-12-09T21:52:55Z" Destination="https://engine.surfconext.nl/authentication/idp/single-sign-on" AssertionConsumerServiceURL="https://profile.surfconext.nl/simplesaml/module.php/saml/sp/saml2-acs.php/default-sp" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"><saml:Issuer>https://profile.surfconext.nl/simplesaml/module.php/saml/sp/metadata.php/default-sp</saml:Issuer><samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:2.0:nameid-format:transient" AllowCreate="true"/></samlp:AuthnRequest>
EOT;

        self::assertEquals(urldecode($samlMessage), $query->getSamlMessage());
        self::assertEquals($stringXml, $query->getDecodedSamlRequest());
        self::assertFalse($query->isSigned());
        self::assertEquals(null, $query->getSignature());
        self::assertEquals(null, $query->getSignatureAlgorithm());
        self::assertFalse($query->hasRelayState());
        self::assertEquals(null, $query->getRelayState());
    }

    /**
     * @param $parameterKey
     * @dataProvider provideParseUnsignedQueryStringWithDuplicatedSamlParameterKey
     */
    public function testParseUnsignedQueryStringWithDuplicatedSamlParameterKey($parameterKey){
        $samlMessage = "pVJNjxMxDP0ro9yn88FOtRu1lcpWiEoLVNvCgQtKE6eNlHGG2IHl35OZLmLZQy%2BcrNh%2Bz88vXpDq%2FSDXic%2F4CN8TEBdPvUeSU2EpUkQZFDmSqHogyVru1x8eZDur5RADBx28eAG5jlBEENkFFMV2sxTfzO0dmKa11namPuoc39hba%2BfqpqlbM6%2Fb5mZ%2B1LWtj6L4ApEycikyUYYTJdgisULOqbrpyqYt67tD28iulV33VRSbvI1DxRPqzDyQrCrAk0OYUYpWB4QnnqGvVN4fkJ2emitnhoocnjyU5E5YjnrXf6TfB6TUQ9xD%2FOE0fH58%2BEueHbHOv2Yn1w8eRneqPpiU68M5DxjfdIltqTRNWQNWJc8lDaLYPfv71qHJaq5be7w0kXx%2FOOzK3af9QawWI7ecrIqr%2F9HYAyujWL2SuKheDlhcbuljlrbd7IJ3%2BlfxLsRe8XXlY8aZ0k6tkqNCcvkzsuXeh5%2F3ERTDUnBMIKrVZeS%2FF7v6DQ%3D%3D";
        $signature = urlencode("AFSvUfNsWzJikhTJ3sY5vEh7PyLQTGL4XOJg2WQG3WGmbPRZKJBNigX01U/5GpmSjU1X53QOs0qp2PmputKyycg8eBZi78CkmkFxRU0Wj37OHAg1r1EQn/yGSJuRKPHmx0LPbX3TxiDe01jZWAmIhsD6R7w6YaGU8/GDEOFcnXDTSuRu/R6LKNaaib4FaH9TxKkuGJR79otMBsOjiK1hA4A/oLuG0jeQrN0M7J8R/JF7i3iZzmzbEzSvmRuDl4Gt05VJ55XcJyAW8hBCuJrwgeZps71EdJaWpLVjF+oAhactWV+Ak5gpm4RSpew7Pw0VJs65hLCJCoywqlsxq12acw==");
        $signAlg = "http%3A%2F%2Fwww.w3.org%2F2000%2F09%2Fxmldsig%23rsa-sha1";
        $relayState = "https%3A%2F%2Fprofile.surfconext.nl%2F";

        $qs = "SAMLRequest=$samlMessage&RelayState=$relayState&SigAlg=$signAlg&Signature=$signature&$parameterKey=yo";

        self::expectException(InvalidReceivedMessageQueryStringException::class);
        self::expectExceptionCode(0);
        self::expectExceptionMessage(sprintf(
            'Invalid ReceivedMessage query string ("%s"): parameter "%s" already present',
            $qs,
            $parameterKey
        ));

        ReceivedMessageQueryString::parse($qs);
    }

    /**
     * @expectedException AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\InvalidReceivedMessageQueryStringException
     * @expectedExceptionMessage Invalid ReceivedMessage query string (""): neither parameter "SAMLRequest" nor parameter "SAMLResponse" found
     */
    public function testParseEmptyQueryString(){
        ReceivedMessageQueryString::parse("?");
    }

    /**
     * @expectedException AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\InvalidReceivedMessageQueryStringException
     * @expectedExceptionMessage Invalid ReceivedMessage query string ("SAMLRequest=yo&SAMLResponse=yop"): message must be either a request or a response but found parameters "SAMLRequest" and "SAMLResponse"
     */
    public function testParseInvalidQueryStringWithBothRequestAndResponseAttribute(){
        ReceivedMessageQueryString::parse("?SAMLRequest=yo&SAMLResponse=yop");
    }

    public function testParseSignedQueryStringWithoutSignatureAlgorithm()
    {
        $samlMessage = "pVJNjxMxDP0ro9yn88FOtRu1lcpWiEoLVNvCgQtKE6eNlHGG2IHl35OZLmLZQy%2BcrNh%2Bz88vXpDq%2FSDXic%2F4CN8TEBdPvUeSU2EpUkQZFDmSqHogyVru1x8eZDur5RADBx28eAG5jlBEENkFFMV2sxTfzO0dmKa11namPuoc39hba%2BfqpqlbM6%2Fb5mZ%2B1LWtj6L4ApEycikyUYYTJdgisULOqbrpyqYt67tD28iulV33VRSbvI1DxRPqzDyQrCrAk0OYUYpWB4QnnqGvVN4fkJ2emitnhoocnjyU5E5YjnrXf6TfB6TUQ9xD%2FOE0fH58%2BEueHbHOv2Yn1w8eRneqPpiU68M5DxjfdIltqTRNWQNWJc8lDaLYPfv71qHJaq5be7w0kXx%2FOOzK3af9QawWI7ecrIqr%2F9HYAyujWL2SuKheDlhcbuljlrbd7IJ3%2BlfxLsRe8XXlY8aZ0k6tkqNCcvkzsuXeh5%2F3ERTDUnBMIKrVZeS%2FF7v6DQ%3D%3D";
        $signature = urlencode("AFSvUfNsWzJikhTJ3sY5vEh7PyLQTGL4XOJg2WQG3WGmbPRZKJBNigX01U/5GpmSjU1X53QOs0qp2PmputKyycg8eBZi78CkmkFxRU0Wj37OHAg1r1EQn/yGSJuRKPHmx0LPbX3TxiDe01jZWAmIhsD6R7w6YaGU8/GDEOFcnXDTSuRu/R6LKNaaib4FaH9TxKkuGJR79otMBsOjiK1hA4A/oLuG0jeQrN0M7J8R/JF7i3iZzmzbEzSvmRuDl4Gt05VJ55XcJyAW8hBCuJrwgeZps71EdJaWpLVjF+oAhactWV+Ak5gpm4RSpew7Pw0VJs65hLCJCoywqlsxq12acw==");
        $relayState = "https%3A%2F%2Fprofile.surfconext.nl%2F";

        $qs = "SAMLRequest=$samlMessage&RelayState=$relayState&Signature=$signature";

        self::expectException(InvalidReceivedMessageQueryStringException::class);
        self::expectExceptionCode(0);
        self::expectExceptionMessage(sprintf(
            'Invalid ReceivedMessage query string ("%s") contains a signature but not a signature algorithm',
            $qs
        ));

        ReceivedMessageQueryString::parse($qs);
    }

    public function testParseSignedQueryStringWithoutSignature()
    {
        $samlMessage = "pVJNjxMxDP0ro9yn88FOtRu1lcpWiEoLVNvCgQtKE6eNlHGG2IHl35OZLmLZQy%2BcrNh%2Bz88vXpDq%2FSDXic%2F4CN8TEBdPvUeSU2EpUkQZFDmSqHogyVru1x8eZDur5RADBx28eAG5jlBEENkFFMV2sxTfzO0dmKa11namPuoc39hba%2BfqpqlbM6%2Fb5mZ%2B1LWtj6L4ApEycikyUYYTJdgisULOqbrpyqYt67tD28iulV33VRSbvI1DxRPqzDyQrCrAk0OYUYpWB4QnnqGvVN4fkJ2emitnhoocnjyU5E5YjnrXf6TfB6TUQ9xD%2FOE0fH58%2BEueHbHOv2Yn1w8eRneqPpiU68M5DxjfdIltqTRNWQNWJc8lDaLYPfv71qHJaq5be7w0kXx%2FOOzK3af9QawWI7ecrIqr%2F9HYAyujWL2SuKheDlhcbuljlrbd7IJ3%2BlfxLsRe8XXlY8aZ0k6tkqNCcvkzsuXeh5%2F3ERTDUnBMIKrVZeS%2FF7v6DQ%3D%3D";
        $signAlg = "http%3A%2F%2Fwww.w3.org%2F2000%2F09%2Fxmldsig%23rsa-sha1";
        $relayState = "https%3A%2F%2Fprofile.surfconext.nl%2F";

        $qs = "SAMLRequest=$samlMessage&RelayState=$relayState&SigAlg=$signAlg";

        self::expectException(InvalidReceivedMessageQueryStringException::class);
        self::expectExceptionCode(0);
        self::expectExceptionMessage(sprintf(
            'Invalid ReceivedMessage query string ("%s") contains a signature algorithm but not a signature',
            $qs
        ));

        ReceivedMessageQueryString::parse($qs);
    }

    public function provideParseUnsignedQueryStringWithDuplicatedSamlParameterKey(){
        return [
            ["SAMLRequest"],
            ["Signature"],
            ["SigAlg"],
            ["RelayState"],
        ];
    }

    protected function buildInvalidReceivedMessageQueryStringException($given)
    {
        return new InvalidReceivedMessageQueryStringException(sprintf(
            'Could not parse query string: expected a non-empty string, %s given',
            $given
        ));
    }
}