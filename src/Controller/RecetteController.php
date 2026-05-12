<?php

namespace App\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Entity\Recette;
use App\Form\RecetteType;
use App\Repository\RecetteRepository;
use App\Repository\CategorieRecetteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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
    public function index(RecetteRepository $recetteRepository, CategorieRecetteRepository $categorieRecetteRepository, Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 10;
        $search = trim((string) $request->query->get('search', ''));
        $categoryId = $request->query->get('categorie');

        // Get all published recipes with filters
        $queryBuilder = $recetteRepository->createQueryBuilder('r')
            ->leftJoin('r.categorie', 'c')->addSelect('c')
            ->leftJoin('r.auteur', 'a')->addSelect('a')
            ->where('r.publiee = true')
            ->orderBy('r.dateCreation', 'DESC')
            ->addOrderBy('r.id', 'DESC');

        // Apply search filter
        if ($search !== '') {
            $searchLower = '%' . mb_strtolower($search) . '%';
            $queryBuilder
                ->andWhere('LOWER(r.titre) LIKE :search 
                    OR LOWER(r.description) LIKE :search 
                    OR LOWER(a.pseudo) LIKE :search 
                    OR LOWER(a.email) LIKE :search
                    OR EXISTS (
                        SELECT 1 FROM App\\Entity\\Ingredient ing2
                        WHERE ing2 MEMBER OF r.ingredients
                        AND LOWER(ing2.nom) LIKE :search
                    )
                    OR EXISTS (
                        SELECT 1 FROM App\\Entity\\TagRecette t2
                        WHERE t2 MEMBER OF r.tags
                        AND LOWER(t2.nom) LIKE :search
                    )')
                ->setParameter('search', $searchLower);
        }

        // Apply category filter
        if ($categoryId !== null && $categoryId !== '') {
            $queryBuilder
                ->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', (int) $categoryId);
        }

        // Count total recipes
        $countQueryBuilder = clone $queryBuilder;
        $totalRecipes = (int) $countQueryBuilder
            ->select('COUNT(DISTINCT r.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $totalPages = max(1, (int) ceil($totalRecipes / $limit));
        $page = min($page, $totalPages);

        // Get paginated recipes
        $recettes = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('recette/index.html.twig', [
            'recettes' => $recettes,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalRecipes' => $totalRecipes,
            'categories' => $categorieRecetteRepository->findBy([], ['nom' => 'ASC']),
            'search' => $search,
            'selectedCategory' => $categoryId,
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

            return $this->redirectToRoute($this->getRecipeRedirectRoute(), [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('recette/new.html.twig', [
            'recette' => $recette,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_recette_show', methods: ['GET'])]
    public function show(Recette $recette, RequestStack $requestStack): Response
    {
        $favoris = $requestStack->getSession()->get('favoris', []);

        return $this->render('recette/show.html.twig', [
            'recette' => $recette,
            'isFavorite' => in_array($recette->getId(), $favoris),
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

            return $this->redirectToRoute($this->getRecipeRedirectRoute(), [], Response::HTTP_SEE_OTHER);
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

        return $this->redirectToRoute($this->getRecipeRedirectRoute(), [], Response::HTTP_SEE_OTHER);
    }

    private function getRecipeRedirectRoute(): string
    {
        return $this->isGranted('ROLE_ADMIN') ? 'app_admin_recettes' : 'app_recette_index';
    }
}
