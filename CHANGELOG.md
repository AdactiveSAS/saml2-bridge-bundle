# CHANGELOG

## v0.10.0
### Add
  - Symfony 4 support
  - Able to set `assertionNotBeforeInterval` as `null` (fix for `SubjectConfirmationData notBefore`)
  - Allow to send an array of values for `attributes`
  - Add validAudiences in the SP configuration

### Fix
  - Missing Content-type xml on metadata response
  - SubjectConfirmationData notBefore must be null for Bearer confirmation
  - `AssertionBuilder.setNotBefore` should set `NotBefore` subjectConfirmationData and not `NotOnOrAfter`

## v0.9.1

### Fix
  - Dev dependencies to fix Travis build

## v0.9.0

### Add
  - `ServiceProvider#assertionNotBeforeInterval` property to customize assertion validity
  - `ServiceProvider#assertionNotOnOrAfterInterval` property to customize assertion validity
  - `ServiceProvider#assertionSessionNotOnOrAfterInterval` property to customize assertion validity

## v0.8.1

### Fix
  - Prevent throwing exception on the `HostedIdentityProviderProcessor::onKernelResponse` when there is no current state.
  - Unit tests

## v0.8.0

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

