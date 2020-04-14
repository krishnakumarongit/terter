<?php
namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Entity\Gallery;


class GalleryController extends AbstractController
{
    public function index(Request $request, $page = 1, $type='all')
    {
        $limit = 1;
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

        $entityManager = $this->getDoctrine()->getManager();
        $gallery = $entityManager->getRepository(Gallery::class)->findOneBy(
             array('id' => $id)
        );

        if (!$gallery || $id == '') {
             return $this->redirectToRoute('public_gallery');
        }

        return $this->render('gallery/details.html.twig', [
            'post' => $gallery
        ]);

    }
}