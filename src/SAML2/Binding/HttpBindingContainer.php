<?php

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