<?php

namespace App\Controller;

use App\Repository\RecetteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class FavorisController extends AbstractController
{
    #[Route('/favoris/ajouter/{id}', name: 'app_favoris_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function add(
        int $id,
        RequestStack $requestStack,
        \Symfony\Component\HttpFoundation\Request $request
    ): Response {

        $session = $requestStack->getSession();

        if (!$this->isCsrfTokenValid('add_favorite_'.$id, $request->request->get('_token', ''))) {
            return $this->redirectToRoute('app_recette_show', ['id' => $id]);
        }

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

    #[Route('/favoris/supprimer/{id}', name: 'app_favoris_remove', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function remove(
        int $id,
        RequestStack $requestStack,
        \Symfony\Component\HttpFoundation\Request $request
    ): Response {

        $session = $requestStack->getSession();

        if (!$this->isCsrfTokenValid('remove_favorite_'.$id, $request->request->get('_token', ''))) {
            return $this->redirectToRoute('app_mes_favoris');
        }

        $favoris = $session->get('favoris', []);

        $favoris = array_filter($favoris, function ($favId) use ($id) {
            return $favId != $id;
        });

        $session->set('favoris', $favoris);

        return $this->redirectToRoute('app_mes_favoris');
    }

    #[Route('/favoris', name: 'app_favoris_index')]
    #[Route('/mes-favoris', name: 'app_mes_favoris')]
    public function index(
        RequestStack $requestStack,
        RecetteRepository $repo,
        \Symfony\Component\HttpFoundation\Request $request
    ): Response {

        $session = $requestStack->getSession();

        $favoris = $session->get('favoris', []);

        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        if (empty($favoris)) {
            return $this->render('favoris/index.html.twig', [
                'recettes' => [],
                'currentPage' => 1,
                'totalPages' => 1,
                'totalRecipes' => 0,
            ]);
        }

        // Build query for favorited recipes
        $queryBuilder = $repo->createQueryBuilder('r')
            ->leftJoin('r.categorie', 'c')->addSelect('c')
            ->leftJoin('r.auteur', 'a')->addSelect('a')
            ->leftJoin('r.ingredients', 'ing')->addSelect('ing')
            ->leftJoin('r.tags', 't')->addSelect('t')
            ->where('r.id IN (:favoris)')
            ->setParameter('favoris', $favoris)
            ->orderBy('r.dateCreation', 'DESC')
            ->addOrderBy('r.id', 'DESC');

        // Count total favorited recipes
        $countQueryBuilder = clone $queryBuilder;
        $totalRecipes = (int) $countQueryBuilder
            ->select('COUNT(DISTINCT r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalRecipes / $limit));
        $page = min($page, $totalPages);

        // Get paginated favorited recipes
        $recettes = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('favoris/index.html.twig', [
            'recettes' => $recettes,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecipes' => $totalRecipes,
        ]);
    }
}