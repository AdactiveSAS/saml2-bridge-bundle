<?php

namespace AdactiveSas\Saml2BridgeBundle\Tests\Functional;


use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\User;

class SingleSignOnTest extends WebTestCase
{
    private function getAuthnRequestUri(){
        return "/saml/sso/?SAMLRequest=fZFBa4NAEIXvgfwHb3tSV02MLCpIcxHSS9L20EuZ6gSlumt3xtKfX00oaaF2mMvAm%2B89ZlKCvhtUMXKjj%2Fg%2BIrFTEKHl1ug7o2ns0Z7QfrQVPh4PmWiYB1K%2Bz5PSM9yg9c7whj5UJNYr54%2FaT8pWwwy8rbf14M0If%2Fb3iYxwyn0mXl6TOgyDSLpyd0ZXBtHW3YQ1uHENGMhNnCQBLviURCOWmhg0ZyKUwc6VGzeUD0GktlPHz8J5QkuXHKEnhfPZd5rUnCATo9XKALWkNPRIiit1Ku4PahIq%2BD7IgvONM%2FwPGqxhU5lO5FdQOu%2BoS3CbL122R4YaGFL%2Fp3q9us6%2Ff5d%2FAQ%3D%3D";
    }

    public function testAuthnRequestWithAlreadyLoggedInUser(){
        $client = $this->createAuthenticatedClient("moroine");

        $client->request("GET", $this->getAuthnRequestUri());

//        var_dump($client->getCrawler()->filter("title")->text());
        var_dump($client->getCrawler()->html());
        $this->assertEquals(302, $client->getResponse()->getStatusCode());
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