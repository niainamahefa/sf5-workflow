<?php

namespace App\Controller;

use App\Entity\Post;
use App\Entity\PostRequest;
use App\Form\PostType;
use App\Repository\PostRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Workflow\WorkflowInterface;

class PostController extends AbstractController
{
    private $postRequestWorkflow;

    public function  __construct(WorkflowInterface $postRequestWorkflow)
    {
        $this->postRequestWorkflow = $postRequestWorkflow;
    }

    /**
     * @Route("/new", name="app_new")
     * @param Request $request
     * @param EntityManagerInterface $em
     * @return Response
     */
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $post = new Post();

        $post->setMember($this->getUser());

        $form = $this->createForm(PostType::class, $post);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $post = $form->getData();

            try {
                $this->postRequestWorkflow->apply($post, 'to_pending');
            } catch (\LogicException $exception) {
                //
            }

            $em->persist($post);
            $em->flush();

            $this->addFlash('success', 'Request saved !');

            return $this->redirectToRoute('app_new');
        }

        return $this->render('post/index.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @Route("/superuser", name="app_superuser")
     * @param PostRepository $postRepository
     * @return Response
     */
    public function superUser(PostRepository $postRepository): Response
    {
        return $this->render('post/superuser.html.twig', [
            'posts' => $postRepository->findAll()
        ]);
    }

    /**
     * @Route("/validation/{id}/{to}", name="app_validation")
     * @param Post $post
     * @param String $to
     * @param EntityManagerInterface $entityManager
     * @return Response
     */
    public function change(Post $post, String $to, EntityManagerInterface $entityManager): Response
    {
        try {
            $this->postRequestWorkflow->apply($post, $to);
        } catch (\LogicException $exception) {
            //
        }

        $entityManager->persist($post);
        $entityManager->flush();

        $this->addFlash('success', 'Action saved !');

        return $this->redirectToRoute('app_superuser');

    }
}
