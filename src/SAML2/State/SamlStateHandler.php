<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\State;


use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Workflow\DefinitionBuilder;
use Symfony\Component\Workflow\MarkingStore\SingleStateMarkingStore;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\Workflow;

class SamlStateHandler implements EventSubscriberInterface
{
    const SESSION_NAME_ATTRIBUTE = "adactive_sas_saml2_bridge.state";

    const TRANSITION_SSO_START = "sso_transition_start";
    const TRANSITION_SSO_START_AUTHENTICATE = "sso_authenticate_start";
    const TRANSITION_SSO_AUTHENTICATE_SUCCESS = "sso_authenticate_success";
    const TRANSITION_SSO_AUTHENTICATE_FAIL = "sso_authenticate_fail";
    const TRANSITION_SSO_RESPOND = "sso_respond";
    const TRANSITION_SSO_RESUME = "sso_resume";
    
    const TRANSITION_SLS_START = "sls_transition_start";
    const TRANSITION_SLS_START_BY_IDP = "sls_transition_start_by_idp";
    const TRANSITION_SLS_START_DISPATCH = "sls_start_dispatch";
    const TRANSITION_SLS_END_DISPATCH = "sls_end_dispatch";
    const TRANSITION_SLS_START_PROPAGATE = "sls_start_propagate";
    const TRANSITION_SLS_END_PROPAGATE = "sls_end_propagate";
    const TRANSITION_SLS_RESPOND = "sls_respond";
    const TRANSITION_SLS_RESUME = "sls_resume";

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var Workflow
     */
    protected $workflow;

