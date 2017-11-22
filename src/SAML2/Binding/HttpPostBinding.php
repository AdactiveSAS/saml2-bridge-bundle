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


use AdactiveSas\Saml2BridgeBundle\Exception\BadRequestHttpException;
use AdactiveSas\Saml2BridgeBundle\Exception\LogicException;
use AdactiveSas\Saml2BridgeBundle\Form\SAML2ResponseForm;
use AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\UnsupportedBindingException;
use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class HttpPostBinding extends AbstractHttpBinding implements HttpBindingInterface
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
     * @param FormFactoryInterface $formFactory
     * @param EngineInterface $templateEngine
     */
    public function __construct(FormFactoryInterface $formFactory, EngineInterface $templateEngine)
    {
        $this->formFactory = $formFactory;
        $this->templateEngine = $templateEngine;
    }

    /**
     * @param \SAML2_StatusResponse $response
     * @return Response
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @throws \RuntimeException
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
     * @throws \RuntimeException
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function getUnsignedResponse(\SAML2_StatusResponse $response)
    {
        $form = $this->getUnsignedResponseForm($response);

        return $this->templateEngine->renderResponse(
            "AdactiveSasSaml2BridgeBundle:Binding:post.html.twig",
            [
                "form" => $form->createView(),
            ]
        );
    }

    /**
     * @param \SAML2_StatusResponse $response
     * @return \Symfony\Component\Form\FormInterface
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function getSignedResponseForm(\SAML2_StatusResponse $response)
    {
        return $this->getResponseForm($response, true);
    }

    /**
     * @param \SAML2_StatusResponse $response
     * @return \Symfony\Component\Form\FormInterface
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    public function getUnsignedResponseForm(\SAML2_StatusResponse $response)
    {
        return $this->getResponseForm($response, false);
    }

    /**
     * @param \SAML2_Request $request
     * @return Response
     * @throws \AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\UnsupportedBindingException
     */
    public function getUnsignedRequest(\SAML2_Request $request)
    {
        throw new UnsupportedBindingException("Unsupported binding: unsigned POST Request is not supported at the moment");
    }

    /**
     * @param Request $request
     * @return ReceivedMessageQueryString
     * @throws \AdactiveSas\Saml2BridgeBundle\SAML2\Binding\Exception\InvalidReceivedMessageQueryStringException
     * @throws \AdactiveSas\Saml2BridgeBundle\Exception\BadRequestHttpException
     */
    protected function getReceivedMessageQueryString(Request $request)
    {
        if (!$request->isMethod(Request::METHOD_POST)) {
            throw new BadRequestHttpException(sprintf(
                'Could not receive Message from HTTP Request: expected a POST method, got %s',
                $request->getMethod()
            ));
        }

        $requestParams = $request->request->all();

        return ReceivedMessageQueryString::parse($requestParams);
    }

    /**
     * @param \SAML2_StatusResponse $response
     * @param $isSign
     * @return \Symfony\Component\Form\FormInterface
     * @throws \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     */
    protected function getResponseForm(\SAML2_StatusResponse $response, $isSign)
    {
        if ($response->getDestination() === null) {
            throw new LogicException('Invalid destination');
        }

        $xmlDom = $isSign ? $response->toSignedXML() : $response->toUnsignedXML();

        $data = [
            'SAMLResponse' => base64_encode($xmlDom->ownerDocument->saveXML()),
        ];

        $hasRelayState = !empty($response->getRelayState());
        if ($hasRelayState) {
            $data["RelayState"] = $response->getRelayState();
        }

        return $this->formFactory->createNamed(
            "",
            SAML2ResponseForm::class,
            $data,
            [
            "has_relay_state"=> $hasRelayState,
            "destination" => $response->getDestination(),
            ]
        );
    }
}
