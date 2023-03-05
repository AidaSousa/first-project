<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Form\PostType;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

class PostController extends AbstractController
{

    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/', name: 'app_post')]
    public function index(Request $request, SluggerInterface $slugger): Response
    {
        $post = new Post();
        $posts = $this->em->getRepository(Post::class)->findAllPosts();


        $form = $this->createForm(PostType::class, $post);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            $url = str_replace(" ", "-", $form->get('title')->getData());

            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'-'.$file->guessExtension();
                try {
                    $file->move(
                        $this->getParameter('files_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw new \Exception('Ups there is a problem with your file');
                }

                $post->setFile($newFilename);
            }


            $post->setUrl($url);
            $user = $this->em->getRepository(User::class)->find(1);
            $post->setUser($user);
            $this->em->persist($post);
            $this->em->flush();
            return $this->redirectToRoute('app_post');
        }

        return $this->render('post/index.html.twig', [
            'form' => $form->createView(),
            'posts' => $posts
        ]);
    }

    // #[Route('/', name: 'app_post')]
    // public function index($id): Response
    // {
    //     $post = $this->em->getRepository(Post::class)->find($id);
    //     $custom_post = $this->em->getRepository(Post::class)->findPost($id);
    //     return $this->render('post/index.html.twig', [
    //         'post' => $post,
    //         'custom_post' => $custom_post
    //         ]
    //     );
    // }
    
    // #[Route('/insert/post', name: 'insert_post')]
    // public function insert() {
    //     $post = new Post(title:'mi segundo post insertado', type:'opinion', description:'holamundo', file:'hooho', url:'klafalf');
    //     $user = $this->em->getRepository(User::class)->find(id:'1');
    //     $post->setUser($user);
    //     $this->em->persist($post);
    //     $this->em->flush();
    //     return new JsonResponse(['success'=>true]);
    // }

    // #[Route('/update/post', name: 'update_post')]
    // public function update() {
    //     $post = $this->em->getRepository(Post::class)->find(id:'3');
    //     $post->setTitle('mi nuevo titulo');
    //     $this->em->flush();
    //     return new JsonResponse(['success'=>true]);
    // }

    // #[Route('/delete/post', name: 'delete_post')]
    // public function delete() {
    //     $post = $this->em->getRepository(Post::class)->find(id:'5');
    //     $this->em->remove($post);
    //     $this->em->flush();
    //     return new JsonResponse(['success'=>true]);
    // }
}

