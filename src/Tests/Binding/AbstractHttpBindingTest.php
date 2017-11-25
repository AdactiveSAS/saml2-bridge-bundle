<?php
/**
 * Created by PhpStorm.
 * User: moroine
 * Date: 25/11/17
 * Time: 3:36 PM
 */

namespace AdactiveSas\Saml2BridgeBundle\Tests\Binding;

use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\AbstractHttpBinding;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;

class AbstractHttpBindingTest extends TestCase
{
    public function testGetSignedRequest(){
        $stub = $this->getMockForAbstractClass(AbstractHttpBinding::class);

        $response = $this->createMock(Response::class);
        $destination = 'https://service-provider.com/metadata';
        $rawDocument = <<<DOCUMENT
<samlp:AuthnRequest xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol" xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion" ID="ONELOGIN_809707f0030a5d00620c9d9df97f627afe9dcc24" Version="2.0" ProviderName="SP test" IssueInstant="2014-07-16T23:52:45Z" Destination="http://idp.example.com/SSOService.php" ProtocolBinding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" AssertionConsumerServiceURL="http://sp.example.com/demo1/index.php?acs">
  <saml:Issuer>http://sp.example.com/demo1/metadata.php</saml:Issuer>
  <samlp:NameIDPolicy Format="urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress" AllowCreate="true"/>
  <samlp:RequestedAuthnContext Comparison="exact">
    <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport</saml:AuthnContextClassRef>
  </samlp:RequestedAuthnContext>
</samlp:AuthnRequest>
DOCUMENT;
        $document = new \DOMDocument();
        $document->preserveWhiteSpace = false;
        $this->assertTrue($document->loadXML($rawDocument));
        $encodedDocument = <<<DOCUMENT
fVNNj9owFLzvr4h8J3GyQBoLgij0A4lCRLI99FK59kuxFNup7bD039fJwopKu5ws+c2bN+N5ni3OsglOYKzQao7iEKNF/jCzVDYtWXbuqA7wpwPrAo9TlgyFOeqMIppaYYmiEixxjJTLb1uShJi0RjvNdINuWu53UGvBOC8ABZv1HO13n7b7L5vdzw84S3FaY/yI6YRjPE0wy3jG6yytp0lKa8g4Y8kYBd+vBjwdCgqjT4KD2flJc1QWgfMGPLe1HWyUdVQ5j8TxeITTUTytkkcySch48gMFa48UirqB7OhcS6JI8DaEM5VtAyHTMirLfQnmJBiE7bEdxg2GPwrFhfp93+uvF5AlX6uqGBX7skLB8up/pZXtJJgL/dNh+yrC/q+Bg9Rx5Lng3ItYUGZRPsRGBpsmv9cnwVFOHe1bZ9Ft1yX5/uU260I3gv0NPmsjqXvfVhzGw43go3qAEpBUNEvODVjr7TWNfl4ZoM6n4UwHKLrOuSwX8GHVvH0HZxestGypEbbPwItn7mrtFrVq/NocoM7vbhYjrMf568Ifz9rwPi1gfmRlqLKtNu7yAm+Sv9TeEfpavf0o+cM/
DOCUMENT;

        $relayState = 'relayStateData';
        $key = $this->createMock(\XMLSecurityKey::class);

        $request = $this->createMock(\SAML2_Request::class);
        $request->method('getDestination')->willReturn($destination);
        $request->method('getRelayState')->willReturn($relayState);
        $request->method('getSignatureKey')->willReturn($key);
        $request->method('toUnsignedXML')->willReturn($document->documentElement);

        $stub->expects($this->once())
            ->method('buildRequest')
            ->with($destination, $encodedDocument, $relayState, $key)
            ->willReturn($response);

        $this->assertSame($response, $stub->getSignedRequest($request));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Invalid destination
     */
    public function testGetSignedRequestWithInvalidDestination(){
        $stub = $this->getMockForAbstractClass(AbstractHttpBinding::class);

        $request = $this->createMock(\SAML2_Request::class);
        $request->method('getDestination')->willReturn(null);

        $stub->getSignedRequest($request);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Signature key is required
     */
    public function testGetSignedRequestWithInvalidSignatureKey(){
        $stub = $this->getMockForAbstractClass(AbstractHttpBinding::class);

        $request = $this->createMock(\SAML2_Request::class);
        $request->method('getDestination')->willReturn('something');
        $request->method('getSignatureKey')->willReturn(null);

        $stub->getSignedRequest($request);
    }
}
