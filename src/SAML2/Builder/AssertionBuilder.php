<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Builder;


class AssertionBuilder
{
    /**
     * @var \SAML2_Assertion
     */
    protected $assertion;

    /**
     * @var \DateTime
     */
    protected $issueInstant;

    /**
     * AssertionBuilder constructor.
     * @param \DateTime|null $issueInstant
     */
    public function __construct(\DateTime $issueInstant = null)
    {
        $this->assertion = new \SAML2_Assertion();

        $this->issueInstant = $issueInstant === null ? new \DateTime('now', new \DateTimeZone('UTC')): $issueInstant;

        $this->assertion->setNotBefore($this->issueInstant->getTimestamp());
        $this->assertion->setIssueInstant($this->issueInstant->getTimestamp());

        // Add default bearer confirmation
        $confirmation = new \SAML2_XML_saml_SubjectConfirmation();
        $confirmation->Method = \SAML2_Const::CM_BEARER;

        $confirmationData = new \SAML2_XML_saml_SubjectConfirmationData();
        $confirmationData->NotBefore = $this->issueInstant->getTimestamp();

        $confirmation->SubjectConfirmationData = $confirmationData;

        $this->assertion->setSubjectConfirmation([$confirmation]);
    }

    /**
     * @return \SAML2_Assertion
     */
    public function getAssertion(){
        return $this->assertion;
    }

    /**
     * @return \DateTime|null
     */
    public function getIssueInstant(){
        return $this->issueInstant;
    }

    /**
     * @param \DateInterval $interval
     * @return $this
     */
    public function setNotOnOrAfter(\DateInterval $interval){
        $endTime = clone $this->issueInstant;
        $endTime->add($interval);

        $this->assertion->setNotOnOrAfter($endTime->getTimestamp());

        $confirmation = $this->assertion->getSubjectConfirmation()[0];
        $confirmation->SubjectConfirmationData->NotOnOrAfter = $endTime->getTimestamp();
        $this->assertion->setSubjectConfirmation([$confirmation]);

        return $this;
    }

    /**
     * @param \DateInterval $interval
     * @return $this
     */
    public function setSessionNotOnOrAfter(\DateInterval $interval){
        $sessionEndTime = clone $this->issueInstant;
        $sessionEndTime->add($interval);

        $this->assertion->setSessionNotOnOrAfter($sessionEndTime->getTimestamp());
        $confirmation = $this->assertion->getSubjectConfirmation()[0];
        $confirmation->SubjectConfirmationData->NotOnOrAfter = $sessionEndTime->getTimestamp();
        $this->assertion->setSubjectConfirmation([$confirmation]);

        return $this;
    }

    /**
     * @param array $attributes
     * @return $this
     */
    public function setAttributes(array $attributes){
        $this->assertion->setAttributes($attributes);

        return $this;
    }

    /**
     * @param string $name
     * @param $value
     * @return AssertionBuilder
     */
    public function setAttribute($name, $value){
        $attributes = $this->assertion->getAttributes();
        $attributes[$name] = $value;

        return $this->setAttributes($attributes);
    }

    /**
     * @param $value
     * @param $format
     * @return $this
     */
    public function setNameId($value, $format) {
        $this->assertion->setNameId([
            "Value" => $value,
            "Format" => $format
        ]);

        return $this;
    }

    /**
     * @param $issuer
     * @return $this
     */
    public function setIssuer($issuer){
        $this->assertion->setIssuer($issuer);

        return $this;
    }
}