    /**
     * @var SamlState
     */
    protected $state;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var AuthorizationChecker
     */
    protected $authorizationChecker;

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
            KernelEvents::REQUEST => ['onKernelRequest', 12], // Come before firewall
            KernelEvents::RESPONSE => ['onKernelResponse', -8], // Come after HostedIdentityProviderProcessor
        ];
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        // Reload from session
        $state = $this->session->get(self::SESSION_NAME_ATTRIBUTE);
        
        $this->state = $state ? $state : new SamlState();
    }

    /**
     * @param FilterResponseEvent $event
     */
    public function onKernelResponse(FilterResponseEvent $event){
        // Save into session
        $this->session->set(self::SESSION_NAME_ATTRIBUTE, $this->state);
    }

    /**
     * SamlStateHandler constructor.
     * @param Session $session
     * @param TokenStorageInterface $tokenStorage
     * @param AuthorizationChecker $authorizationChecker
     */
    public function __construct(Session $session, TokenStorageInterface $tokenStorage, AuthorizationChecker $authorizationChecker)
    {
        $this->session = $session;
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;

        $this->workflow = $this->buildWorkflow();
    }

    /**
     * @return Workflow
     */
    protected function buildWorkflow(){
        $builder = new DefinitionBuilder();
        $builder->addPlaces([
            SamlState::STATE_INITIAL,

            SamlState::STATE_SSO_STARTED,
            SamlState::STATE_SSO_AUTHENTICATING_START,
            SamlState::STATE_SSO_AUTHENTICATING_FAILED,
            SamlState::STATE_SSO_AUTHENTICATING_SUCCESS,
            SamlState::STATE_SSO_RESPONDING,

            SamlState::STATE_SLS_STARTED,
            SamlState::STATE_SLS_DISPATCH_START,
            SamlState::STATE_SLS_DISPATCH_END,
            SamlState::STATE_SLS_PROPAGATE_START,
            SamlState::STATE_SLS_PROPAGATE_END,
            SamlState::STATE_SLS_RESPONDING,
        ]);

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SSO_START,
                SamlState::STATE_INITIAL,
                SamlState::STATE_SSO_STARTED
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SSO_RESPOND,
                SamlState::STATE_SSO_STARTED,
                SamlState::STATE_SSO_RESPONDING
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SSO_START_AUTHENTICATE,
                SamlState::STATE_SSO_STARTED,
                SamlState::STATE_SSO_AUTHENTICATING_START
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SSO_AUTHENTICATE_SUCCESS,
                SamlState::STATE_SSO_AUTHENTICATING_START,
                SamlState::STATE_SSO_AUTHENTICATING_SUCCESS
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SSO_AUTHENTICATE_FAIL,
                SamlState::STATE_SSO_AUTHENTICATING_START,
                SamlState::STATE_SSO_AUTHENTICATING_FAILED
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SSO_RESPOND,
                SamlState::STATE_SSO_AUTHENTICATING_SUCCESS,
                SamlState::STATE_SSO_RESPONDING
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SSO_RESPOND,
                SamlState::STATE_SSO_AUTHENTICATING_FAILED,
                SamlState::STATE_SSO_RESPONDING
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SSO_RESUME,
                SamlState::STATE_SSO_RESPONDING,
                SamlState::STATE_INITIAL
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SLS_START,
                SamlState::STATE_INITIAL,
                SamlState::STATE_SLS_STARTED
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SLS_START_BY_IDP,
                SamlState::STATE_INITIAL,
                SamlState::STATE_SLS_DISPATCH_END
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SLS_START_DISPATCH,
                SamlState::STATE_SLS_STARTED,
                SamlState::STATE_SLS_DISPATCH_START
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SLS_END_DISPATCH,
                SamlState::STATE_SLS_DISPATCH_START,
                SamlState::STATE_SLS_DISPATCH_END
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SLS_START_PROPAGATE,
                SamlState::STATE_SLS_DISPATCH_END,
                SamlState::STATE_SLS_PROPAGATE_START
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SLS_END_PROPAGATE,
                SamlState::STATE_SLS_PROPAGATE_START,
                SamlState::STATE_SLS_PROPAGATE_END
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SLS_START_PROPAGATE,
                SamlState::STATE_SLS_PROPAGATE_END,
                SamlState::STATE_SLS_PROPAGATE_START
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SLS_RESPOND,
                SamlState::STATE_SLS_PROPAGATE_END,
                SamlState::STATE_SLS_RESPONDING
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SLS_RESPOND,
                SamlState::STATE_SLS_DISPATCH_END,
                SamlState::STATE_SLS_RESPONDING
            )
        );

        $builder->addTransition(
            new Transition(
                self::TRANSITION_SLS_RESUME,
                SamlState::STATE_SLS_RESPONDING,
                SamlState::STATE_INITIAL
            )
        );

        $definition = $builder->build();

        $marking = new SingleStateMarkingStore('state');

        return new Workflow($definition, $marking, null, "adactive_sas.saml");
    }

    /**
     * @param $transition
     * @return $this
     */
    public function apply($transition)
    {
        $this->workflow->apply($this->get(), $transition);

        return $this;
    }

    /**
     * @param $transition
     * @param bool $needRequest
     * @return bool
     */
    public function can($transition, $needRequest = true)
    {
        return $this->has() && (!$needRequest || $this->get()->getRequest() !== null) && $this->workflow->can($this->get(), $transition);
    }

    /**
     * @return bool
     */
    public function has(){
        return $this->get() !== null;
    }

    /**
     * @return SamlState
     */
    public function get()
    {
        return $this->state;
    }

    /**
     * @param bool $force
     * @return $this
     */
    public function resume($force = false)
    {
        $canSsoResume = $this->can(SamlStateHandler::TRANSITION_SSO_RESUME);
        $canSlsResume = $this->can(SamlStateHandler::TRANSITION_SLS_RESUME);
        if ($force && !$canSsoResume && !$canSlsResume){
            $this->get()->setState(SamlState::STATE_INITIAL);
        }else if($canSsoResume){
            $this->apply(SamlStateHandler::TRANSITION_SSO_RESUME);
        }else {
            // will trigger if it's not allowed !
            $this->apply(SamlStateHandler::TRANSITION_SLS_RESUME);
        }

        $this->get()->setRequest(null)->setOriginalLogoutResponse(null);

        return $this;
    }

    /**
     * @return bool
     */
    public function isAuthenticated()
    {
        return $this->tokenStorage->getToken() === null ||
            !$this->authorizationChecker->isGranted('IS_AUTHENTICATED_REMEMBERED');
    }

    /**
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->tokenStorage->getToken()->getUser();
    }
}
