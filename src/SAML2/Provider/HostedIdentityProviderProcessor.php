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

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Provider;

use AdactiveSas\Saml2BridgeBundle\Entity\HostedIdentityProvider;
use AdactiveSas\Saml2BridgeBundle\Entity\ServiceProvider;
use AdactiveSas\Saml2BridgeBundle\Entity\ServiceProviderRepository;
use AdactiveSas\Saml2BridgeBundle\Exception\InvalidArgumentException;
use AdactiveSas\Saml2BridgeBundle\Exception\InvalidSamlRequestException;
use AdactiveSas\Saml2BridgeBundle\Exception\RuntimeException;
use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\UnknownServiceProviderException;
use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\HttpBindingContainer;
use AdactiveSas\Saml2BridgeBundle\SAML2\Builder\AssertionBuilder;
use AdactiveSas\Saml2BridgeBundle\SAML2\Builder\AuthnResponseBuilder;
use AdactiveSas\Saml2BridgeBundle\SAML2\Builder\LogoutRequestBuilder;
use AdactiveSas\Saml2BridgeBundle\SAML2\Builder\LogoutResponseBuilder;
use AdactiveSas\Saml2BridgeBundle\SAML2\Event\AuthenticationSuccessEvent;
use AdactiveSas\Saml2BridgeBundle\SAML2\Event\GetAuthnResponseEvent;
use AdactiveSas\Saml2BridgeBundle\SAML2\Event\LogoutEvent;
use AdactiveSas\Saml2BridgeBundle\SAML2\Event\ReceiveAuthnRequestEvent;
use AdactiveSas\Saml2BridgeBundle\SAML2\Event\Saml2Events;
use AdactiveSas\Saml2BridgeBundle\SAML2\Metadata\MetadataFactory;
use AdactiveSas\Saml2BridgeBundle\SAML2\SAML2_Const;
use AdactiveSas\Saml2BridgeBundle\SAML2\State\SamlState;
use AdactiveSas\Saml2BridgeBundle\SAML2\State\SamlStateHandler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationEvent as CoreAuthenticationEvent;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent as CoreAuthenticationFailureEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class HostedIdentityProviderProcessor implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ServiceProviderRepository
     */
    protected $serviceProviderRepository;

    /**
     * @var \SAML2_Certificate_KeyLoader
     */
    protected $publicKeyLoader;

    /**
     * @var HostedIdentityProvider
     */
    protected $identityProvider;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var HttpBindingContainer
     */
    protected $bindingContainer;

    /**
     * @var SamlStateHandler
     */
    protected $stateHandler;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var MetadataFactory
     */
    protected $metadataFactory;

    /**
     * HostedIdentityProvider constructor.
     *
     * @param ServiceProviderRepository $serviceProviderRepository
     * @param HostedIdentityProvider $identityProvider
     * @param HttpBindingContainer $bindingContainer
     * @param SamlStateHandler $stateHandler
     * @param EventDispatcherInterface $eventDispatcher
     * @param MetadataFactory $metadataFactory
     *
     * @internal param HostedEntities $HostedEntities
     */
    public function __construct(
        ServiceProviderRepository $serviceProviderRepository,
        HostedIdentityProvider $identityProvider,
        HttpBindingContainer $bindingContainer,
        SamlStateHandler $stateHandler,
        EventDispatcherInterface $eventDispatcher,
        MetadataFactory $metadataFactory
    )
    {
        $this->serviceProviderRepository = $serviceProviderRepository;
        $this->publicKeyLoader = new \SAML2_Certificate_KeyLoader();
        $this->identityProvider = $identityProvider;
        $this->bindingContainer = $bindingContainer;
        $this->stateHandler = $stateHandler;
        $this->eventDispatcher = $eventDispatcher;
        $this->metadataFactory = $metadataFactory;

        $this->setLogger(new NullLogger());
    }

    /**
     * @param LoggerInterface $logger
     * @return $this
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * The array keys are event names and the value can be:
     *
     *  * The method name to call (priority defaults to 0)
     *  * An array composed of the method name to call and the priority
     *  * An array of arrays composed of the method names to call and respective
     *    priorities, or 0 if unset
     *
     * For instance:
     *
     *  * array('eventName' => 'methodName')
     *  * array('eventName' => array('methodName', $priority))
     *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * @return array The event names to listen to
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
            AuthenticationEvents::AUTHENTICATION_SUCCESS => "onAuthenticationSuccess",
            AuthenticationEvents::AUTHENTICATION_FAILURE => "onAuthenticationFailure",
            Saml2Events::SLO_LOGOUT_SUCCESS => 'onLogoutSuccess',
        ];
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        if ($event->getResponse()->isServerError() || $event->getResponse()->isClientError()) {
            return;
        }

        if ($this->stateHandler->can(SamlStateHandler::TRANSITION_SSO_RESPOND)) {
            $event->setResponse($this->continueSingleSignOn());
            return;
        }else{
            $state = $this->stateHandler->get();
            $this->logger->debug("Cannot TRANSITION_SSO_RESPOND", ['state' => $state === null ? null : $state->getState()]);
        }

        if ($this->stateHandler->can(SamlStateHandler::TRANSITION_SLS_RESPOND, false)) {
            $event->setResponse($this->continueSingleLogoutService());
            return;
        }else{
            $state = $this->stateHandler->get();
            $this->logger->debug("Cannot TRANSITION_SLS_RESPOND", ['state' => $state === null ? null : $state->getState()]);
        }
    }

    /**
     * @param CoreAuthenticationEvent $event
     */
    public function onAuthenticationSuccess(CoreAuthenticationEvent $event)
    {
        if ($event->getAuthenticationToken() instanceof AnonymousToken) {
            $this->logger->info("Anonymous user, wait for authentication");
            return;
        }

        if($this->stateHandler->has()){
            $this->stateHandler->get()->resetLoginRetryCount();
        }

        $user = $event->getAuthenticationToken()->getUser();
        if ($this->stateHandler->has()
            && $user instanceof UserInterface && $this->stateHandler->has()) {
            $this->stateHandler->get()->setUserName($user->getUsername());
        }

        if (!$this->stateHandler->can(SamlStateHandler::TRANSITION_SSO_AUTHENTICATE_SUCCESS)) {
            $this->logger->debug('Cannot perform authentication success');
            return;
        }

        $this->logger->notice('Authentication succeed');

        $this->stateHandler->get()->setAuthnContext($this->identityProvider->getAuthnContext());
        $this->stateHandler->apply(SamlStateHandler::TRANSITION_SSO_AUTHENTICATE_SUCCESS);
    }

    /**
     * @param CoreAuthenticationFailureEvent $event
     */
    public function onAuthenticationFailure(CoreAuthenticationFailureEvent $event)
    {
        if (!$this->stateHandler->can(SamlStateHandler::TRANSITION_SSO_AUTHENTICATE_FAIL)) {
            $this->logger->debug("Cannot perform authentication fail");
            return;
        }

        if($this->stateHandler->has()){
            /** @var \SAML2_AuthnRequest $authRequest */
            $authRequest = $this->stateHandler->get()->getRequest();

            $sp = $this->getServiceProvider($authRequest->getIssuer());

            if($this->stateHandler->get()->getLoginRetryCount() < $sp->getMaxRetryLogin()){
                $this->stateHandler->get()->incrementLoginRetryCount();
                $this->logger->debug("Login failed, retrying");
                return;
            }
        }

        $this->logger->notice("Authentication failed");
        $this->stateHandler->apply(SamlStateHandler::TRANSITION_SSO_AUTHENTICATE_FAIL);
    }

    /**
     * @param LogoutEvent $event
     */
    public function onLogoutSuccess(LogoutEvent $event)
    {
        if (!$this->stateHandler->can(SamlStateHandler::TRANSITION_SLS_END_DISPATCH)) {
            $this->logger->notice("Logout initiated by IDP");
            $this->stateHandler->resume(true);
            $this->stateHandler->get()->setOriginalLogoutResponse($event->getResponse());

            $this->stateHandler->apply(SamlStateHandler::TRANSITION_SLS_START_BY_IDP);

            return;
        }

        $this->logger->notice('Logout success');

        $this->stateHandler->get()->setOriginalLogoutResponse($event->getResponse());
        $this->stateHandler->apply(SamlStateHandler::TRANSITION_SLS_END_DISPATCH);
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getMetadataXmlResponse()
    {
        return $this->metadataFactory->getMetadataResponse();
    }


    /**
     * @param Request $httpRequest
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\RuntimeException
     * @throws \InvalidArgumentException
     */
    public function processSingleSignOn(Request $httpRequest)
    {
        $this->stateHandler->resume(true)->apply(SamlStateHandler::TRANSITION_SSO_START);

        $this->logger->notice('Received AuthnRequest, started processing');

        $inputBinding = $this->bindingContainer->getByRequestMethod($httpRequest->getMethod());

        try {
            $authRequest = $inputBinding->receiveUnsignedAuthnRequest($httpRequest);
            $sp = $this->getServiceProvider($authRequest->getIssuer());
            if ($sp->wantSignedAuthnRequest()) {
                $authRequest = $inputBinding->receiveSignedAuthnRequest($httpRequest);
            }

            $this->validateMessage($authRequest);

            $event = new ReceiveAuthnRequestEvent($authRequest, $this->identityProvider, $this->stateHandler);
            $this->eventDispatcher->dispatch(Saml2Events::SSO_AUTHN_RECEIVE_REQUEST, $event);
        } catch (\Throwable $e) {
            // handle error, apparently the request cannot be processed :(
            $msg = sprintf('Could not process Request, error: "%s"', $e->getMessage());
            $this->logger->critical($msg);

            throw new RuntimeException($msg, 0, $e);
        }

        $this->stateHandler->get()->setRequest($authRequest);

        try{
            $needLogin = $this->authnRequestNeedLogin($authRequest);
        }catch (InvalidSamlRequestException $e){
            $this->logger->warning($e->getMessage());

            $sp = $this->getServiceProvider($authRequest->getIssuer());
            $outBinding = $this->bindingContainer->get($sp->getAssertionConsumerBinding());

            $authnResponse = $this->buildAuthnFailedResponse($authRequest, $e->getSamlStatusCode());

            if ($sp->wantSignedAuthnResponse()) {
                return $outBinding->getSignedResponse($authnResponse);
            }

            return $outBinding->getUnsignedResponse($authnResponse);
        }

        if ($needLogin) {
            $this->stateHandler->apply(SamlStateHandler::TRANSITION_SSO_START_AUTHENTICATE);

            $this->logger->notice(
                sprintf('Login is required, redirecting to login page %s',
                    $this->identityProvider->getLoginUrl()
                )
            );

            return new RedirectResponse($this->identityProvider->getLoginUrl());
        }

        $this->stateHandler->get()->setAuthnContext(SAML2_Const::AC_PREVIOUS_SESSION);

        return $this->continueSingleSignOn();
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function continueSingleSignOn()
    {
        $this->logger->notice("Continue SSO process");

        /** @var \SAML2_AuthnRequest $authRequest */
        $authRequest = $this->stateHandler->get()->getRequest();

        $sp = $this->getServiceProvider($authRequest->getIssuer());
        $outBinding = $this->bindingContainer->get($sp->getAssertionConsumerBinding());

        if($this->stateHandler->get()->getState() === SamlState::STATE_SSO_AUTHENTICATING_FAILED){
            $authnResponse = $this->buildAuthnFailedResponse($authRequest, \SAML2_Const::STATUS_AUTHN_FAILED);
        }else {
            $authnResponse = $this->buildAuthnResponse($authRequest);

            $this->stateHandler->get()->addServiceProviderId($sp->getEntityId());

            $event = new AuthenticationSuccessEvent($sp, $this->identityProvider, $this->stateHandler);
            $this->eventDispatcher->dispatch(Saml2Events::SSO_AUTHN_SUCCESS, $event);
        }

        $this->stateHandler->apply(SamlStateHandler::TRANSITION_SSO_RESPOND);

        if ($sp->wantSignedAuthnResponse()) {
            $response = $outBinding->getSignedResponse($authnResponse);
        } else {
            $response = $outBinding->getUnsignedResponse($authnResponse);
        }

        $this->stateHandler->resume();

        return $response;
    }

    /**
     * @param Request $httpRequest
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\InvalidArgumentException
     */
    public function processSingleLogoutService(Request $httpRequest)
    {
        $inputBinding = $this->bindingContainer->getByRequestMethod($httpRequest->getMethod());

        try {
            $logoutMessage = $inputBinding->receiveUnsignedMessage($httpRequest);
        } catch (\Throwable $e) {
            // handle error, apparently the request cannot be processed :(
            $msg = sprintf('Could not process Request, error: "%s"', $e->getMessage());
            $this->logger->critical($msg);

            throw new RuntimeException($msg, 0, $e);
        }

        if ($logoutMessage instanceof \SAML2_LogoutRequest) {
            $sp = $this->getServiceProvider($logoutMessage->getIssuer());
            if ($sp->wantSignedLogoutRequest()) {
                $logoutMessage = $inputBinding->receiveSignedLogoutRequest($httpRequest);
            }
            $this->validateMessage($logoutMessage);

            $this->logger->notice('Received LogoutRequest, started processing');

            $this->stateHandler->resume(true)->apply(SamlStateHandler::TRANSITION_SLS_START);

            $this->stateHandler->get()->setRequest($logoutMessage);

            $sp = $this->getServiceProvider($logoutMessage->getIssuer());
            $this->stateHandler->get()->removeServiceProviderId($sp->getEntityId());

            return $this->continueSingleLogoutService();
        }

        if ($logoutMessage instanceof \SAML2_LogoutResponse) {
            $sp = $this->getServiceProvider($logoutMessage->getIssuer());
            if ($sp->wantSignedLogoutResponse()) {
                $logoutMessage = $inputBinding->receiveSignedLogoutResponse($httpRequest);
            }
            $this->validateMessage($logoutMessage);

            $this->logger->notice('Received LogoutResponse, continue processing');
            $this->stateHandler->apply(SamlStateHandler::TRANSITION_SLS_END_PROPAGATE);

            return $this->continueSingleLogoutService();
        }

        throw new InvalidArgumentException(sprintf(
            'The received request is neither a LogoutRequest nor a LogoutResponse, "%s" received instead',
            substr(get_class($logoutMessage), strrpos($logoutMessage, '_') + 1)
        ));
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \InvalidArgumentException
     */
    public function continueSingleLogoutService()
    {
        $this->logger->notice('Continue SLS process');
        if ($this->stateHandler->can(SamlStateHandler::TRANSITION_SLS_START_DISPATCH)) {
            $this->stateHandler->apply(SamlStateHandler::TRANSITION_SLS_START_DISPATCH);

            $this->logger->notice(
                sprintf('Logout from studio, redirecting to logout page %s',
                    $this->identityProvider->getLogoutUrl()
                )
            );

            return new RedirectResponse($this->identityProvider->getLogoutUrl());
        }

        $state = $this->stateHandler->get();
        if ($state->hasServiceProviderIds()) {
            $this->stateHandler->apply(SamlStateHandler::TRANSITION_SLS_START_PROPAGATE);

            // Dispatch logout to service providers
            $sp = $this->serviceProviderRepository->getServiceProvider($state->popServiceProviderIds());
            $logoutRequest = $this->buildLogoutRequest($sp);

            $outBinding = $this->bindingContainer->get($sp->getSingleLogoutBinding());

            $this->logger->notice(sprintf('Propagate logout to sp %s',$sp->getSingleLogoutUrl()));

            if ($sp->wantSignedLogoutRequest()) {
                $response = $outBinding->getSignedRequest($logoutRequest);
            } else {
                $response = $outBinding->getUnsignedRequest($logoutRequest);
            }

            return $response;
        }

        $this->stateHandler->apply(SamlStateHandler::TRANSITION_SLS_RESPOND);

        /** @var \SAML2_LogoutRequest $logoutRequest */
        $logoutRequest = $this->stateHandler->get()->getRequest();
        if ($logoutRequest !== null) {
            $logoutResponse = $this->buildLogoutResponse($logoutRequest);

            $sp = $this->getServiceProvider($logoutRequest->getIssuer());
            $outBinding = $this->bindingContainer->get($sp->getSingleLogoutBinding());

            $this->logger->notice(sprintf('Logout: Respond to sp initiator %s',$sp->getEntityId()));

            if ($sp->wantSignedLogoutResponse()) {
                $response = $outBinding->getSignedResponse($logoutResponse);
            } else {
                $response = $outBinding->getUnsignedResponse($logoutResponse);
            }

            $originalLogoutResponse = $this->stateHandler->get()->getOriginalLogoutResponse();

            $originalHeaders = $originalLogoutResponse->headers->all();

            // Remove possible location header that would replace the redirect response
            if($originalLogoutResponse->headers->has("location")){
                unset ($originalHeaders["location"]);
            }

            // Merge original logout response header to include possible cookie removal
            $response->headers->add($originalHeaders);
        } else {
            // Identity provider initialized ==> Redirect as a standard logout
            $response = $this->stateHandler->get()->getOriginalLogoutResponse();
        }

        $this->stateHandler->resume();

        $this->logger->notice('Saml: Logout terminated');

        return $response;
    }

    /**
     * @param \SAML2_AuthnRequest $authnRequest
     * @return bool
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\InvalidSamlRequestException
     */
    public function authnRequestNeedLogin(\SAML2_AuthnRequest $authnRequest)
    {
        $isPassive = $authnRequest->getIsPassive();
        $isForce = $authnRequest->getForceAuthn();

        if($isPassive && $isForce)
        {
            throw new InvalidSamlRequestException(
                'Invalid Saml request: cannot be passive and force',
                \SAML2_Const::STATUS_REQUESTER
            );
        }

        if($isForce){
            return true;
        }

        $isAuthenticated = $this->stateHandler->isAuthenticated();

        if($isPassive && !$isAuthenticated)
        {
            throw new InvalidSamlRequestException(
                'Invalid Saml request: cannot authenticate passively',
                \SAML2_Const::STATUS_NO_PASSIVE
            );
        }

        return $isAuthenticated;
    }

    /**
     * @param \SAML2_AuthnRequest $authnRequest
     * @return \SAML2_Response
     * @throws \Exception
     */
    protected function buildAuthnResponse(\SAML2_AuthnRequest $authnRequest)
    {
        $serviceProvider = $this->getServiceProvider($authnRequest->getIssuer());

        $authnResponseBuilder = new AuthnResponseBuilder();

        $state = $this->stateHandler->get();
        $user = $this->stateHandler->getUser();
        $nameIdValue =
            is_callable($serviceProvider->getNameIdValue())
                ? call_user_func($serviceProvider->getNameIdValue(), $user)
                : $serviceProvider->getNameIdValue();


        $assertionBuilder = new AssertionBuilder();
        $assertionBuilder
            ->setNotBefore($serviceProvider->getAssertionNotBeforeInterval())
            ->setNotOnOrAfter($serviceProvider->getAssertionNotOnOrAfterInterval())
            ->setSessionNotOnOrAfter($serviceProvider->getAssertionSessionNotOnORAfterInterval())
            ->setIssuer($this->identityProvider->getEntityId())
            ->setNameId($nameIdValue, $serviceProvider->getNameIdFormat(), $serviceProvider->getNameQualifier(), $authnRequest->getIssuer())
            ->setConfirmationMethod(SAML2_Const::CM_BEARER)
            ->setInResponseTo($authnRequest->getId())
            ->setRecipient($serviceProvider->getAssertionConsumerUrl())
            ->setAuthnContext($state->getAuthnContext())
            ->setValidAudiences($serviceProvider->getValidAudiences());
        foreach ($serviceProvider->getAttributes() as $attributeName => $attributeCallback) {
            $assertionBuilder->setAttribute($attributeName, $attributeCallback($user));
        }
        $assertionBuilder->setAttributesNameFormat(\SAML2_Const::NAMEFORMAT_UNSPECIFIED);
        if ($serviceProvider->wantSignedAssertions()) {
            $assertionBuilder->sign($this->getIdentityProviderXmlPrivateKey(), $this->getIdentityProviderXmlPublicKey());
        }
        $assertionBuilder->setAttributesNameFormat(\SAML2_Const::NAMEFORMAT_UNSPECIFIED);

        $destination = $authnRequest->getAssertionConsumerServiceURL()
            ? $authnRequest->getAssertionConsumerServiceURL()
            : $serviceProvider->getAssertionConsumerUrl();

        $authnResponseBuilder
            ->setStatus(\SAML2_Const::STATUS_SUCCESS)
            ->setIssuer($this->identityProvider->getEntityId())
            ->setRelayState($authnRequest->getRelayState())
            ->setDestination($destination)
            ->addAssertionBuilder($assertionBuilder)
            ->setInResponseTo($authnRequest->getId())
            ->setWantSignedAssertions($serviceProvider->wantSignedAssertions())
            ->setSignatureKey($this->getIdentityProviderXmlPrivateKey());

        $event = new GetAuthnResponseEvent($serviceProvider, $this->identityProvider, $this->stateHandler, $authnResponseBuilder);

        $this->eventDispatcher->dispatch(Saml2Events::SSO_AUTHN_GET_RESPONSE, $event);

        return $event->getAuthnResponseBuilder()->getResponse();
    }

    /**
     * @param \SAML2_AuthnRequest $authnRequest
     * @return \SAML2_Response
     */
    protected function buildAuthnFailedResponse(\SAML2_AuthnRequest $authnRequest, $samlStatus)
    {
        $serviceProvider = $this->getServiceProvider($authnRequest->getIssuer());

        $authnResponseBuilder = new AuthnResponseBuilder();

        return $authnResponseBuilder
            ->setStatus($samlStatus)
            ->setIssuer($this->identityProvider->getEntityId())
            ->setRelayState($authnRequest->getRelayState())
            ->setDestination($serviceProvider->getAssertionConsumerUrl())
            ->setInResponseTo($authnRequest->getId())
            ->setSignatureKey($this->getIdentityProviderXmlPrivateKey())
            ->getResponse();
    }

    /**
     * @param ServiceProvider $serviceProvider
     * @return \SAML2_LogoutRequest
     */
    protected function buildLogoutRequest(ServiceProvider $serviceProvider)
    {
        $logoutRequestBuilder = new LogoutRequestBuilder();

        return $logoutRequestBuilder
            ->setNameId($this->stateHandler->get()->getUserName(), \SAML2_Const::NAMEFORMAT_BASIC)
            ->setIssuer($this->identityProvider->getEntityId())
            ->setDestination($serviceProvider->getSingleLogoutUrl())
            ->setSignatureKey($this->getIdentityProviderXmlPrivateKey())
            ->getRequest();
    }

    /**
     * @param \SAML2_LogoutRequest $logoutRequest
     * @return \SAML2_LogoutResponse
     */
    protected function buildLogoutResponse(\SAML2_LogoutRequest $logoutRequest)
    {
        $serviceProvider = $this->getServiceProvider($logoutRequest->getIssuer());

        $logoutResponseBuilder = new LogoutResponseBuilder();

        return $logoutResponseBuilder
            ->setInResponseTo($logoutRequest->getId())
            ->setDestination($serviceProvider->getSingleLogoutUrl())
            ->setIssuer($this->identityProvider->getEntityId())
            ->setSignatureKey($this->getIdentityProviderXmlPrivateKey())
            ->setStatus(\SAML2_Const::STATUS_SUCCESS)
            ->setRelayState($logoutRequest->getRelayState())
            ->getResponse();
    }

    /**
     * @param $entityId
     * @return ServiceProvider
     */
    protected function getServiceProvider($entityId)
    {
        return $this->serviceProviderRepository->getServiceProvider($entityId);
    }

    /**
     * @return \XMLSecurityKey
     */
    protected function getIdentityProviderXmlPrivateKey()
    {
        /** @var \SAML2_Configuration_PrivateKey $privateKey */
        $privateKey = $this->identityProvider->getPrivateKey("default");
        $xmlPrivateKey = new \XMLSecurityKey(\XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $xmlPrivateKey->loadKey($privateKey->getFilePath(), true);

        return $xmlPrivateKey;
    }

    /**
     * @return \XMLSecurityKey
     */
    protected function getIdentityProviderXmlPublicKey()
    {
        $publicFileCert = $this->identityProvider->getCertificateFile();
        $xmlPublicKey = new \XMLSecurityKey(\XMLSecurityKey::RSA_SHA256, ['type' => 'public']);
        $xmlPublicKey->loadKey($publicFileCert, true, true);

        return $xmlPublicKey;
    }

    /**
     * @param \SAML2_Message $message
     */
    protected function validateMessage(\SAML2_Message $message)
    {
        if (!$this->serviceProviderRepository->hasServiceProvider($message->getIssuer())) {
            throw new UnknownServiceProviderException($message->getIssuer());
        }

        $serviceProvider = $this->getServiceProvider($message->getIssuer());

        $this->logger->debug(sprintf('Extracting public keys for ServiceProvider "%s"', $serviceProvider->getEntityId()));

        $keys = $this->publicKeyLoader->extractPublicKeys($serviceProvider);

        $this->logger->debug(sprintf('Found "%d" keys, filtering the keys to get X509 keys', $keys->count()));
        $x509Keys = $keys->filter(function (\SAML2_Certificate_Key $key) {
            return $key instanceof \SAML2_Certificate_X509;
        });

        $this->logger->debug(sprintf(
            'Found "%d" X509 keys, attempting to use each for signature verification',
            $x509Keys->count()
        ));

        /** @var \SAML2_Certificate_X509[] $x509Keys */
        foreach ($x509Keys as $x509Key) {
            $key = new \XMLSecurityKey(\XMLSecurityKey::RSA_SHA256, array('type' => 'public'));
            $key->loadKey($x509Key->getCertificate());

            $message->validate($key);
        }
    }
}
