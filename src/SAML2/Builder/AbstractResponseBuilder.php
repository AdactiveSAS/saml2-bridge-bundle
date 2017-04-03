<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Builder;


abstract class AbstractResponseBuilder
{
    /**
     * @var \DateTime
     */
    private $issueInstant;

    /**
     * @return void
     */
    abstract protected function createResponseInstance();

    /**
     * @return \SAML2_StatusResponse
     */
    abstract public function getResponse();

    /**
     * AuthnResponseFactory constructor.
     * @param \DateTime|null $issueInstant
     */
    public function __construct(\DateTime $issueInstant = null)
    {
        $this->createResponseInstance();

        $this->issueInstant = $issueInstant === null ? new \DateTime('now', new \DateTimeZone('UTC')): $issueInstant;

        $this->getResponse()->setIssueInstant($this->issueInstant->getTimestamp());
    }

    /**
     * @return \DateTime|null
     */
    public function getIssueInstant(){
        return $this->issueInstant;
    }

    /**
     * @param string $code
     * @param string|null $subCode
     * @param string|null $message
     * @return $this
     */
    public function setStatus($code, $subCode = null, $message = null)
    {
        $this->getResponse()->setStatus(
            [
                "Code" => $code,
                "SubCode" => $subCode,
                "Message" => $message
            ]
        );

        return $this;
    }

    /**
     * @param $issuer
     * @return $this
     */
    public function setIssuer($issuer){
        $this->getResponse()->setIssuer($issuer);

        return $this;
    }

    /**
     * @param $destination
     * @return $this
     */
    public function setDestination($destination){
        $this->getResponse()->setDestination($destination);

        return $this;
    }

    /**
     * @param $inResponseTo
     * @return $this
     */
    public function setInResponseTo($inResponseTo){
        $this->getResponse()->setInResponseTo($inResponseTo);

        return $this;
    }

    /**
     * @param \XMLSecurityKey $key
     * @return $this
     */
    public function setSignatureKey(\XMLSecurityKey $key){
        $this->getResponse()->setSignatureKey($key);

        return $this;
    }

    /**
     * @param $relayState
     * @return $this
     */
    public function setRelayState($relayState){
        $this->getResponse()->setRelayState($relayState);

        return $this;
    }
}