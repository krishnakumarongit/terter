<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Form\Signup;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Service\MailService;


class SignupController extends AbstractController
{
    public function index(Request $request,  UserPasswordEncoderInterface $passwordEncoder, MailService $mailService)
    {
    	$formError = 0;
    	$user  = new User();
    	$form = $this->createForm(Signup::class, $user);
    	$form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        	// encode the plain password
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $form->get('password')->getData()
                )
            );
            //setting default value
            $user->setEmailValid(0);
            $user->setRoles(['ROLE_USER']);
            $user->setRegisteredOn(\DateTime::createFromFormat('Y-m-d H:i:s',date('Y-m-d H:i:s')));
            //save data
        	$entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();
            $user = $entityManager->getRepository(User::class)->find($user->getId());
            if (!$user) {
        		throw $this->createNotFoundException(
            		'No user found for id '
        		);
   			}

   			$userEmail = $user->getEmail();
   			//used to store session in
   			$emailToken = md5($user->getId().'-'.$user->getEmail().'-'.time());
            $user->setToken('');
            //used for email verification
            $user->setEmailToken($emailToken);
   			$entityManager->persist($user);
    		$entityManager->flush();
    		//send validation mail
            $mailService->sendMail('Please activate your account - '.$this->getParameter('app.site_name'), $this->renderView('email/signup.html.twig',['email' => $userEmail,
            'token' => $emailToken,'name' => strstr($userEmail,'@',true)]), $userEmail, $this->getParameter('app.site_name'));
    		//authenticate
    		//redirect to dashboard
    		return $this->redirectToRoute('my_account');
        } 

        if ($form->isSubmitted() && !$form->isValid()) {
        	$formError = 1;
        }

        return $this->render('signup/index.html.twig', [
        	'form' => $form->createView(),
        	'formError' => $formError 
        ]);
    }


    public function beforelogout(Request $request)
    {
        $messageView = '';
        foreach ($this->container->get('session')->getFlashBag()->get('success', []) as $message) {
            $messageView .= $message;
        }
        if ($messageView != '') {
            $this->addFlash('success', $messageView);
        } else {
            $this->addFlash('success', 'You have successfully logged out');
        }       
        return $this->redirectToRoute('app_login');
    }

}