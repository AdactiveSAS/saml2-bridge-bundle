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

namespace AdactiveSas\Saml2BridgeBundle\Tests\Functional;

use AdactiveSas\Saml2BridgeBundle\SAML2\SAML2_Const;
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
        return "/saml/sso/?SAMLRequest=fZFBa4NAEIXvgfwHb3tSV02MLCpIcxHSS9L20EuZ6gSlumt3xtKfX42UtNB0mMvAm28eb1KCvhtUMXKjj%2Fg%2BIrFTEKHl1ug7o2ns0Z7QfrQVPh4PmWiYB1K%2Bz5PSM9yg9c7whv7M8aEisV45f9R%2BkrcaZuqV0daDN3OWZSIjnHKfiZfXpA7DIJKu3J3RlUG0dTdhDW5cAwZyEydJgDfulEQjlpoYNGcilMHOlRs3lA9BpLZTx8%2FCeUJLFx%2BhJ4Xz2Xea1OwgE6PVygC1pDT0SIordSruD2oSKvhO5cblK2f4HzRYw6YyncgXUDrvqItxm9%2BKt0eGGhhS%2F6d6vVrm3w%2FMvwA%3D";
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
            'responseID' => $rootAttributes["ID"],
            'assertionID' => $assertionAttributes["ID"],
            'issueInstant' => gmdate('Y-m-d\TH:i:s\Z', $issueInstant->getTimestamp()),
            'sessionNotOnOrAfter' => gmdate('Y-m-d\TH:i:s\Z', $sessionNotOnOrAfter->getTimestamp()),
            'subjectNotOnOrAfter' => gmdate('Y-m-d\TH:i:s\Z', $subjectNotOnOrAfter->getTimestamp()),
            'authnContext' => SAML2_Const::AC_PREVIOUS_SESSION,
            'spNameQualifier' => 'https://test.other.fake/metadata',
            'inResponseTo' => '_b8d22130-07fe-0135-42da-6dae1046881e',
            'recipient' => 'https://test.other.fake/saml/acs'
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
        $client = $this->createClient(array('test_case' => 'AcmeBundle', 'root_config' => 'config.yml'));
        $client->request('GET', '/login');

        $form = $client->getCrawler()->selectButton('login')->form();

        $form['_username'] = $username;
        $form['_password'] = 'test';
        $client->submit($form);

        $client->followRedirect();

        return $client;
    }
}
