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
     * AuthnResponseBuilder constructor.
     * @param \DateTime|null $issueInstant
     */
    public function __construct(\DateTime $issueInstant = null)
    {
        $this->assertionBuilders = [];

        parent::__construct($issueInstant);
    }

    /**
     * @return \SAML2_Response
     */
    public function getResponse()
    {
        $assertions = [];
        foreach ($this->assertionBuilders as $assertionBuilder){
            $assertions[] = $assertionBuilder->getAssertion();
        }

        $this->response->setAssertions($assertions);

        return $this->response;
    }

    /**
     * @return void
     */
    protected function createResponseInstance()
    {
        $this->response = new \SAML2_Response();
    }

    /**
     * @param AssertionBuilder[] $assertionBuilders
     * @return $this
     */
    public function setAssertionBuilders(array $assertionBuilders){
        $this->assertionBuilders = [];

        foreach ($assertionBuilders as $assertionBuilder){
            $this->addAssertionBuilder($assertionBuilder);
        }

        return $this;
    }

    /**
     * @param AssertionBuilder $assertion
     * @return $this
     */
    public function addAssertionBuilder(AssertionBuilder $assertion){
        $this->assertionBuilders[] = $assertion;

        return $this;
    }

    /**
     * @return AssertionBuilder[]
     */
    public function getAssertionBuilders(){
        return $this->assertionBuilders;
    }
}