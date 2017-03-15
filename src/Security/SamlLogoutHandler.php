<?php

namespace AdactiveSas\Saml2BridgeBundle\Security;


use AdactiveSas\Saml2BridgeBundle\SAML2\Event\LogoutEvent;
use AdactiveSas\Saml2BridgeBundle\SAML2\Event\Saml2Events;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class SamlLogoutHandler implements LogoutHandlerInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * SamlLogoutHandler constructor.
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(EventDispatcherInterface $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * This method is called by the LogoutListener when a user has requested
     * to be logged out. Usually, you would unset session variables, or remove
     * cookies, etc.
     *
     * @param Request $request
     * @param Response $response
     * @param TokenInterface $token
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        $event = new LogoutEvent($token->getUser(), $response);
        $this->dispatcher->dispatch(Saml2Events::SLO_LOGOUT_SUCCESS, $event);
    }
}