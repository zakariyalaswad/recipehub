<?php

namespace App\Controller;

use App\Repository\RecetteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
final class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET'])]
    public function index(RecetteRepository $recetteRepository, RequestStack $requestStack): Response
    {
        if ($this->isGranted('ROLE_CUISINIER')) {
            return $this->redirectToRoute('app_chef_profile');
        }

        $user = $this->getUser();
        $session = $requestStack->getSession();
        $favoriteIds = $session->get('favoris', []);

        return $this->render('user/profile.html.twig', [
            'recipeCount' => $recetteRepository->count(['auteur' => $user]),
            'favoriteCount' => is_array($favoriteIds) ? count($favoriteIds) : 0,
        ]);
    }
}
