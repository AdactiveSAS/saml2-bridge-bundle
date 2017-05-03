<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Builder;


class AuthnResponseBuilder extends AbstractResponseBuilder
{
    /**
     * @var \SAML2_Response
     */
    protected $response;

    /**
     * @var AssertionBuilder[]
     */
    protected $assertionBuilders;

    /**
     * @var bool
     */
    protected $wantSignedAssertions;

    /**
     * AuthnResponseBuilder constructor.
     * @param \DateTime|null $issueInstant
     */
    public function __construct(\DateTime $issueInstant = null)
    {
        $this->assertionBuilders = [];
        $this->wantSignedAssertions = false;

        parent::__construct($issueInstant);
    }

    /**
     * @return \SAML2_Response
     */
    public function getResponse()
    {
        $assertions = [];
        $key = $this->getSignatureKey();
        foreach ($this->assertionBuilders as $assertionBuilder) {
            $assertion = $assertionBuilder->getAssertion();

            if($this->wantSignedAssertions()){
                $assertion->setSignatureKey($key);
            }

            $assertions[] = $assertion;
        }

        if(null !== $key){
            $this->response->setSignatureKey($key);
        }

        $this->response->setAssertions($assertions);

        return $this->response;
    }

    /**
     * @return AssertionBuilder[]
     */
    public function getAssertionBuilders()
    {
        return $this->assertionBuilders;
    }

    /**
     * @return AssertionBuilder
     */
    public function getDefaultAssertionBuilder()
    {
        return count($this->assertionBuilders) === 0 ? null : $this->assertionBuilders[0];
    }

    /**
     * @param AssertionBuilder[] $assertionBuilders
     * @return $this
     */
    public function setAssertionBuilders(array $assertionBuilders)
    {
        $this->assertionBuilders = [];

        foreach ($assertionBuilders as $assertionBuilder) {
            $this->addAssertionBuilder($assertionBuilder);
        }

        return $this;
    }

    /**
     * @param AssertionBuilder $assertion
     * @return $this
     */
    public function addAssertionBuilder(AssertionBuilder $assertion)
    {
        if (!in_array($assertion, $this->assertionBuilders, true)) {
            $this->assertionBuilders[] = $assertion;
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function wantSignedAssertions(){
        return $this->wantSignedAssertions;
    }

    /**
     * @param bool $value
     * @return $this
     */
    public function setWantSignedAssertions($value){
        $this->wantSignedAssertions = $value;

        return $this;
    }

    /**
     * @return void
     */
    protected function createResponseInstance()
    {
        $this->response = new \SAML2_Response();
    }
}