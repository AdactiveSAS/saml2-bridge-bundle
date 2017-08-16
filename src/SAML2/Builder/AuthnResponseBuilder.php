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