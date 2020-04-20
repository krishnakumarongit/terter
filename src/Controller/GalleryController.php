<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Entity\User;
use App\Entity\Gallery;
use App\Entity\Comments;
use App\Entity\Likes;


class GalleryController extends AbstractController
{
    public function index(Request $request, $page = 1, $type='all')
    {
        $limit = 10;
        $condtion['status'] = 'Approved';
        if ($type !="" && $type != 'all') {
            $condtion['type'] = ucfirst($type);
        }

        $entityManager = $this->getDoctrine()->getManager();
        $galleryCount = $entityManager->getRepository(Gallery::class)->findBy($condtion,
             array('id' => 'DESC')
        );

        $gallery = $entityManager->getRepository(Gallery::class)->findBy($condtion,
             array('id' => 'DESC'),
             $limit,
             $page - 1
        );

        $totalCount = count($galleryCount);
        $linkCount = ceil($totalCount/ $limit);
        return $this->render('gallery/index.html.twig', [
            'list' => $gallery,
            'linkCount' => $linkCount,
            'page' => $page,
            'type' => ucfirst($type),
            'total' => $totalCount - 1
        ]);

    }

    public function details(Request $request, $id = '') 
    {

        $defaultData = ['message' => 'Type your message here'];
        $entityManager = $this->getDoctrine()->getManager();
        $gallery = $entityManager->getRepository(Gallery::class)->findOneBy(
             array('id' => $id)
        );



        $conn = $this->getDoctrine()->getManager()->getConnection();
        $sql = 'SELECT count(id) as like_count from likes where type_id='.$id.' and type ="Gallery"';
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $likes = $stmt->fetch();
        $userLikes = isset($likes['like_count']) ? $likes['like_count'] : 0;


        $youLike = 0;
        if ($this->getUser()) {

            $sql = 'SELECT count(id) as like_count from likes where type_id='.$id.' and type ="Gallery"
            and user_id ='.$this->getUser()->getId();
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $likes = $stmt->fetch();
            $youLike = isset($likes['like_count']) ? $likes['like_count'] : 0;
        }

        if (!$gallery || $id == '') {
            return $this->redirectToRoute('public_gallery');
        }

        $form = $this->createFormBuilder($defaultData)
        ->add('comment', TextareaType::class)
        ->add('submit', SubmitType::class)
        ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->getUser()) {
                //check if comment matches
                 $data = $form->getData();
                 $comment = trim($data['comment']);
                 if ($comment == "" || strlen($comment) < 10 ) {
                    $this->addFlash('error', 'Comments should be at least 10 characters in length');
                    return $this->redirectToRoute('gallery_view',['id' => $id]);
                 } else {
                    //insert comment
                    $commenEntity = new Comments();
                    $commenEntity->setStatus('Inactive');  
                    $commenEntity->setType('Gallery');  
                    $commenEntity->setTypeId($id);  
                    $commenEntity->setUserId($this->getUser()->getId());
                    $commenEntity->setComment($comment);
                    $commenEntity->setCreatedOn(\DateTime::createFromFormat('Y-m-d H:i:s',date('Y-m-d H:i:s')));
                    $entityManager = $this->getDoctrine()->getManager();
                    $entityManager->persist($commenEntity);
                    $entityManager->flush();
                    $this->addFlash('success', 'Your comment is awaiting moderation. It will be visible after it has been approved.');
                    return $this->redirectToRoute('gallery_view',['id' => $id]);
                 }
            } else {
                $this->addFlash('error', 'You must be logged in to post a comment');
                return $this->redirectToRoute('app_login');
            } 
        }

     
         $conn = $this->getDoctrine()->getManager()
            ->getConnection();
         $sql = 'SELECT comments.comment, comments.created_on,user.first_name,user.last_name,
         user.profile_photo, user.email  FROM comments left join user on user.id = comments.user_id 
         where comments.type="Gallery" and comments.type_id = '.$id.' and comments.status="Active"
         order by comments.id desc';
         $stmt = $conn->prepare($sql);
         $stmt->execute();
         $commentsUser = $stmt->fetchAll();

        return $this->render('gallery/details.html.twig', [
            'post' => $gallery,
            'form' => $form->createView(),
            'commentCount' => count($commentsUser),
            'likeCount' => $userLikes - $youLike,
            'youLike' => $youLike,
            'comments' => $commentsUser
        ]);

    }


    public function like(Request $request, $id)
    {
        if ($this->getUser()) {
            //check if user liked it else unlike it
            $entityManager = $this->getDoctrine()->getManager();
            
            $gallery = $entityManager->getRepository(Gallery::class)->findOneBy(
                 array('id' => $id)
            );

            if (!$gallery) {
                return new Response(json_encode(['status' => 0, 'message' => 'An unexpected error occurred. Please try again later.']));
            } 


            $userLikes = $entityManager->getRepository(Likes::class)->findOneBy(
                array(
                    'type_id' => $id,
                    'user_id' => $this->getUser()->getId(),
                    'type' => 'Gallery'
                )
            );

            $conn = $this->getDoctrine()->getManager()->getConnection();
            $sql = 'SELECT count(id) as like_count from likes where type_id='.$id.' and type ="Gallery"';
            $stmt = $conn->prepare($sql);
            $stmt->execute();
            $likes = $stmt->fetch();
            $likesCount = isset($likes['like_count']) ? $likes['like_count'] : 0;

            if (!$userLikes) {
                //insert like here
                $lik = new Likes;
                $lik->setUserId($this->getUser()->getId());
                $lik->setCreatedDate(\DateTime::createFromFormat('Y-m-d',date('Y-m-d')));
                $lik->setType('Gallery');
                $lik->setTypeId($id);
                $entityManager->persist($lik);
                $entityManager->flush();

                if ($likesCount == 0) {
                    return new Response(json_encode(['status' => 1, 'message' => 'You liked this']));
                } else {
                    return new Response(json_encode(['status' => 1, 'message' => 'You and '.$likesCount.' others liked this']));
                }
                //enter user like
            } else {
                //delete user like
                $entityManager->remove($userLikes);
                $entityManager->flush();
                if ($likesCount == 0) {
                    return new Response(json_encode(['status' => 1, 'message' => 0]));
                } else {
                     return new Response(json_encode(['status' => 1, 'message' => $likesCount - 1]));
                }
            }

        } else {
            return new Response(json_encode(['status' => 0, 'message' => 'You must be logged in to like']));
        }
    }


}