# SAML2 Bridge Bundle

[![Coverage Status](https://coveralls.io/repos/github/AdactiveSAS/saml2-bridge-bundle/badge.svg?branch=master)](https://coveralls.io/github/AdactiveSAS/saml2-bridge-bundle?branch=master)
[![Build Status](https://travis-ci.org/AdactiveSAS/saml2-bridge-bundle.svg?branch=master)](https://travis-ci.org/AdactiveSAS/saml2-bridge-bundle)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/9e59c817-b08a-4065-bbe5-742335adcf2b/big.png)](https://insight.sensiolabs.com/projects/9e59c817-b08a-4065-bbe5-742335adcf2b)
A bundle that adds SAML capabilities to your application using [simplesamlphp/saml2][1] highly inspired by 
[OpenConext/Stepup-saml-bundle][2]

## SAML Support

SAML Support is limited, this bundle can be used to provide a basic identity provider with the following support:
- Basic metadata
- Single Sign On:
    - Binding: 
        - Http-POST & Http-Redirect signed request
        - Http-POST &  Http-Post signed response
- Single Logout:
    - Binding:
        - Http-POST & Http-Redirect signed request
        - Http-POST & Http-Redirect signed response
    - Both identity provider initiated and service provider initiated

## Getting started

### Installation

* Add the package to your Composer file
  ```sh
  composer require adactive-sas/saml2-bridge-bundle
  ```

* Add the bundle to your kernel in `app/AppKernel.php`
  ```php
  public function registerBundles()
  {
      // ...
      $bundles[] = new AdactiveSas\Saml2BridgeBundle\AdactiveSasSaml2BridgeBundle();
  }
  ```
  
### Configuration

```yaml
adactive_sas_saml2_bridge:
    hosted:
        metadata_route: name_of_the_route_of_metadata_url
        identity_provider:
            enabled: true
            service_provider_repository: service.name.of.entity_repository
            sso_route: name_of_the_route_of_the_single_sign_on_url
            sls_route: name_of_the_route_of_the_single_logout_url
            login_route: name_of_the_route_of_the_login_url
            logout_route: name_of_the_route_of_the_logout_url
            public_key: %idp_public_key_file_path%
            private_key: %idp_private_key_file_path%
```

Also add logout handler.
```yaml
            logout:
                handlers: [adactive_sas_saml2_bridge.logout.handler]
```
The hosted configuration lists the configuration for the services (SP, IdP or both) that your application offers. SP and IdP
 functionality can be turned off and on individually through the repective `enabled` flags.
 
The inlined certificate in the last line can be replaced with `certificate_file` containing a filesystem path to
a file which contains said certificate.

It is recommended to use parameters as listed above. The various `publickey` and `privatekey` variables are the
contents of the key in a single line, without the certificate etc. delimiters. The use of parameters as listed above
is highly recommended so that the actual key contents can be kept out of the configuration files (using for instance
a local `parameters.yml` file).

The `service_provider_repository` is a repository of service providers for which you offer IdP services. The service
configured _must_ implement the `AdactiveSas\Saml2BridgeBundle\Entity\ServiceProviderRepository` interface.

### Example Usage

#### Implement the Service Provider Repository

```php
<?php

namespace Acme\SamlBundle\Entity;

use AdactiveSas\Saml2BridgeBundle\Entity\ServiceProvider;
use AdactiveSas\Saml2BridgeBundle\Entity\ServiceProviderRepository;

class SamlServiceProviderRepository implements ServiceProviderRepository
{
    protected $spMap = [];
    
    public function __construct() {
        $this->spMap["https://test.fake/metadata"] = new ServiceProvider(
            [
                /**
                * Returns the contents of an X509 pem certificate, without the '-----BEGIN CERTIFICATE-----' and
                * '-----END CERTIFICATE-----'.
                *
                * @return null|string
                */
                'certificateData' => 'MIIEJTCCAw2gAwIBAgIJANug+o++1X5IMA0GCSqGSIb3DQEBCwUAMIGoMQswCQYDVQQGEwJOTDEQMA4GA1UECAwHVXRyZWNodDEQMA4GA1UEBwwHVXRyZWNodDEVMBMGA1UECgwMU1VSRm5ldCBCLlYuMRMwEQYDVQQLDApTVVJGY29uZXh0MRwwGgYDVQQDDBNTVVJGbmV0IERldmVsb3BtZW50MSswKQYJKoZIhvcNAQkBFhxzdXJmY29uZXh0LWJlaGVlckBzdXJmbmV0Lm5sMB4XDTE0MTAyMDEyMzkxMVoXDTE0MTExOTEyMzkxMVowgagxCzAJBgNVBAYTAk5MMRAwDgYDVQQIDAdVdHJlY2h0MRAwDgYDVQQHDAdVdHJlY2h0MRUwEwYDVQQKDAxTVVJGbmV0IEIuVi4xEzARBgNVBAsMClNVUkZjb25leHQxHDAaBgNVBAMME1NVUkZuZXQgRGV2ZWxvcG1lbnQxKzApBgkqhkiG9w0BCQEWHHN1cmZjb25leHQtYmVoZWVyQHN1cmZuZXQubmwwggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDXuSSBeNJY3d4p060oNRSuAER5nLWT6AIVbv3XrXhcgSwc9m2b8u3ksp14pi8FbaNHAYW3MjlKgnLlopYIylzKD/6Ut/clEx67aO9Hpqsc0HmIP0It6q2bf5yUZ71E4CN2HtQceO5DsEYpe5M7D5i64kS2A7e2NYWVdA5Z01DqUpQGRBc+uMzOwyif6StBiMiLrZH3n2r5q5aVaXU4Vy5EE4VShv3Mp91sgXJj/v155fv0wShgl681v8yf2u2ZMb7NKnQRA4zM2Ng2EUAyy6PQ+Jbn+rALSm1YgiJdVuSlTLhvgwbiHGO2XgBi7bTHhlqSrJFK3Gs4zwIsop/XqQRBAgMBAAGjUDBOMB0GA1UdDgQWBBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAfBgNVHSMEGDAWgBQCJmcoa/F7aM3jIFN7Bd4uzWRgzjAMBgNVHRMEBTADAQH/MA0GCSqGSIb3DQEBCwUAA4IBAQBd80GpWKjp1J+Dgp0blVAox1s/WPWQlex9xrx1GEYbc5elp3svS+S82s7dFm2llHrrNOBt1HZVC+TdW4f+MR1xq8O5lOYjDRsosxZc/u9jVsYWYc3M9bQAx8VyJ8VGpcAK+fLqRNabYlqTnj/t9bzX8fS90sp8JsALV4g84Aj0G8RpYJokw+pJUmOpuxsZN5U84MmLPnVfmrnuCVh/HkiLNV2c8Pk8LSomg6q1M1dQUTsz/HVxcOhHLj/owwh3IzXf/KXV/E8vSYW8o4WWCAnruYOWdJMI4Z8NG1Mfv7zvb7U3FL1C/KLV04DqzALXGj+LVmxtDvuxqC042apoIDQV',
                
                /**
                * Returns the full path to the (local) file that contains the X509 pem certificate.
                *
                * @return null|string
                */
                "certificateFile" => "",
                                
                /**
                * @return null|string
                */
                "entityId" => "https://test.fake/saml/metadata",
                
                /**
                * @return null|bool
                */
                "assertionEncryptionEnabled" => true,
                                
                "assertionConsumerUrl" => "https://test.fake/saml/acs",
                "assertionConsumerBinding" => \SAML2_Const::BINDING_HTTP_POST,
                "singleLogoutUrl" => "https://test.fake/saml/sls",
                "singleLogoutBinding" => \SAML2_Const::BINDING_HTTP_REDIRECT,
                "nameIdFormat" => \SAML2_Const::NAMEID_PERSISTENT,
                "nameIdValue" => function (UserInterface $user) {
                    /** @var User $user */
                    return $user->getEmailCanonical();
                },
                "NameQualifier" => 'test.fake',
                "wantSignedAuthnRequest" => true,
                "wantSignedAuthnResponse" => true,
                "wantSignedAssertions" => false,
                "wantSignedLogoutRequest" => false,
                "wantSignedLogoutResponse" => false,
                "attributes" => [
                    'User.Email' => function (UserInterface $user) {
                        /** @var User $user */
                        return $user->getEmailCanonical();
                    },
                    'User.Username' => function (UserInterface $user) {
                        /** @var User $user */
                        return $user->getName();
                    },
                    'first_name' => function (UserInterface $user) {
                        /** @var User $user */
                        return $user->getFirstName();
                    },
                    'last_name' => function (UserInterface $user) {
                        /** @var User $user */
                        return $user->getLastName();
                    },
                ],
            ]
        );
    }

    /**
     * @param string $entityId
     * @return ServiceProvider
     */
    public function getServiceProvider($entityId)
    {
        return $this->hasServiceProvider($entityId) ? $this->spMap[$entityId] : null;
    }

    /**
     * @param string $entityId
     * @return bool
     */
    public function hasServiceProvider($entityId)
    {
        return array_key_exists($entityId, $this->spMap);
    }
}
```

###### Slack example
```
$this->spMap["https://slack.com"] = new ServiceProvider(
    [
        /**
         * Returns the contents of an X509 pem certificate, without the '-----BEGIN CERTIFICATE-----' and
         * '-----END CERTIFICATE-----'.
         *
         * @return null|string
         */
        'certificateData' => 'MIIDrzCCApagAwIBAgIBADANBgkqhkiG9w0BAQ0FADBxMQswCQYDVQQGEwJ1czETMBEGA1UECAwKQ2FsaWZvcm5pYTEhMB8GA1UECgwYU2xhY2sgVGVjaG5vbG9naWVzLCBJbmMuMRIwEAYDVQQDDAlzbGFjay5jb20xFjAUBgNVBAcMDVNhbiBGcmFuY2lzY28wHhcNMTUwMzE3MDEyMzMyWhcNMjUwMzE0MDEyMzMyWjBxMQswCQYDVQQGEwJ1czETMBEGA1UECAwKQ2FsaWZvcm5pYTEhMB8GA1UECgwYU2xhY2sgVGVjaG5vbG9naWVzLCBJbmMuMRIwEAYDVQQDDAlzbGFjay5jb20xFjAUBgNVBAcMDVNhbiBGcmFuY2lzY28wggEjMA0GCSqGSIb3DQEBAQUAA4IBEAAwggELAoIBAgDB0y4ruySosz1GX/3KI1jp4oivxtnXLeMwKELrBgG+rZ8pl+UMhLG2iCp0nbnwSxXVU0ONJVI3SSzJ5VQtBHHCA4UAzse0HRaSZfBs+6urKoMLf8iusBYk62f2g/RAPjsMVcjC8B3FHyhaD9OnWSdJ7uGopmwwEhDiwf/gdS9Uw8FojYDuVprODfmj7+fgWPkGTf8TRGaHjudjuP1LMDRAz2cI0ym09jbnW8BVynSjjUrE+K9ri1uWzT2tp49OHqSgjaXkWWY6prFa9MT8jsibe02Id2i5+h0c4F892O7MybNWgF139dMGapmW4rf3GT7brLZEO4sZPwovhlj3b6U+8wIDAQABo1AwTjAdBgNVHQ4EFgQUa2YVk5yi+WMxLT/q7rokAfzyvU0wHwYDVR0jBBgwFoAUa2YVk5yi+WMxLT/q7rokAfzyvU0wDAYDVR0TBAUwAwEB/zANBgkqhkiG9w0BAQ0FAAOCAQIAUwv53vh2LkgbJbBGyRlSkjAZyybwM7pO6TtQ4SHyn366SG1lZXkc9S9u8m4kMETDquOujC/fZLiAe4f8rZ8+ZXV0f17FL/RhMDzVBv6DgDabfpXAkt+Yn+ZIThFi2D7L4jyJzZPbaf7soCu1e/Dx0CBhm/Lz2nsny6Il7rkEbDB7gBpjZODMMi/PEJ5I462JUrj+9aSZBtx2/NXIoFkLZ1B4j3UG+WJhcYMlMBim/GTimKS7yzkvfqdADmIAaO0RPYduNPds6Dyjyjbqj3XR3WwdsmTorO95UKitRGu10ImwByXo2xzQCwGNP8WuRAmWVIlisLLNEDKTnZDb38085gY=',

        /**
         * Returns the full path to the (local) file that contains the X509 pem certificate.
         *
         * @return null|string
         */
        "certificateFile" => "",

        /**
         * @return null|string
         */
        "entityId" => "https://slack.com",

        /**
         * @return null|bool
         */
        "assertionEncryptionEnabled" => true,

        "assertionConsumerUrl" => "https://$slackTeamName.slack.com/sso/saml",
        "assertionConsumerBinding" => \SAML2_Const::BINDING_HTTP_POST,
        "singleLogoutUrl" => "https://$slackTeamName.slack.com/sso/saml/logout",
        "singleLogoutBinding" => \SAML2_Const::BINDING_HTTP_REDIRECT,
        "nameIdFormat" => \SAML2_Const::NAMEID_PERSISTENT,
        "nameIdValue" => function (UserInterface $user) {
            /** @var User $user */
            return $user->getEmailCanonical();
        },
        "NameQualifier" => "$slackTeamName.slack.com",
        "wantSignedAuthnRequest" => true,
        "wantSignedAuthnResponse" => true,
        "wantSignedAssertions" => false,
        "attributes" => [
            'User.Email' => function (UserInterface $user) {
                /** @var User $user */
                return $user->getEmailCanonical();
            },
            'User.Username' => function (UserInterface $user) {
                /** @var User $user */
                return $user->getName();
            },
            'first_name' => function (UserInterface $user) {
                /** @var User $user */
                return $user->getFirstName();
            },
            'last_name' => function (UserInterface $user) {
                /** @var User $user */
                return $user->getLastName();
            },
        ],
    ]
);

```
###### Freshdesk example
```
$this->spMap["https://$freshdeskAccountName.freshdesk.com"] = new ServiceProvider(
    [
        /**
         * Returns the contents of an X509 pem certificate, without the '-----BEGIN CERTIFICATE-----' and
         * '-----END CERTIFICATE-----'.
         *
         * @return null|string
         */
        'certificateData' => '',

        /**
         * Returns the full path to the (local) file that contains the X509 pem certificate.
         *
         * @return null|string
         */
        "certificateFile" => "",

        /**
         * @return null|string
         */
        "entityId" => "https://$freshdeskAccountName.freshdesk.com",

        /**
         * @return null|bool
         */
        "assertionEncryptionEnabled" => false,

        "assertionConsumerUrl" => "https://$freshdeskAccountName.freshdesk.com/login/saml",
        "assertionConsumerBinding" => \SAML2_Const::BINDING_HTTP_POST,
        "singleLogoutUrl" => "https://$freshdeskAccountName.freshdesk.com/logout/saml",
        "singleLogoutBinding" => \SAML2_Const::BINDING_HTTP_REDIRECT,
        "nameIdFormat" => 'urn:oasis:names:tc:SAML:2.0:nameid-format:email',
        "nameIdValue" => function (UserInterface $user) {
            /** @var User $user */
            return $user->getEmailCanonical();
        },
        "NameQualifier" => "$freshdeskAccountName.freshdesk.com",
        "wantSignedAuthnRequest" => false,
        "wantSignedAuthnResponse" => false,
        "wantSignedAssertions" => true,
        "attributes" => [
            'email' => function (UserInterface $user) {
                /** @var User $user */
                return $user->getEmailCanonical();
            },
            'name' => function (UserInterface $user) {
                /** @var User $user */
                return $user->getName();
            },
            'given_name' => function (UserInterface $user) {
                /** @var User $user */
                return $user->getFirstName();
            },
            'family_name' => function (UserInterface $user) {
                /** @var User $user */
                return $user->getLastName();
            },
        ],
    ]
);

```
###### NewRelic example
```
$this->spMap["rpm.newrelic.com"] = new ServiceProvider(
    [
        /**
         * Returns the contents of an X509 pem certificate, without the '-----BEGIN CERTIFICATE-----' and
         * '-----END CERTIFICATE-----'.
         *
         * @return null|string
         */
        'certificateData' => '',

        /**
         * Returns the full path to the (local) file that contains the X509 pem certificate.
         *
         * @return null|string
         */
        "certificateFile" => "",

        /**
         * @return null|string
         */
        "entityId" => "rpm.newrelic.com",

        /**
         * @return null|bool
         */
        "assertionEncryptionEnabled" => false,

        "assertionConsumerUrl" => "https://rpm.newrelic.com/accounts/$accountId/sso/saml/finalize",
        "assertionConsumerBinding" => \SAML2_Const::BINDING_HTTP_POST,
        "singleLogoutUrl" => "",
        "singleLogoutBinding" => \SAML2_Const::BINDING_HTTP_REDIRECT,
        "nameIdFormat" => 'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress',
        "nameIdValue" => function (UserInterface $user) {
            /** @var User $user */
            return $user->getEmailCanonical();
        },
        "NameQualifier" => "rpm.newrelic.com",
        "wantSignedAuthnRequest" => false,
        "wantSignedAuthnResponse" => false,
        "wantSignedAssertions" => true,
        "attributes" => [],
    ]
);

```

> Note: Keep in mind that this is a example, you may retrieve ServiceProviders from database

#### Create the Controller

```php
<?php

namespace Acme\SamlBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/saml")
 */
class SamlController extends Controller
{
    /**
     * @Route("/sso", name="acme_saml_sso")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function singleSignOnAction(Request $httpRequest)
    {
        $idpProcessor = $this->get("adactive_sas_saml2_bridge.processor.hosted_idp");

        return $idpProcessor->processSingleSignOn($httpRequest);
    }

    /**
     * @Route("/sls", name="acme_saml_sls")
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function singleLogoutAction(Request $httpRequest)
    {
        $idpProcessor = $this->get("adactive_sas_saml2_bridge.processor.hosted_idp");

        return $idpProcessor->processSingleLogoutService($httpRequest);
    }

    /**
     * @Route("/metadata", name="acme_saml_metadata", defaults={"_format"="xml"})
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function metadataAction()
    {
        $idpProcessor = $this->get("adactive_sas_saml2_bridge.processor.hosted_idp");

        return $idpProcessor->getMetadataXmlResponse();
    }
}
```

#### Define services
```xml
<?xml version="1.0" ?>

<container xmlns="http://symfony.com/schema/dic/services"
           xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
           xsi:schemaLocation="http://symfony.com/schema/dic/services http://symfony.com/schema/dic/services/services-1.0.xsd">

    <services>
        <service id="acme.saml.service_provider_repository" class="Acme\SamlBundle\Entity\SamlServiceProviderRepository">
        </service>
    </services>
</container>
```

#### Configuration

```yaml
adactive_sas_saml2_bridge:
    hosted:
        metadata_route: acme_saml_metadata
        identity_provider:
            enabled: true
            service_provider_repository: acme.saml.service_provider_repository
            sso_route: acme_saml_sso
            sls_route: acme_saml_sls
            login_route: fos_user_security_login
            logout_route: fos_user_security_logout
            public_key: '%kernel.root_dir%/../vendor/adactive-sas/saml2-bridge-bundle/src/Resources/keys/development_publickey.cer'
            private_key: '%kernel.root_dir%/../vendor/adactive-sas/saml2-bridge-bundle/src/Resources/keys/development_privatekey.pem'
```

> Note: this is development keys, never use them in production !

## Tests

We are aware that this bundle really miss tests, this would come in next releases.

## Contributing

For the time being, this bundle is very limited but is designed to be support all SAML2 process.

So feel free to create issue and pull-request in order to help us making this bundle a bit more complete.


[1]: https://github.com/simplesamlphp/saml2
[2]: https://github.com/OpenConext/Stepup-saml-bundle
