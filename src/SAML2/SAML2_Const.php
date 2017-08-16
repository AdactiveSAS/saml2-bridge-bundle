<?php
/**
 * Created by PhpStorm.
 * User: moroine
 * Date: 14/08/17
 * Time: 16:28
 */

namespace AdactiveSas\Saml2BridgeBundle\SAML2;


class SAML2_Const extends \SAML2_Const
{

    /**
     * Password protected transport authentication context.
     */
    const AC_PASSWORD_PROTECTED_TRANSPORT = 'urn:oasis:names:tc:SAML:2.0:ac:classes:PasswordProtectedTransport';

    /**
     * Previous session authentication context.
     */
    const AC_PREVIOUS_SESSION = 'urn:oasis:names:tc:SAML:2.0:ac:classes:PreviousSession';

}