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

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Event;


class Saml2Events
{
    const SSO_AUTHN_SUCCESS = "adactive_sas_saml2.sso_authn_success";
    const SLO_LOGOUT_SUCCESS = "adactive_sas_saml2.slo_logout_success";
    const SSO_AUTHN_GET_RESPONSE = "adactive_sas_saml2.sso_authn_get_response";
    const SSO_AUTHN_RECEIVE_REQUEST = "adactive_sas_saml2.sso_authn_receive_request";
}
