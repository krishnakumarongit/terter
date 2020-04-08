<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;

class Signup extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', TextType::class, [])
			->add('password', RepeatedType::class, [
				'type' => PasswordType::class,
				'required' => true,
				'invalid_message' => 'The password fields must match.',
				'first_options'  => ['label' => 'Password'],
				'second_options' => ['label' => 'Repeat Password'],
			])
			->add('create_account', SubmitType::class)
        ;
    }
  
}