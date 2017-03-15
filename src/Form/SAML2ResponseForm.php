<?php

namespace AdactiveSas\Saml2BridgeBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SAML2ResponseForm extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->setAction($options["destination"])
            ->add("SAMLResponse", "hidden");

        if ($options["has_relay_state"]) {
            $builder->add("RelayState", "hidden");
        }

        $builder->add('submit', 'submit');
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setRequired("has_relay_state")
            ->setAllowedTypes("has_relay_state", "bool");

        $resolver
            ->setRequired("destination")
            ->setAllowedTypes("destination", "string");
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'adactive_sas_saml2_response_form';
    }
}
