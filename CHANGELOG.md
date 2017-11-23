# CHANGELOG

## v0.7.2

### Add
  - \AdactiveSas\Saml2BridgeBundle\Entity\IdentityProvider::getSsoBinding is deprecated and no more used
  - NewRelic example

### Fix
  - Add an ability to receive GET and POST requests.
  
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

