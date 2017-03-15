<?php

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
use AdactiveSas\Saml2BridgeBundle\SAML2\Event\Saml2Events;
use AdactiveSas\Saml2BridgeBundle\SAML2\Metadata\MetadataFactory;
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
     * @param ServiceProviderRepository $serviceProviderRepository
     * @param HostedIdentityProvider $identityProvider
     * @param HttpBindingContainer $bindingContainer
     * @param SamlStateHandler $stateHandler
     * @param EventDispatcherInterface $eventDispatcher
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

        if ($this->stateHandler->can(SamlStateHandler::TRANSITION_SSO_RESPOND)) {
            $event->setResponse($this->continueSingleSignOn());
            return;
        }

        if ($this->stateHandler->can(SamlStateHandler::TRANSITION_SLS_RESPOND)) {
            $event->setResponse($this->continueSingleLogoutService());
            return;
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

        $user = $event->getAuthenticationToken()->getUser();
        if($user instanceof UserInterface){
            $this->stateHandler->get()->setUserName($user->getUsername());
        }

        if (!$this->stateHandler->can(SamlStateHandler::TRANSITION_SSO_AUTHENTICATE_SUCCESS)) {
            $this->logger->debug("Cannot perform authentication success");
            return;
        }

        $this->logger->notice("Authentication succeed");
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

            $this->stateHandler
                ->apply(SamlStateHandler::TRANSITION_SLS_START)
                ->apply(SamlStateHandler::TRANSITION_SLS_START_DISPATCH)
                ->apply(SamlStateHandler::TRANSITION_SLS_END_DISPATCH);

            return;
        }

        $this->logger->notice("Logout success");

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
     */
    public function processSingleSignOn(Request $httpRequest)
    {
        $this->stateHandler->resume(true)->apply(SamlStateHandler::TRANSITION_SSO_START);

        $this->logger->notice('Received AuthnRequest, started processing');

        $inputBinding = $this->bindingContainer->get($this->identityProvider->getSsoBinding());

        try {
            $authRequest = $inputBinding->receiveSignedAuthnRequest($httpRequest);
            $this->validateRequest($authRequest);
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

            return $outBinding->getSignedResponse($authnResponse);
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

        $response = $outBinding->getSignedResponse($authnResponse);

        $this->stateHandler->resume();

        return $response;
    }

    /**
     * @param Request $httpRequest
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function processSingleLogoutService(Request $httpRequest)
    {
        $inputBinding = $this->bindingContainer->get($this->identityProvider->getSlsBinding());

        try {
            $logoutMessage = $inputBinding->receiveSignedMessage($httpRequest);
            if ($logoutMessage instanceof \SAML2_LogoutRequest){
                $this->validateRequest($logoutMessage);
            }
        } catch (\Throwable $e) {
            // handle error, apparently the request cannot be processed :(
            $msg = sprintf('Could not process Request, error: "%s"', $e->getMessage());
            $this->logger->critical($msg);

            throw new RuntimeException($msg, 0, $e);
        }

        if ($logoutMessage instanceof \SAML2_LogoutRequest) {
            $this->logger->notice('Received LogoutRequest, started processing');

            $this->stateHandler->resume(true)->apply(SamlStateHandler::TRANSITION_SLS_START);

            $this->stateHandler->get()->setRequest($logoutMessage);

            $sp = $this->getServiceProvider($logoutMessage->getIssuer());
            $this->stateHandler->get()->removeServiceProviderId($sp->getEntityId());

            return $this->continueSingleLogoutService();
        }

        if ($logoutMessage instanceof \SAML2_LogoutResponse) {
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
     */
    public function continueSingleLogoutService()
    {
        $this->logger->notice("Continue SLS process");
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

            $response = $outBinding->getSignedRequest($logoutRequest);

            return $response;
        }

        $this->stateHandler->apply(SamlStateHandler::TRANSITION_SLS_RESPOND);

        /** @var \SAML2_LogoutRequest $logoutRequest */
        $logoutRequest = $this->stateHandler->get()->getRequest();
        if ($logoutRequest !== null) {
            $logoutResponse = $this->buildLogoutResponse($logoutRequest);

            $sp = $this->getServiceProvider($logoutRequest->getIssuer());
            $outBinding = $this->bindingContainer->get($sp->getSingleLogoutBinding());

            $response = $outBinding->getSignedResponse($logoutResponse);

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

        $this->stateHandler->remove();

        return $response;
    }

    /**
     * @param \SAML2_AuthnRequest $authnRequest
     * @return bool
     */
    public function authnRequestNeedLogin(\SAML2_AuthnRequest $authnRequest)
    {
        $isPassive = $authnRequest->getIsPassive();
        $isForce = $authnRequest->getForceAuthn();

        if($isPassive && $isForce)
        {
            throw new InvalidSamlRequestException(
                "Invalid Saml request: cannot be passive and force",
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
                "Invalid Saml request: cannot authenticate passively",
                \SAML2_Const::STATUS_NO_PASSIVE
            );
        }

        return $isAuthenticated;
    }

    /**
     * @param \SAML2_AuthnRequest $authnRequest
     * @return \SAML2_Response
     */
    protected function buildAuthnResponse(\SAML2_AuthnRequest $authnRequest)
    {
        $serviceProvider = $this->getServiceProvider($authnRequest->getIssuer());

        $authnResponseBuilder = new AuthnResponseBuilder();

        $assertionBuilder = new AssertionBuilder();
        $assertionBuilder
            ->setNotOnOrAfter(new \DateInterval('PT5M'))
            ->setSessionNotOnOrAfter(new \DateInterval('P1D'))
            ->setIssuer($this->identityProvider->getEntityId())
            ->setNameId($this->stateHandler->get()->getUserName(), \SAML2_Const::NAMEFORMAT_BASIC);

        $authnResponseBuilder
            ->setStatus(\SAML2_Const::STATUS_SUCCESS)
            ->setIssuer($this->identityProvider->getEntityId())
            ->setRelayState($authnRequest->getRelayState())
            ->setDestination($serviceProvider->getAssertionConsumerUrl())
            ->addAssertionBuilder($assertionBuilder)
            ->setInResponseTo($authnRequest->getId())
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
     * @param \SAML2_Request $request
     */
    protected function validateRequest(\SAML2_Request $request)
    {
        if (!$this->serviceProviderRepository->hasServiceProvider($request->getIssuer())) {
            throw new UnknownServiceProviderException($request->getIssuer());
        }

        $serviceProvider = $this->getServiceProvider($request->getIssuer());

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

            $request->validate($key);
        }
    }
}