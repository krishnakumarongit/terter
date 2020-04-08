<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class ActivateController extends AbstractController
{
    public function index(Request $request, String $token)
    {
    	$subject = '';
    	if ($token != "") {
	    	//activate user with 
	    	$entityManager = $this->getDoctrine()->getManager();
	    	$user = $entityManager->getRepository(User::class)->findOneBy([
    		   'email_token' => $token
			]);
	        if (!$user) {
	        	$subject = 'An error occured while processing your request. Please try again later';
	        } else {
	        	$subject = 'Your account has been activated successfully.';
	        	$user->setEmailValid(1);
	        	$user->setEmailToken('');
   				$entityManager->persist($user);
    			$entityManager->flush();
	        }
    	} else {
    		$subject = 'An error occured while processing your request. Please try again later';
    	} 

    	return $this->render('signup/activation.html.twig', [
        	'message' => $subject 
        ]);
    }


    public function success(Request $request)
    {
        return $this->render('signup/reset.html.twig', []);
    }



    public function reset (Request $request, String $token, UserPasswordEncoderInterface $passwordEncoder) 
    {
        if ($token != '') {
            $entityManager = $this->getDoctrine()->getManager();
            $user = $entityManager->getRepository(User::class)->findOneBy([
               'token' => $token
            ]);
            if (!$user) {
                die('Unable to process your request. Please try again.');
            }
            $defaultData = ['message' => ''];
            $form = $this->createFormBuilder($defaultData)
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'required' => true,
                'constraints' => [
                    new NotBlank(),
                    new Length(['min' => 5,'max' => 255]),
                ],
                'invalid_message' => 'The password fields must match.',
                'first_options'  => ['label' => 'Password'],
                'second_options' => ['label' => 'Repeat Password'],
            ])
        ->add('submit', SubmitType::class)
        ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
             $data = $form->getData();
             $password = $data['password'];
             $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $password
                )
            );

            $user->setToken('');
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('success', 'Congratulations! Your password has been changed successfully');
            return $this->redirectToRoute('app_login');
        }
        return $this->render('signup/change_password.html.twig', [
            'form' => $form->createView(),
            'formError' => ''
        ]);
        
        }
    }
}