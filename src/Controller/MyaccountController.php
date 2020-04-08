<?php

namespace App\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\Email;
use App\Entity\User;
use App\Entity\Spam;
use App\Service\MailService;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


class MyaccountController extends AbstractController
{
    /**
     * @Route("/my-account", name="my_account")
     */
    public function index(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }
        return $this->render('myaccount/index.html.twig', []);
    }


    public function login(Request $request, UserPasswordEncoderInterface $passwordEncoder, MailService $mailService): Response
    {

        $errorMessage = '';
        $sendMail = 0;
        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getUser();
              
        $defaultData1 = ['message' => ''];
        $form2 = $this->createFormBuilder($defaultData1)
        ->add('email', TextType::class,  [
            'constraints' => [
                new NotBlank(),
                new Email(),
            ],
        ])
        ->add('submit', SubmitType::class)
        ->getForm();

         $form2->handleRequest($request);
            
        
        if ($form2->isSubmitted() && $form2->isValid()) {
            $data2 = $form2->getData();
            $email = $data2['email'];
            if ($email != $user->getEmail()) {
                //check if this email exists in database
                $userEmail = $entityManager->getRepository(User::class)->findOneBy([
                    'email' => $email
                ]);
                if ($userEmail) {
                    //this email already exists
                    $this->addFlash('login_error', $email.' already in use.');
                    return $this->redirectToRoute('login_info');
                } else {
                    //check for user myaccount email reset max - 2
                   
                    //check how many times requested
                    $spamCheck = $entityManager->getRepository(Spam::class)->findBy([
                        'email' => $user->getId(),
                        'type' => 'CHANGE_EMAIL',
                        'date' => \DateTime::createFromFormat('Y-m-d',date('Y-m-d'))
                    ]);

                     if ($spamCheck) {
                        //check last update date
                        if (count($spamCheck) >= 2) {
                            $errorMessage = 'Your change login email limit has exceeded for today. Please check your spam junk folder of your email.';
                        } else {
                            $sendMail = 1;
                            $emailToken = $user->getEmailToken();
                            if ($emailToken == '') {
                                 $emailToken = md5($user->getId().'-'.$email.'-'.time());
                            }
                        }
                    } else {
                        //send reset email
                        $sendMail = 1;
                        $emailToken = md5($user->getId().'-'.$email.'-'.time());
                    }

                     if ($sendMail ==1) {
                        $spamCheck = new Spam();
                        $spamCheck->setEmail($user->getId());
                        $spamCheck->setIpAddress($_SERVER['REMOTE_ADDR']);
                        $spamCheck->setDate(\DateTime::createFromFormat('Y-m-d',date('Y-m-d')));
                        $spamCheck->setType('CHANGE_EMAIL');
                        $entityManager->persist($spamCheck);
                        $entityManager->flush();
                        $user->setEmail($email);
                        //used for email verification
                        $user->setEmailToken($emailToken);
                        $user->setEmailValid(0);
                        $entityManager->persist($user);
                        $entityManager->flush();
                        //send validation mail
                        $mailService->sendMail('Please activate your account - '.$this->getParameter('app.site_name'), $this->renderView('email/signup.html.twig',['email' => $email,
                            'token' => $emailToken,'name' => strstr($email,'@',true)]), $email, $this->getParameter('app.site_name'));
                        $this->addFlash('success', 'Login email changed successfully. Login with your new email to continue.');
                        return $this->redirectToRoute('before_logout');
                    }
                }
            }
        }

        return $this->render('myaccount/login.html.twig', [
            'form2' => $form2->createView(),
            'errorMessage' => $errorMessage
        ]);
    }

public function password(Request $request, UserPasswordEncoderInterface $passwordEncoder): Response
    {

        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getUser();
              
        $defaultData = ['message' => ''];
            $form1 = $this->createFormBuilder($defaultData)
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

        $form1->handleRequest($request);
        

        if ($form1->isSubmitted() && $form1->isValid()) {
             $data = $form1->getData();
             $password = $data['password'];
             $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $password
                )
            );
            $entityManager->persist($user);
            $entityManager->flush();
            $this->addFlash('password_success', 'Password changed successfully');
            return $this->redirectToRoute('password_info');
        }

        return $this->render('myaccount/password.html.twig', [
            'form1' => $form1->createView(),
        ]);
    }



    public function verify(Request $request, AuthenticationUtils $authenticationUtils, MailService $mailService): Response
    {
        $sendMail = 0;
        $errorMessage = '';
        $successMessage = '';

        $entityManager = $this->getDoctrine()->getManager();
        $user = $this->getUser();
              
        $defaultData = ['message' => ''];
            $form = $this->createFormBuilder($defaultData)
           ->add('check', HiddenType::class, [
                'data' => 'abcdef',]
            )
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

                    $email = $user->getEmail();
                    //check how many times requested
                    $spamCheck = $entityManager->getRepository(Spam::class)->findBy([
                        'email' => $user->getId(),
                        'type' => 'ACTIVATION_EMAIL',
                        'date' => \DateTime::createFromFormat('Y-m-d',date('Y-m-d'))
                    ]);

                     if ($spamCheck) {
                        //check last update date
                        if (count($spamCheck) >= 2) {
                            $errorMessage = 'Your activation email request limit has exceeded for today. Please check your spam junk folder of your email.';
                        } else {
                            $sendMail = 1;
                            $emailToken = $user->getEmailToken();
                            if ($emailToken == '') {
                                 $emailToken = md5($user->getId().'-'.$email.'-'.time());
                            }
                        }
                    } else {
                        //send reset email
                        $sendMail = 1;
                        $emailToken = md5($user->getId().'-'.$email.'-'.time());
                    }

                    if ($sendMail ==1) {
                        $spamCheck = new Spam();
                        $spamCheck->setEmail($user->getId());
                        $spamCheck->setIpAddress($_SERVER['REMOTE_ADDR']);
                        $spamCheck->setDate(\DateTime::createFromFormat('Y-m-d',date('Y-m-d')));
                        $spamCheck->setType('ACTIVATION_EMAIL');
                        $entityManager->persist($spamCheck);
                        $entityManager->flush();
                        //used for email verification
                        $user->setEmailToken($emailToken);
                        $user->setEmailValid(0);
                        $entityManager->persist($user);
                        $entityManager->flush();
                        //send validation mail
                        $mailService->sendMail('Please activate your account - '.$this->getParameter('app.site_name'), $this->renderView('email/signup.html.twig',['email' => $email,
                            'token' => $emailToken,'name' => strstr($email,'@',true)]), $email, $this->getParameter('app.site_name'));
                        $successMessage = 1;
                        
                    }

        }

        return $this->render('myaccount/verify.html.twig', [
            'form' => $form->createView(),
            'errorMessage' => $errorMessage,
            'successMessage' => $successMessage
        ]);

    }

    
}
