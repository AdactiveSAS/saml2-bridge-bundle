<?php

namespace AdactiveSas\Saml2BridgeBundle\SAML2\Event;


class Saml2Events
{
    const SSO_AUTHN_SUCCESS = "adactive_sas_saml2.sso_authn_success";
    const SLO_LOGOUT_SUCCESS = "adactive_sas_saml2.slo_logout_success";
    const SSO_AUTHN_GET_RESPONSE = "adactive_sas_saml2.sso_authn_get_response";
}