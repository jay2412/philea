<?php

namespace Cnes\PhileaBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class UserType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('username')
            ->add('email')
            ->add('enabled')
            ->add('password')
            ->add('locked','checkbox',array('required'=> false))
            ->add('roles', 'collection', array(
                'type'   => 'choice',
                'options'  => array(
                'choices'  => array('ROLE_GESTIONNAIRE' => 'Gestionnaire', 'ROLE_REDACTEUR' => 'Rédacteur'),
                    'label' => false,)))
            ->add('nom')
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Cnes\PhileaBundle\Entity\User'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'cnes_phileabundle_user';
    }
}
