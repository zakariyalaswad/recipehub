<?php

namespace App\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\Recette;
use App\Form\RecetteType;
use App\Repository\RecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\FileUploader;

#[Route('/recettes')]
final class RecetteController extends AbstractController
{
    public function __construct(private FileUploader $fileUploader)
    {
    }

    #[Route(name: 'app_recette_index', methods: ['GET'])]
    public function index(RecetteRepository $recetteRepository): Response
    {
        return $this->render('recette/index.html.twig', [
            'recettes' => $recetteRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_recette_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CUISINIER')]  
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $recette = new Recette();
        $form = $this->createForm(RecetteType::class, $recette);
        $form->handleRequest($request);
        $imageFile = $form->get('image')->getData();    

        if ($imageFile) {
            $fileName = $this->fileUploader->upload($imageFile);
            $recette->setImageName($fileName);
        }
        $recette->setAuteur($this->getUser());
        $recette->setDateCreation(new \DateTime());

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($recette);
            $entityManager->flush();

            return $this->redirectToRoute('app_recette_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recette/new.html.twig', [
            'recette' => $recette,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_recette_show', methods: ['GET'])]
    public function show(Recette $recette): Response
    {
        return $this->render('recette/show.html.twig', [
            'recette' => $recette,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_recette_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_CUISINIER')]
    public function edit(Request $request, Recette $recette, EntityManagerInterface $entityManager): Response
    {
        if (
            $recette->getAuteur() !== $this->getUser()
            && !$this->isGranted('ROLE_ADMIN')
        ) {
            throw $this->createAccessDeniedException();
        }
        $form = $this->createForm(RecetteType::class, $recette);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {

                // supprimer ancienne image
                if ($recette->getImageName()) {
                    $fileUploader->remove($recette->getImageName());
                }

                // upload nouvelle image
                $fileName = $fileUploader->upload($imageFile);

                // save nouveau nom
                $recette->setImageName($fileName);
            }
            $entityManager->flush();
            $this->addFlash('success', 'Recette modifiée avec succès');

            return $this->redirectToRoute('app_recette_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recette/edit.html.twig', [
            'recette' => $recette,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_recette_delete', methods: ['POST'])]
    #[IsGranted('ROLE_CUISINIER')]
    public function delete(Request $request, Recette $recette, EntityManagerInterface $entityManager): Response
    {
        if (
            $recette->getAuteur() !== $this->getUser()
            && !$this->isGranted('ROLE_ADMIN')
        ) {
            throw $this->createAccessDeniedException();
        }
        if ($this->isCsrfTokenValid('delete'.$recette->getId(), $request->getPayload()->getString('_token'))) {
            if ($recette->getImageName()) {
                $fileUploader->remove($recette->getImageName());
            }
            $entityManager->remove($recette);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_recette_index', [], Response::HTTP_SEE_OTHER);
    }
}
