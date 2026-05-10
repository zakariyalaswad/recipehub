<?php

namespace App\Controller;

use App\Repository\CategorieRecetteRepository;
use App\Repository\IngredientRepository;
use App\Repository\RecetteRepository;
use App\Repository\TagRecetteRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('', name: 'app_admin_dashboard', methods: ['GET'])]
    public function dashboard(
        RecetteRepository $recetteRepository,
        CategorieRecetteRepository $categorieRecetteRepository,
        TagRecetteRepository $tagRecetteRepository,
        IngredientRepository $ingredientRepository,
        UserRepository $userRepository,
    ): Response {
        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'recipes' => $recetteRepository->count([]),
                'users' => $userRepository->count([]),
                'categories' => $categorieRecetteRepository->count([]),
                'tags' => $tagRecetteRepository->count([]),
                'ingredients' => $ingredientRepository->count([]),
            ],
        ]);
    }

    #[Route('/recettes', name: 'app_admin_recettes', methods: ['GET'])]
    public function recettes(
        Request $request,
        RecetteRepository $recetteRepository,
        CategorieRecetteRepository $categorieRecetteRepository,
    ): Response {
        $search = trim((string) $request->query->get('q', ''));
        $categorieId = $request->query->get('categorie');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        $queryBuilder = $recetteRepository->createQueryBuilder('r')
            ->leftJoin('r.categorie', 'c')->addSelect('c')
            ->leftJoin('r.auteur', 'a')->addSelect('a')
            ->orderBy('r.dateCreation', 'DESC')
            ->addOrderBy('r.id', 'DESC');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('LOWER(r.titre) LIKE :search OR LOWER(r.description) LIKE :search OR LOWER(c.nom) LIKE :search OR LOWER(a.pseudo) LIKE :search OR LOWER(a.email) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($categorieId !== null && $categorieId !== '') {
            $queryBuilder
                ->andWhere('c.id = :categorieId')
                ->setParameter('categorieId', (int) $categorieId);
        }

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

        return $this->render('admin/recette.html.twig', [
            'search' => $search,
            'selectedCategory' => $categorieId !== null && $categorieId !== '' ? (int) $categorieId : null,
            'categories' => $categorieRecetteRepository->findBy([], ['nom' => 'ASC']),
            'recipes' => $recipes,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRecipes' => $totalRecipes,
            'from' => $totalRecipes === 0 ? 0 : (($page - 1) * $limit) + 1,
            'to' => min($page * $limit, $totalRecipes),
        ]);
    }

    #[Route('/categories', name: 'app_admin_categories', methods: ['GET'])]
    public function categories(
        Request $request,
        CategorieRecetteRepository $categorieRecetteRepository,
    ): Response {
        $search = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        $queryBuilder = $categorieRecetteRepository->createQueryBuilder('c')
            ->orderBy('c.nom', 'ASC');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('LOWER(c.nom) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $countQueryBuilder = clone $queryBuilder;
        $totalCategories = (int) $countQueryBuilder
            ->select('COUNT(DISTINCT c.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalCategories / $limit));
        $page = min($page, $totalPages);

        $categories = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/categorie_recette.html.twig', [
            'search' => $search,
            'categories' => $categories,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalCategories' => $totalCategories,
            'from' => $totalCategories === 0 ? 0 : (($page - 1) * $limit) + 1,
            'to' => min($page * $limit, $totalCategories),
        ]);
    }

    #[Route('/tags', name: 'app_admin_tags', methods: ['GET'])]
    public function tags(
        Request $request,
        TagRecetteRepository $tagRecetteRepository,
    ): Response {
        $search = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        $queryBuilder = $tagRecetteRepository->createQueryBuilder('t')
            ->orderBy('t.nom', 'ASC');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('LOWER(t.nom) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        $countQueryBuilder = clone $queryBuilder;
        $totalTags = (int) $countQueryBuilder
            ->select('COUNT(DISTINCT t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalTags / $limit));
        $page = min($page, $totalPages);

        $tags = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('admin/tag_recette.html.twig', [
            'search' => $search,
            'tags' => $tags,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalTags' => $totalTags,
            'from' => $totalTags === 0 ? 0 : (($page - 1) * $limit) + 1,
            'to' => min($page * $limit, $totalTags),
        ]);
    }

    #[Route('/utilisateurs', name: 'app_admin_users', methods: ['GET'])]
    public function users(
        Request $request,
        UserRepository $userRepository,
    ): Response {
        $search = trim((string) $request->query->get('q', ''));
        $role = $request->query->get('role');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;

        $queryBuilder = $userRepository->createQueryBuilder('u')
            ->orderBy('u.dateCreation', 'DESC')
            ->addOrderBy('u.id', 'DESC');

        if ($search !== '') {
            $queryBuilder
                ->andWhere('LOWER(u.pseudo) LIKE :search OR LOWER(u.email) LIKE :search')
                ->setParameter('search', '%' . mb_strtolower($search) . '%');
        }

        if ($role !== null && $role !== '') {
            $queryBuilder
                ->andWhere('u.roles LIKE :role')
                ->setParameter('role', '%' . $role . '%');
        }

        $countQueryBuilder = clone $queryBuilder;
        $totalUsers = (int) $countQueryBuilder
            ->select('COUNT(DISTINCT u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalUsers / $limit));
        $page = min($page, $totalPages);

        $users = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        // Count stats
        $admins = $userRepository->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where("u.roles LIKE :admin")
            ->setParameter('admin', '%ROLE_ADMIN%')
            ->getQuery()
            ->getSingleScalarResult();

        $activeAuthors = $userRepository->createQueryBuilder('u')
            ->select('COUNT(DISTINCT u.id)')
            ->leftJoin('u.recettes', 'r')
            ->where('r.id IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/user.html.twig', [
            'search' => $search,
            'selectedRole' => $role,
            'users' => $users,
            'stats' => [
                'totalUsers' => $totalUsers,
                'admins' => $admins,
                'activeAuthors' => $activeAuthors,
            ],
            'page' => $page,
            'totalPages' => $totalPages,
            'totalUsers' => $totalUsers,
            'from' => $totalUsers === 0 ? 0 : (($page - 1) * $limit) + 1,
            'to' => min($page * $limit, $totalUsers),
        ]);
    }
}
