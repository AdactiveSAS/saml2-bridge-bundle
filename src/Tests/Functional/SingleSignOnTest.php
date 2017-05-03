<?php

namespace AdactiveSas\Saml2BridgeBundle\Tests\Functional;

use Symfony\Component\DomCrawler\Crawler;

class SingleSignOnTest extends WebTestCase
{
    /**
     * File under Fixtures/SingleSignOn/auth_not_signed.xml.twig
     *
     * Deflate --> Base64 encoded -> url encoded
     *
     * Validated through https://www.samltool.com
     *
     * @return string
     */
    private function getAuthnRequestUri()
    {
        return "/saml/sso/?SAMLRequest=fZFBa4NAEIXvgfwHb3tSV02MLCpIcxHSS9L20EuZ6gSlumt3xtKfX00oaaF2mMvAm%2B89ZlKCvhtUMXKjj%2Fg%2BIrFTEKHl1ug7o2ns0Z7QfrQVPh4PmWiYB1K%2Bz5PSM9yg9c7whj5UJNYr54%2FaT8pWwwy8rbf14M0If%2Fb3iYxwyn0mXl6TOgyDSLpyd0ZXBtHW3YQ1uHENGMhNnCQBLviURCOWmhg0ZyKUwc6VGzeUD0GktlPHz8J5QkuXHKEnhfPZd5rUnCATo9XKALWkNPRIiit1Ku4PahIq%2BD7IgvONM%2FwPGqxhU5lO5FdQOu%2BoS3CbL122R4YaGFL%2Fp3q9us6%2Ff5d%2FAQ%3D%3D";
    }

    public function testAuthnRequestWithAlreadyLoggedInUser()
    {
        $client = $this->createAuthenticatedClient("moroine");

        $client->request("GET", $this->getAuthnRequestUri());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $location = $client->getResponse()->headers->get("location");

        $parts = parse_url($location);

        $this->assertEquals("https", $parts["scheme"]);
        $this->assertEquals("test.other.fake", $parts["host"]);
        $this->assertEquals("/saml/acs", $parts["path"]);
        $this->assertEquals("https", $parts["scheme"]);

        parse_str($parts["query"], $queryParts);

        $this->assertCount(1, array_keys($queryParts));
        $this->assertArrayHasKey("SAMLResponse", $queryParts);

        $responseXmlString = gzinflate(base64_decode($queryParts["SAMLResponse"], true));

        $responseCrawler = new Crawler($responseXmlString);

        $this->assertCount(1, $responseCrawler);

        $rootNode = $responseCrawler->getNode(0);
        $rootAttributes = $this->getNodeAttributes($rootNode);

        $this->assertArrayHasKey("ID", $rootAttributes);
        $this->assertNotEmpty($rootAttributes["ID"]);

        $this->assertArrayHasKey("IssueInstant", $rootAttributes);
        $this->assertRegExp('/^[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}Z$/', $rootAttributes["IssueInstant"]);
        $issueInstant = new \DateTime($rootAttributes["IssueInstant"]);
        $this->assertEquals(time(), $issueInstant->getTimestamp(), null, 10);

        $assertionNodesCrawler = $responseCrawler->filterXPath("//samlp:Response/saml:Assertion");
        $this->assertCount(1, $assertionNodesCrawler);
        $assertionAttributes = $this->getNodeAttributes($assertionNodesCrawler->getNode(0));

        $this->assertArrayHasKey("ID", $assertionAttributes);
        $this->assertNotEmpty($assertionAttributes["ID"]);

        $this->assertArrayHasKey("ID", $assertionAttributes);
        $this->assertNotEmpty($assertionAttributes["ID"]);

        $sessionNotOnOrAfter = new \DateTime();
        $sessionNotOnOrAfter->setTimestamp($issueInstant->getTimestamp() + 5*60);

        $subjectNotOnOrAfter = new \DateTime();
        $subjectNotOnOrAfter->setTimestamp($issueInstant->getTimestamp() + 24*3600);

        $expectedResponseXmlString = $this->renderTemplate("/SingleSignOn/response_not_signed.xml.twig", [
            "responseID" => $rootAttributes["ID"],
            "assertionID" => $assertionAttributes["ID"],
            "issueInstant" => gmdate('Y-m-d\TH:i:s\Z', $issueInstant->getTimestamp()),
            "sessionNotOnOrAfter" => gmdate('Y-m-d\TH:i:s\Z', $sessionNotOnOrAfter->getTimestamp()),
            "subjectNotOnOrAfter" => gmdate('Y-m-d\TH:i:s\Z', $subjectNotOnOrAfter->getTimestamp()),
        ]);

        $this->assertXmlStringEqualsXmlString($expectedResponseXmlString, $responseXmlString);
    }

    protected function getNodeAttributes(\DOMNode $node){
        $data = [];

        $attributes = $node->attributes;
        for($i = 0; $i < $attributes->length ; $i++){
            $attribute = $attributes->item($i);
            $data[$attribute->nodeName] = $attribute->nodeValue;
        }

        return $data;
    }

    protected function renderTemplate($path, $params){
        $loader = new \Twig_Loader_Filesystem(__DIR__ . "/../Fixtures/");
        $twig = new \Twig_Environment($loader);

        return $twig->render($path, $params);
    }

    protected function createAuthenticatedClient($username)
    {
        $client = $this->createClient(array('test_case' => 'AcmeBundle', 'root_config' => 'config_no_signing.yml'));
        $client->request('GET', '/login');

        $form = $client->getCrawler()->selectButton('login')->form();

        $form['_username'] = $username;
        $form['_password'] = 'test';
        $client->submit($form);

        $client->followRedirect();

        return $client;
    }
}