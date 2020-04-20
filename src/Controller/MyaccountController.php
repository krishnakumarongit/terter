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
use App\Entity\Gallery;
use App\Form\GalleryForm;
use App\Service\MailService;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;


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

    public function galleryList(Request $request): Response
    {
         $entityManager = $this->getDoctrine()->getManager();
         $list = $entityManager->getRepository(Gallery::class)->findBy([
                        'user_id' => $this->getUser()->getId(),
                  ]);
         if (count($list) > 0) {
            return $this->render('myaccount/gallery_list.html.twig', [
            'list' => $list           
            ]);
         } else {
            return $this->redirectToRoute('myaccount_gallery');
         }
    }

    public function galleryDelete(Request $request, String $token=""): Response
    {
        $entityManager = $this->getDoctrine()->getManager();
        $gallery = $entityManager->getRepository(Gallery::class)->findOneBy([
            'id' => $token
        ]);
        if (!$gallery){
            return $this->redirectToRoute('myaccount_gallery_list');            
        }
        if ($gallery->getUserId() != $this->getUser()->getId()) {
            return $this->redirectToRoute('myaccount_gallery_list');      
        }
        $oldImage = $gallery->getImage();
        @unlink($this->getParameter('app.gallery_folder')."/".$oldImage);
        $entityManager->remove($gallery);
        $entityManager->flush();
        $this->addFlash('success', 'Your Pets image deleted successfully');
        return $this->redirectToRoute('myaccount_gallery_list');

    }



    public function galleryEdit(Request $request, String $token="", SluggerInterface $slugger): Response
    {

        $image_error = '';
        $entityManager = $this->getDoctrine()->getManager();
        $gallery = $entityManager->getRepository(Gallery::class)->findOneBy([
            'id' => $token
        ]);

        $oldImage = $gallery->getImage();

        if (!$gallery){
            return $this->redirectToRoute('myaccount_gallery_list');            
        }

        if ($gallery->getUserId() != $this->getUser()->getId()) {
            return $this->redirectToRoute('myaccount_gallery_list');      
        }

        if ($gallery->getStatus() != 'Inactive') {
            return $this->redirectToRoute('myaccount_gallery_list');      
        }

        $form = $this->createForm(GalleryForm::class, $gallery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

               

            $image = $form->get('image')->getData();

         

            if ($image) {
               


                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.time().'.'.$image->guessExtension();
                try {
                    $image->move(
                        $this->getParameter('app.gallery_folder'),
                        $newFilename
                    );

                    list($originalWidth, $originalHeight) = getimagesize($this->getParameter('app.gallery_folder')."/".$newFilename);
                    $ratio = $originalWidth / $originalHeight;
                    $targetWidth = 700;
                    $targetHeight = 500;
                    if ($originalWidth <= $targetWidth && $originalHeight <= $targetHeight ) { 
                        $targetWidth = $originalWidth;
                        $targetHeight = $originalHeight;

                    } else {
                        $targetWidth = $targetHeight * $ratio;
                        $targetHeight = $targetWidth / $ratio;
                        if ($originalWidth >= $originalHeight) {
                            if($targetHeight > $targetWidth) {
                              $temp = $targetHeight;
                              $targetHeight = $targetWidth;
                              $targetWidth =  $temp;
                            }
                        } else {
                            if($targetHeight < $targetWidth) {
                              $temp = $targetWidth;
                              $targetWidth = $targetHeight;
                              $targetHeight =  $temp;
                            }
                        }
                    }

                    $result = $this->resize_crop_image($targetWidth, $targetHeight,$this->getParameter('app.gallery_folder')."/".$newFilename, $this->getParameter('app.gallery_folder')."/thumb_".$newFilename);
                    if ($result ==1) {

                        @unlink($this->getParameter('app.gallery_folder')."/".$newFilename);

                        $gallery->setName($form->get('name')->getData());
                        $gallery->setType($form->get('type')->getData());
                        $gallery->setImage("thumb_".$newFilename);
                        $gallery->setStatus("Inactive");
                         $gallery->setCreatedOn(\DateTime::createFromFormat('Y-m-d H:i:s',date('Y-m-d H:i:s')));
                       
                        $entityManager->persist($gallery);
                        $entityManager->flush();
                        $this->addFlash('success', 'Your Pets image updated successfully. After approval it will get listed in the gallery.');
                        return $this->redirectToRoute('myaccount_gallery_list');

                    } else {
                        $image_error = 'Unable to upload file. Please try again with a different image';
                    }
                } catch (FileException $e) {
                    $image_error = 'Unable to upload file. Please try again with a different image';
                }

            } else {
                $gallery->setName($form->get('name')->getData());
                $gallery->setType($form->get('type')->getData());
                $gallery->setImage($oldImage);
                $gallery->setStatus("Inactive");
                $gallery->setCreatedOn(\DateTime::createFromFormat('Y-m-d H:i:s',date('Y-m-d H:i:s')));
                $entityManager->persist($gallery);
                $entityManager->flush();
                $this->addFlash('success', 'Your Pets image update successfully. After approval it will get listed in the gallery.');
                return $this->redirectToRoute('myaccount_gallery_list');
            }
        }

        return $this->render('myaccount/gallery.html.twig', [
            'form' => $form->createView(),
            'image_error' => $image_error,
            'image' => $oldImage
        ]);
    }


    public function gallery(Request $request, SluggerInterface $slugger): Response
    {
        $image_error = '';
        $gallery  = new Gallery();
        $form = $this->createForm(GalleryForm::class, $gallery);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            //upload image and resize
             $image = $form->get('image')->getData();
             if ($image) {
                $originalFilename = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.time().'.'.$image->guessExtension();
                try {
                    $image->move(
                        $this->getParameter('app.gallery_folder'),
                        $newFilename
                    );

                    list($originalWidth, $originalHeight) = getimagesize($this->getParameter('app.gallery_folder')."/".$newFilename);
                    $ratio = $originalWidth / $originalHeight;
                    $targetWidth = 700;
                    $targetHeight = 500;
                    if ($originalWidth <= $targetWidth && $originalHeight <= $targetHeight ) { 
                        $targetWidth = $originalWidth;
                        $targetHeight = $originalHeight;

                    } else {
                        $targetWidth = $targetHeight * $ratio;
                        $targetHeight = $targetWidth / $ratio;
                        if ($originalWidth >= $originalHeight) {
                            if($targetHeight > $targetWidth) {
                              $temp = $targetHeight;
                              $targetHeight = $targetWidth;
                              $targetWidth =  $temp;
                            }
                        } else {
                            if($targetHeight < $targetWidth) {
                              $temp = $targetWidth;
                              $targetWidth = $targetHeight;
                              $targetHeight =  $temp;
                            }
                        }
                    }

                    $result = $this->resize_crop_image($targetWidth, $targetHeight,$this->getParameter('app.gallery_folder')."/".$newFilename, $this->getParameter('app.gallery_folder')."/thumb_".$newFilename);
                    if ($result ==1) {

                        @unlink($this->getParameter('app.gallery_folder')."/".$newFilename);

                        $gallery->setName($form->get('name')->getData());
                        $gallery->setType($form->get('type')->getData());
                        $gallery->setImage("thumb_".$newFilename);
                        $gallery->setStatus("Inactive");
                        $gallery->setTitle("Not required");
                        $gallery->setUserId($this->getUser()->getId());
                        $gallery->setCreatedOn(\DateTime::createFromFormat('Y-m-d H:i:s',date('Y-m-d H:i:s')));
                        $entityManager = $this->getDoctrine()->getManager();
                        $entityManager->persist($gallery);
                        $entityManager->flush();
                        $this->addFlash('success', 'Your Pets image added successfully. After approval it will get listed in the gallery.');
                        return $this->redirectToRoute('myaccount_gallery_list');

                    } else {
                        $image_error = 'Unable to upload file. Please try again with a different image';
                    }
                } catch (FileException $e) {
                    $image_error = 'Unable to upload file. Please try again with a different image';
                }
             } else {
                $image_error = 'Pets image is required.';
             }
        }
        
        return $this->render('myaccount/gallery.html.twig', [
            'form' => $form->createView(),
            'image_error' => $image_error
        ]);

    }


    private  function resize_crop_image($max_width, $max_height, $source_file, $dst_dir, $quality = 80)
    {
        $imgsize = getimagesize($source_file);
        $width = $imgsize[0];
        $height = $imgsize[1];
        $mime = $imgsize['mime'];
     
        switch($mime){
            case 'image/gif':
                $image_create = "imagecreatefromgif";
                $image = "imagegif";
                break;
     
            case 'image/png':
                $image_create = "imagecreatefrompng";
                $image = "imagepng";
                $quality = 7;
                break;
     
            case 'image/jpeg':
                $image_create = "imagecreatefromjpeg";
                $image = "imagejpeg";
                $quality = 80;
                break;
     
            default:
                return false;
                break;
        }
         
        $dst_img = imagecreatetruecolor($max_width, $max_height);
        $src_img = $image_create($source_file);
         
        $width_new = $height * $max_width / $max_height;
        $height_new = $width * $max_height / $max_width;
        //if the new width is greater than the actual width of the image, then the height is too large and the rest cut off, or vice versa
        if($width_new > $width){
            //cut point by height
            $h_point = (($height - $height_new) / 2);
            //copy image
            imagecopyresampled($dst_img, $src_img, 0, 0, 0, $h_point, $max_width, $max_height, $width, $height_new);
        }else{
            //cut point by width
            $w_point = (($width - $width_new) / 2);
            imagecopyresampled($dst_img, $src_img, 0, 0, $w_point, 0, $max_width, $max_height, $width_new, $height);
        }
        $image($dst_img, $dst_dir, $quality);
        if($dst_img)imagedestroy($dst_img);
        if($src_img)imagedestroy($src_img);
        return 1;
    }

    
}
