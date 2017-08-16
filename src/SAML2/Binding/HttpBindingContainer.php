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

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Binding;


use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\UnsupportedBindingException;

class HttpBindingContainer
{
    /**
     * @var HttpRedirectBinding
     */
    protected $redirectBinding;

    /**
     * @var HttpPostBinding
     */
    protected $postBinding;

    /**
     * HttpBindingContainer constructor.
     * @param HttpRedirectBinding $redirectBinding
     * @param HttpPostBinding $postBinding
     */
    public function __construct(HttpRedirectBinding $redirectBinding, HttpPostBinding $postBinding)
    {
        $this->redirectBinding = $redirectBinding;
        $this->postBinding = $postBinding;
    }

    /**
     * @param $binding
     * @return HttpBindingInterface
     */
    public function get($binding){
        switch ($binding){
            case \SAML2_Const::BINDING_HTTP_REDIRECT:
                return $this->redirectBinding;
            case \SAML2_Const::BINDING_HTTP_POST:
                return $this->postBinding;
            default:
                throw new UnsupportedBindingException("Unsupported binding: ". $binding);
        }
    }
}