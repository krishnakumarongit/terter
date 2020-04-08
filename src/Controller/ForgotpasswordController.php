<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\Spam;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Email;
use App\Service\MailService;


class ForgotpasswordController extends AbstractController
{
    public function index(Request $request, MailService $mailService)
    {
    	$errorMessage = '';
    	$successMessage = '';
    	$defaultData = ['message' => ''];
        $form = $this->createFormBuilder($defaultData)
        ->add('email', TextType::class,  [
            'constraints' => [
                new NotBlank(),
                new Email(),
            ],
        ])
        ->add('submit', SubmitType::class)
        ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
        	 $data = $form->getData();
        	 $email =  $data['email'] ?? '';
        	 if ($email != "") {
        	 	//get email from database to check it exists
        	 	$entityManager = $this->getDoctrine()->getManager();
	    		$user = $entityManager->getRepository(User::class)->findOneBy([
    		   		'email' => $email
				]);

	    		if ($user) {
	    			$sendMail = 0;
	    			//check how many times requested
	    			$spamCheck = $entityManager->getRepository(Spam::class)->findBy([
    		   		    'email' => $email,
    		   		    'type' => 'FORGOT_PASSWORD',
    		   		    'date' => \DateTime::createFromFormat('Y-m-d',date('Y-m-d'))
				    ]);
				    if ($spamCheck) {
				    	//check last update date
				    	if (count($spamCheck) >= 2) {
				    		$errorMessage = 'Your password request limit has exceeded for today. Please check your spam junk folder of your email.';
				        } else {
				        	$sendMail = 1;
				    	    $emailToken = $user->getToken();
                            if ($emailToken == '') {
                                $emailToken = md5($user->getId().'-'.$user->getEmail().'-'.time());
                            }
				        }
				    } else {
				    	//send reset email
				    	$sendMail = 1;
				    	$emailToken = md5($user->getId().'-'.$user->getEmail().'-'.time());
				    }
				    if ($sendMail ==1) {
				    	$spamCheck = new Spam();
				    	$spamCheck->setEmail($email);
				    	$spamCheck->setIpAddress($_SERVER['REMOTE_ADDR']);
				    	$spamCheck->setDate(\DateTime::createFromFormat('Y-m-d',date('Y-m-d')));
				    	$spamCheck->setType('FORGOT_PASSWORD');
				    	$entityManager->persist($spamCheck);
    					$entityManager->flush();
    					$user->setToken($emailToken);
    					$entityManager->persist($user);
    					$entityManager->flush();
    					//send email template
    					$mailService->sendMail('Reset password of your account - '.$this->getParameter('app.site_name'), $this->renderView('email/forgot.html.twig',['email' => $email,
                            'token' => $emailToken,'name' => strstr($email,'@',true)]), $email, $this->getParameter('app.site_name'));
    					return $this->redirectToRoute('reset_password_success');
				    }

	    		} else {
	    			$errorMessage = $email.' is not associated with a '.$this->getParameter('app.site_name').' account';
	    		}
        	 }
       
        }

    	return $this->render('signup/forgot.html.twig', [
        	'form' => $form->createView() ,
        	'errorMessage' => $errorMessage,
        	'successMessage' => $successMessage
        ]);
    }
}