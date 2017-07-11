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
     * @param array $attributes
     * @return $this
     */
    public function setAttributesNameFormat($nameFormat = \SAML2_Const::NAMEFORMAT_UNSPECIFIED){
        $this->assertion->setAttributeNameFormat($nameFormat);

        return $this;
    }

    /**
     * @param string $name
     * @param $value
     * @return AssertionBuilder
     */
    public function setAttribute($name, $value){
        $attributes = $this->assertion->getAttributes();
        $attributes[$name] = [$value];

        return $this->setAttributes($attributes);
    }

    /**
     * @param $value
     * @param $format
     * @return $this
     */
    public function setNameId($value, $format, $nameQualifier, $spNameQualifier) {
        $this->assertion->setNameId([
            "Value" => $value,
            "Format" => $format,
            "NameQualifier" => $nameQualifier,
            "SPNameQualifier" => $spNameQualifier,
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function setSubjectConfirmation($method = \SAML2_Const::CM_BEARER, $inResponseTo, \DateInterval $notOnOrAfter, $recipient) {
        $subjectConfirmationData = new \SAML2_XML_saml_SubjectConfirmationData();
        $subjectConfirmationData->InResponseTo = $inResponseTo;

        $endTime = clone $this->issueInstant;
        $endTime->add($notOnOrAfter);
        $subjectConfirmationData->NotOnOrAfter = $endTime->getTimestamp();

        $subjectConfirmationData->Recipient = $recipient;

        $subjectConformation = new \SAML2_XML_saml_SubjectConfirmation();
        $subjectConformation->Method = $method;
        $subjectConformation->SubjectConfirmationData = $subjectConfirmationData;
        $this->assertion->setSubjectConfirmation([$subjectConformation]);

        return $this;
    }
    /**
     * @return $this
     */
    public function setAuthnContext($authnContext = \SAML2_Const::AC_PASSWORD) {
        $this->assertion->setAuthnContextClassRef($authnContext);

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

    /**
     * @param \XMLSecurityKey $privateKey
     * @param \XMLSecurityKey $publicCert
     */
    public function sign(\XMLSecurityKey $privateKey, \XMLSecurityKey $publicCert)
    {
        $element = $this->assertion;
        $element->setSignatureKey($privateKey);
        $element->setCertificates([$publicCert->getX509Certificate()]);
    }
}
