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