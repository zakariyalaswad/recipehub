<?php

namespace App\Controller;

use App\Repository\TagRecetteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TagsController extends AbstractController
{
    #[Route('/tags', name: 'app_tags_index', methods: ['GET'])]
    public function index(
        Request $request,
        TagRecetteRepository $tagRecetteRepository,
    ): Response {
        $search = trim((string) $request->query->get('q', ''));
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 12;

        $queryBuilder = $tagRecetteRepository->createQueryBuilder('t')
            ->leftJoin('t.recettes', 'r')->addSelect('r')
            ->orderBy('t.nom', 'ASC');

        if ($search !== '') {
            $queryBuilder
                ->where('LOWER(t.nom) LIKE :search')
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

        return $this->render('tags/index.html.twig', [
            'tags' => $tags,
            'totalTags' => $totalTags,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'search' => $search,
        ]);
    }
}
