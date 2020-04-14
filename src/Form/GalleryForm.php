<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class GalleryForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
            	 'constraints' => [
                    new Assert\NotNull([
						'message' => 'Pets name cannot be empty'
					]),
					new Assert\Length([
						'min' => 3,
						'max' => 15,
						'minMessage' => 'Pets name must be at least {{ limit }} characters long',
						'maxMessage' => 'Pets name cannot be longer than {{ limit }} characters',
						'allowEmptyString' => false,
					])
                ]
            ])
            ->add('type', ChoiceType::class, [
					'choices'  => [
						'Select' => null,
						'Bird' => 'Bird',
						'Cat' => 'Cat',
						'Dog' => 'Dog',
						'Fish' => 'Fish',
						'Horse' => 'Horse',
						'Invertebrate' => 'Invertebrate',
						'Poultry' => 'Poultry',
						'Rabbit' => 'Rabbit',
						'Reptile' => 'Reptile',
						'Rodent' => 'Rodent',
					],
            	'constraints' => [
                    new Assert\NotNull([
						'message' => 'Pet type cannot be empty'
					]),
                ]
            ])
			->add('image', FileType::class, [
				'data_class' => null,
				'required' => false,
				'constraints' => [
                    new Assert\File([
                        'maxSize' => '10000k',
                        'mimeTypes' => [
                            'image/jpg',
                            'image/jpe',
                            'image/jpeg',
                            'image/pjpeg',
                            'image/gif',
                            'image/png'
                        ],
                        'mimeTypesMessage' => 'Uploaded file is not a valid image. Only JPG, PNG and GIF files are allowed',
                    ])
                ],
			])
			->add('submit', SubmitType::class);
    }
  
}