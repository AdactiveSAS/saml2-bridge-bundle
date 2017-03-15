<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Event;


use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

class LogoutEvent extends Event
{
    /**
     * @var UserInterface
     */
    protected $user;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @param LogoutEvent $event
     * @return LogoutEvent
     */
    public static function fromLogoutEvent(LogoutEvent $event){
        return new self($event->getUser(), $event->getResponse());
    }

    /**
     * AuthenticationEvent constructor.
     * @param UserInterface $user
     * @param Response $response
     */
    public function __construct(UserInterface $user, Response $response)
    {
        $this->user = $user;
        $this->response = $response;
    }

    /**
     * @return UserInterface
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * @return Response
     */
    public function getResponse(){
        return $this->response;
    }
}