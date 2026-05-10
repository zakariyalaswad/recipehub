<?php

namespace App\Controller;

use App\Entity\TagRecette;
use App\Form\TagRecetteType;
use App\Repository\TagRecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/tag/recette')]
final class TagRecetteController extends AbstractController
{
    #[Route(name: 'app_tag_recette_index', methods: ['GET'])]
    public function index(TagRecetteRepository $tagRecetteRepository): Response
    {
        return $this->render('tag_recette/index.html.twig', [
            'tag_recettes' => $tagRecetteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_tag_recette_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $tagRecette = new TagRecette();
        $form = $this->createForm(TagRecetteType::class, $tagRecette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($tagRecette);
            $entityManager->flush();

            return $this->redirectToRoute('app_tag_recette_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tag_recette/new.html.twig', [
            'tag_recette' => $tagRecette,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tag_recette_show', methods: ['GET'])]
    public function show(TagRecette $tagRecette): Response
    {
        return $this->render('tag_recette/show.html.twig', [
            'tag_recette' => $tagRecette,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_tag_recette_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, TagRecette $tagRecette, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(TagRecetteType::class, $tagRecette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_tag_recette_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('tag_recette/edit.html.twig', [
            'tag_recette' => $tagRecette,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_tag_recette_delete', methods: ['POST'])]
    public function delete(Request $request, TagRecette $tagRecette, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$tagRecette->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($tagRecette);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_tag_recette_index', [], Response::HTTP_SEE_OTHER);
    }
}
