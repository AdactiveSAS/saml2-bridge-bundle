<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Builder;


abstract class AbstractRequestBuilder
{
    /**
     * @return void
     */
    abstract protected function createRequestInstance();

    /**
     * @return \SAML2_Request
     */
    abstract public function getRequest();

    /**
     * AuthnResponseFactory constructor.
     * @param \DateTime|null $issueInstant
     */
    public function __construct(\DateTime $issueInstant = null)
    {
        $this->createRequestInstance();

        $this->issueInstant = $issueInstant === null ? new \DateTime('now', new \DateTimeZone(\DateTimeZone::UTC)): $issueInstant;

        $this->getRequest()->setIssueInstant($this->issueInstant->getTimestamp());
    }

    /**
     * @return \DateTime|null
     */
    public function getIssueInstant(){
        return $this->issueInstant;
    }

    /**
     * @param $issuer
     * @return $this
     */
    public function setIssuer($issuer){
        $this->getRequest()->setIssuer($issuer);

        return $this;
    }

    /**
     * @param $destination
     * @return $this
     */
    public function setDestination($destination){
        $this->getRequest()->setDestination($destination);

        return $this;
    }

    /**
     * @param \XMLSecurityKey $key
     * @return $this
     */
    public function setSignatureKey(\XMLSecurityKey $key){
        $this->getRequest()->setSignatureKey($key);

        return $this;
    }

    /**
     * @param $relayState
     * @return $this
     */
    public function setRelayState($relayState){
        $this->getRequest()->setRelayState($relayState);

        return $this;
    }
}