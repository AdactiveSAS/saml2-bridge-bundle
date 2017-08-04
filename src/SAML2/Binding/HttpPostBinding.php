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


use AdactiveSas\Saml2BridgeBundle\Exception\LogicException;
use AdactiveSas\Saml2BridgeBundle\Form\SAML2ResponseForm;
use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\UnsupportedBindingException;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpPostBinding implements HttpBindingInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var EngineInterface
     */
    protected $templateEngine;

    /**
     * HttpPostBinding constructor.
     */
    public function __construct(FormFactoryInterface $formFactory, EngineInterface $templateEngine)
    {
        $this->formFactory = $formFactory;
        $this->templateEngine = $templateEngine;
    }

    /**
     * @param \SAML2_StatusResponse $response
     * @return Response
     */
    public function getSignedResponse(\SAML2_StatusResponse $response)
    {
        $form = $this->getSignedResponseForm($response);

        return $this->templateEngine->renderResponse(
            "AdactiveSasSaml2BridgeBundle:Binding:post.html.twig",
            [
                "form" => $form->createView()
            ]
        );
    }

    /**
     * @param \SAML2_StatusResponse $response
     * @return Response
     */
    public function getUnsignedResponse(\SAML2_StatusResponse $response)
    {
        throw new UnsupportedBindingException("Unsupported binding: unsigned POST Response is not supported at the moment");
    }

    /**
     * @param \SAML2_Request $request
     * @return Response
     */
    public function getSignedRequest(\SAML2_Request $request)
    {
        throw new UnsupportedBindingException("Unsupported binding: unsigned POST Request is not supported at the moment");
    }

    /**
     * @param \SAML2_Request $request
     * @return Response
     */
    public function getUnsignedRequest(\SAML2_Request $request)
    {
        throw new UnsupportedBindingException("Unsupported binding: unsigned POST Request is not supported at the moment");
    }

    /**
     * @param Request $request
     * @return \SAML2_AuthnRequest
     */
    public function receiveSignedAuthnRequest(Request $request)
    {
        throw new UnsupportedBindingException("Unsupported binding: signed POST AuthnRequest is not supported at the moment");
    }

    /**
     * @param Request $request
     * @return \SAML2_LogoutRequest
     */
    public function receiveSignedLogoutRequest(Request $request)
    {
        throw new UnsupportedBindingException("Unsupported binding: signed POST LogoutRequest is not supported at the moment");
    }

    /**
     * @param Request $request
     * @return \SAML2_AuthnRequest
     */
    public function receiveUnsignedAuthnRequest(Request $request)
    {
        throw new UnsupportedBindingException("Unsupported binding: unsigned POST AuthnRequest is not supported at the moment");
    }

    /**
     * @param Request $request
     * @return \SAML2_LogoutRequest
     */
    public function receiveUnsignedLogoutRequest(Request $request)
    {
        throw new UnsupportedBindingException("Unsupported binding: unsigned POST LogoutRequest is not supported at the moment");
    }

    /**
     * @param Request $request
     * @return \SAML2_Message
     */
    public function receiveSignedMessage(Request $request)
    {
        throw new UnsupportedBindingException("Unsupported binding: signed POST Request is not supported at the moment");
    }

    /**
     * @param Request $request
     * @return \SAML2_Message
     */
    public function receiveUnsignedMessage(Request $request)
    {
        throw new UnsupportedBindingException("Unsupported binding: unsigned POST Request is not supported at the moment");
    }

    /**
     * @param \SAML2_StatusResponse $response
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getSignedResponseForm(\SAML2_StatusResponse $response)
    {
        if($response->getDestination() === null){
            throw new LogicException('Invalid destination');
        }

        $data = [
            'SAMLResponse' => base64_encode($response->toSignedXML()->ownerDocument->saveXML()),
        ];

        $hasRelayState = !empty($response->getRelayState());
        if($hasRelayState){
            $data["RelayState"] = $response->getRelayState();
        }

        return $this->formFactory->createNamed(
            "",
            SAML2ResponseForm::class,
            $data,
            [
            "has_relay_state"=> $hasRelayState,
            "destination" => $response->getDestination()
            ]
        );
    }

    /**
     * @param \SAML2_StatusResponse $response
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getUnsignedResponseForm(\SAML2_StatusResponse $response)
    {
        $data = [
            'SAMLResponse' => base64_encode($response->toUnsignedXML()->ownerDocument->saveXML()),
        ];

        $hasRelayState = !empty($response->getRelayState());
        if($hasRelayState){
            $data["RelayState"] = $response->getRelayState();
        }

        return $this->formFactory->create(
            SAML2ResponseForm::class,
            $data,
            [
            "has_relay_state"=> $hasRelayState,
            "destination" => $response->getDestination()
            ]
        );
    }
}