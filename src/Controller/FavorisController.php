<?php

namespace App\Controller;

use App\Repository\RecetteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

final class FavorisController extends AbstractController
{
    #[Route('/favoris/ajouter/{id}', name: 'app_favoris_add')]
    public function add(
        int $id,
        RequestStack $requestStack
    ): Response {

        $session = $requestStack->getSession();

        $favoris = $session->get('favoris', []);

        if (!in_array($id, $favoris)) {
            $favoris[] = $id;
        }

        $session->set('favoris', $favoris);

        $this->addFlash('success', 'Recette ajoutée aux favoris');

        return $this->redirectToRoute('app_recette_show', [
            'id' => $id
        ]);
    }

    #[Route('/favoris/supprimer/{id}', name: 'app_favoris_remove')]
    public function remove(
        int $id,
        RequestStack $requestStack
    ): Response {

        $session = $requestStack->getSession();

        $favoris = $session->get('favoris', []);

        $favoris = array_filter($favoris, function ($favId) use ($id) {
            return $favId != $id;
        });

        $session->set('favoris', $favoris);

        return $this->redirectToRoute('app_mes_favoris');
    }

    #[Route('/mes-favoris', name: 'app_mes_favoris')]
    public function index(
        RequestStack $requestStack,
        RecetteRepository $repo
    ): Response {

        $session = $requestStack->getSession();

        $favoris = $session->get('favoris', []);

        $recettes = $repo->findBy([
            'id' => $favoris
        ]);

        return $this->render('favoris/index.html.twig', [
            'recettes' => $recettes
        ]);
    }
}