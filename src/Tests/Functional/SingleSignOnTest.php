<?php

namespace AdactiveSas\Saml2BridgeBundle\Tests\Functional;


use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\User;

class SingleSignOnTest extends WebTestCase
{
    /**
     * Validated through https://www.samltool.com
     * @return string
     */
    private function getAuthnRequestUri()
    {
        return [
            "request" => "/saml/sso/?SAMLRequest=fZFBa4NAEIXvgfwHb3tSV02MLCpIcxHSS9L20EuZ6gSlumt3xtKfX00oaaF2mMvAm%2B89ZlKCvhtUMXKjj%2Fg%2BIrFTEKHl1ug7o2ns0Z7QfrQVPh4PmWiYB1K%2Bz5PSM9yg9c7whj5UJNYr54%2FaT8pWwwy8rbf14M0If%2Fb3iYxwyn0mXl6TOgyDSLpyd0ZXBtHW3YQ1uHENGMhNnCQBLviURCOWmhg0ZyKUwc6VGzeUD0GktlPHz8J5QkuXHKEnhfPZd5rUnCATo9XKALWkNPRIiit1Ku4PahIq%2BD7IgvONM%2FwPGqxhU5lO5FdQOu%2BoS3CbL122R4YaGFL%2Fp3q9us6%2Ff5d%2FAQ%3D%3D",
            "expected_response" => "https://test.other.fake/saml/acs?SAMLResponse=pVTLbtswELz3KwTeJZGUrAcRO01jFAiQB1AHQdFLsSKXsVpLNES6Tv%2B%2BlCwHttskQArowt3Z2eHsUmfnT80q%2BIWdrU07JSyi5Hz24cxCs1qLL2jXprUYeExrxRCckk3XCgO2tqKFBq1wUiwubq4Fj6hYd8YZaVbkoOT1CrAWO%2Bebk%2BBqPiXfS60L0Bwgp6xkWVnqqpSJ0hlNeZlWkzznk6SgTIKWIFmaZkU2UZCATgoSPOwv4qk9obUbvGqtg9b5EGV5SNOQF%2FeUiUkuWPKNBHO0rm7BDVVL59ZWxLHzwci4JXaRhp8Y99eIQVpP2e5NuTdebVUozllCQ5prDClLJmHKFYSZAmTUaysYktlgpxjUdLO%2Bh2%2BxMhJWS2PdjrxBBwocxGfxIXgcxMKB29jj06VRGDzAaoOvG2wHtFhspERrSTzbdTgmFRf7MYyDe7L1zg4vdbvdRtskMt1jzCll8deb64VcYgNhPXgrkTxXvV00zhkBqFaYqUla5VWe8STVkBacS0h5BRkrFFNcl0yh1qxSJc2VShEZKP6OOf%2FvDLx%2F1Q%2BUbjzdepuv5sFn0zXg3lhw57o%2BGuoBLCqPk2TWmM7ULY6tdnzHrS5Nq%2Bu%2BpJ%2FKDbqlUa93ko2oEDrsyMtEc3%2B%2F4Na4T%2Bjl4EuPwgPu2rvuQjvsDjDlAWa%2FR%2F9qcpIa1XiEqvu0fbcAj%2BHCf6OA3eZ6f%2Btq47DfZ2ywdaeJoHd3Svzu1StymhxeUODXXbjfa4968j8tn2ofn0cUVZ4TdVe7j489RSRNM17wmGV2Gv0rcCAxPn52z69y%2F3uZffgD"
        ];
    }

    public function testAuthnRequestWithAlreadyLoggedInUser()
    {
        $client = $this->createAuthenticatedClient("moroine");

        $client->request("GET", $this->getAuthnRequestUri()["request"]);
        $this->assertEquals(302, $client->getResponse()->getStatusCode());

        $this->assertEquals($this->getAuthnRequestUri()["expected_response"],$client->getResponse()->headers->get("location") );
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