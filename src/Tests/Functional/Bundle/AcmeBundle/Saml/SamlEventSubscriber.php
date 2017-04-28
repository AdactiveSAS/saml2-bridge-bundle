<?php

namespace AdactiveSas\Saml2BridgeBundle\Tests\Functional\Bundle\AcmeBundle\Saml;


use AdactiveSas\Saml2BridgeBundle\SAML2\Event\GetAuthnResponseEvent;
use AdactiveSas\Saml2BridgeBundle\SAML2\Event\Saml2Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SamlEventSubscriber implements EventSubscriberInterface
{

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
            Saml2Events::SSO_AUTHN_GET_RESPONSE => "onGetAuthnResponse"
        ];
    }

    /**
     * @param GetAuthnResponseEvent $event
     */
    public function onGetAuthnResponse(GetAuthnResponseEvent $event)
    {
        $builder = $event->getAuthnResponseBuilder();
        $assertionBuilder = $builder->getDefaultAssertionBuilder();
        $assertionBuilder->setAttribute("email", ["moroine.bentefrit@gmail.com"]);
    }
}