# CHANGELOG

## [unreleased]

### Add
  - receiving POST binding request
  - NewRelic example
  - Single sign-on using `HostedIdentityProviderProcessor::processSingleSignOn` now supports GET and POST requests.
  - Single logout using `HostedIdentityProviderProcessor::processSingleLogoutService` now supports GET and POST messages.
  
## Fix
  - remove dependency of "roave/security-advisories" to allow require without putting minimum stability dev (#10)[https://github.com/AdactiveSAS/saml2-bridge-bundle/issues/10]
  
## Deprecated
  - `\AdactiveSas\Saml2BridgeBundle\Entity\IdentityProvider::getSsoBinding` was removed, overwriting this method have no
more effects.
  - `\AdactiveSas\Saml2BridgeBundle\Entity\IdentityProvider::getSlsBinding` was removed, overwriting this method have no
more effects.
  
## v0.7.1

### Fix
  - Travis test by increasing php memory limit

## v0.7.0

### Add
  - Default Logger into `adactive_sas_saml2_bridge.processor.hosted_idp` service
  - ServiceProvider option `maxRetryLogin` to setup the number of login retry in case of errors. The default is `0` to 
  keep retro-compatibility

### Fix
  - SLS initiated by IDP
  - composer 

