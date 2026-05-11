<?php

namespace App\Controller;

use App\Repository\RecetteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_CUISINIER')]
final class ChefController extends AbstractController
{
    #[Route('/chef/dashboard', name: 'app_chef_dashboard', methods: ['GET'])]
    public function dashboard(
        Request $request,
        RecetteRepository $recetteRepository,
    ): Response {
        $user = $this->getUser();
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 12;

        $queryBuilder = $recetteRepository->createQueryBuilder('r')
            ->leftJoin('r.categorie', 'c')->addSelect('c')
            ->leftJoin('r.tags', 't')->addSelect('t')
            ->where('r.auteur = :auteur')
            ->setParameter('auteur', $user)
            ->orderBy('r.dateCreation', 'DESC')
            ->addOrderBy('r.id', 'DESC');

        $countQueryBuilder = clone $queryBuilder;
        $totalRecipes = (int) $countQueryBuilder
            ->select('COUNT(DISTINCT r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalRecipes / $limit));
        $page = min($page, $totalPages);

        $recipes = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('chef/dashboard.html.twig', [
            'recipes' => $recipes,
            'totalRecipes' => $totalRecipes,
            'currentPage' => $page,
            'totalPages' => $totalPages,
        ]);
    }

    #[Route('/chef/profile', name: 'app_chef_profile', methods: ['GET'])]
    public function profile(RecetteRepository $recetteRepository): Response
    {
        $user = $this->getUser();
        $recipeCount = $recetteRepository->count(['auteur' => $user]);

        return $this->render('chef/profile.html.twig', [
            'recipeCount' => $recipeCount,
        ]);
    }
}
