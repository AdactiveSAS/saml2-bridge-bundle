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

class MetadataTestTest extends WebTestCase
{
    protected $metadataExpected =
        <<<META
<?xml version="1.0" encoding="UTF-8"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata" entityID="http://localhost/saml/metadata/">
    <md:IDPSSODescriptor protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="http://localhost/saml/sso/"/>
        <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="http://localhost/saml/sso/"/>
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect" Location="http://localhost/saml/sls/"/>
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST" Location="http://localhost/saml/sls/"/>
    </md:IDPSSODescriptor>
</md:EntityDescriptor>
META;

    public function testMeta()
    {
        $client = $this->createClient(array('test_case' => 'AcmeBundle', 'root_config' => 'config.yml'));

        $client->followRedirects();
        $client->request("GET", $this->getMetadataRequestUri());
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals(
            $this->spacesClean($this->metadataExpected),
            $this->spacesClean($client->getResponse()->getContent())
        );
    }

    /**
     * @return string
     */
    private function getMetadataRequestUri()
    {
        return "/saml/metadata";
    }

    /**
     * @param string $content
     * @return string
     */
    private function spacesClean($content)
    {
        return preg_replace('/\s+/', '', $content);
    }
}
