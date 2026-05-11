<?php

namespace App\Controller;

use App\Repository\CategorieRecetteRepository;
use App\Repository\RecetteRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function index(
        Request $request,
        RecetteRepository $recetteRepository,
        CategorieRecetteRepository $categorieRecetteRepository,
    ): Response
    {
        $search = trim((string) $request->query->get('search', ''));
        $categorieId = $request->query->get('categorie');
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = 6;

        // Build featured recipe (static for now)
        $featuredRecipe = [
            'title' => 'Chocolate and peanut butter overnight oats',
            'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAFxM9PKX5rqx_fXr6yeZm69ZCqmAWuJNkCMHh3qE1SP8l1aEbwubV52l8ynyk9PAUB9XlC-1YioBbg33k3KTP-jNX9GuSwqQBh2IPX8w1pdB9C4j34OGcIU8fnpnWImbCsq_QkqZbN-OsUeakB-VmegZiO-bsXju3AnGER9K8dq0oMQK5UwFfefM4A5rGGajLAs5WBoE6skq1J7WR2VwNFrNPdWVEIJnOmg88nNPQ8kD-xo8TVE3dGoDnwA6AnKGnGp1rMnfApAVg',
            'badge' => 'Featured Recipe',
            'prepTime' => '10m Prep',
            'servings' => '1 Serving',
            'calories' => '200 kcal',
        ];

        $ingredients = [
            '45g rolled oats',
            '40g soya yogurt',
            '15g peanut butter',
            '10g chia seeds',
            '5g cocoa powder',
            '150ml unsweetened almond milk',
        ];

        // Build query for recipes with search and category filters
        $queryBuilder = $recetteRepository->createQueryBuilder('r')
            ->leftJoin('r.categorie', 'c')->addSelect('c')
            ->leftJoin('r.auteur', 'a')->addSelect('a')
            ->leftJoin('r.ingredients', 'ing')->addSelect('ing')
            ->leftJoin('r.tags', 't')->addSelect('t')
            ->where('r.publiee = true')
            ->orderBy('r.dateCreation', 'DESC')
            ->addOrderBy('r.id', 'DESC');

        if ($search !== '') {
            $searchLower = '%' . mb_strtolower($search) . '%';
            $queryBuilder
                ->andWhere('LOWER(r.titre) LIKE :search 
                    OR LOWER(r.description) LIKE :search 
                    OR LOWER(a.pseudo) LIKE :search 
                    OR LOWER(a.email) LIKE :search
                    OR LOWER(ing.nom) LIKE :search
                    OR LOWER(t.nom) LIKE :search')
                ->setParameter('search', $searchLower);
        }

        if ($categorieId !== null && $categorieId !== '') {
            $queryBuilder
                ->andWhere('c.id = :categorieId')
                ->setParameter('categorieId', (int) $categorieId);
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
        $recipes = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $this->render('home/index.html.twig', [
            'featuredRecipe' => $featuredRecipe,
            'ingredients' => $ingredients,
            'categories' => $categorieRecetteRepository->findBy([], ['nom' => 'ASC']),
            'highlights' => [
                ['icon' => 'eco', 'title' => 'Eco-Friendly', 'description' => 'Sustainable choices', 'class' => 'bg-tertiary-fixed'],
                ['icon' => 'fitness_center', 'title' => 'High Protein', 'description' => 'Fuel your workout', 'class' => 'bg-primary-fixed'],
                ['icon' => 'cake', 'title' => 'Low Sugar', 'description' => 'Guilt-free treats', 'class' => 'bg-surface-variant hidden lg:block'],
            ],
            'instructions' => [
                'Using a teaspoon, mix all the ingredients together in a food container or a medium sized mixing bowl until well combined and the peanut butter is incorporated.',
                "Spoon the mixture into a food container (if you haven't done so already) and place the lid firmly on the container to prevent any drying.",
                'Finally, put the container in the fridge overnight (or for at least 6 hours) before eating to allow the oats and seeds to absorb the liquid.',
            ],
            'nutrition' => [
                ['label' => 'Calories', 'value' => '200'],
                ['label' => 'Protein', 'value' => '12g'],
                ['label' => 'Carbs', 'value' => '24g'],
                ['label' => 'Fats', 'value' => '8g'],
            ],
            'author' => [
                'name' => 'Rob Simpson',
                'role' => 'Recipe Creator & Developer',
                'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAIvxhwVc5qxRn7R6QVC-eI-xaTXKU-Fgveq3ZN5OynbvPOQ0OU0q-R6ql0BAQlRmqCmBMiYD6GqFmGgHsNvC8aD7oVj9ekKI705RJzoW3EiwIPyjBudRtMdkmq3uNNqOZfSktEH9QmFa_86VO48DwMWx4z2rlE77sG0nAHUd2RRp3B6RNxrngreaJqh3rPdNWkf9hboLvmXB635VylsBJc9fzEcxnBK1sJCbanNdduOKtFytFz_jilLeV4GpjQ91sfEzcXlr-YxWg',
            ],
            'suggestions' => [
                [
                    'title' => 'Blueberry and lemon overnight oats',
                    'rating' => '4.8',
                    'tag' => 'Vegan',
                    'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAb9R9snTt1DqyY_9nkRNqSY-XLfXweVyGuFgmClPwXKKmOzoEkiURpy-JYMw8kr3iXRbB22m6qFOepih9yV4qY8F_ipqC3DTr7mdjp6RF5N5G1zABo-77SWczgU9IWM31_pJ8RYafbB1ieZ9FdksuoJdbfPekVg20EQHnpI887WqHo1ai_Yn_B2pzILnILPZPC3rdYpWTyITWvQ9dLl50jNP7vaKTthMiApcQ-AOlgSfCOtutMPGOByYn-3iPe-bgf62Cr814ZG1Q',
                    'calories' => '200 kcal',
                    'protein' => '12g Protein',
                    'time' => '15m Prep',
                ],
                [
                    'title' => 'Fresh Mediterranean Bowl',
                    'rating' => '4.9',
                    'tag' => 'Healthy',
                    'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBRId7ZXTNm1Lkm9ruYMW_HH_sqaAZc0AzR8qIiast9EI7D_ya-fugzSIiemb3A-TEEHvvO2O6oThoiDSxktYGAHaTzm02WrENJq5j9l-0bY2IcgZmG69DHrGIyDpvfU6s7MOzELuBp02K3j-FXHHw7e6x7Z5QgCNzYHPGVlFKS1YVbl9XYM70WqJy7MkG4kdRKegb1OdXXi-wjbk9sQdDcfNLTyYXLbF3wryk09rMImpHGNE5Y6ReDupsqxLKtPN8Rh9ukPnx2WoE',
                    'calories' => '350 kcal',
                    'protein' => '18g Protein',
                    'time' => '20m Prep',
                ],
                [
                    'title' => 'Avocado & Poached Egg Toast',
                    'rating' => '4.7',
                    'tag' => 'Breakfast',
                    'image' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAPqxmErhSpEJqMzACb2LD2UJKrjcB4t_Fwco6HSQlPoASA-mglEJLNxIMA9r_hTdV-vzyDORX1_Hw6Qryjw0527e4W01pWHC61Zx5yIEPQmh5URtnTyjRAytDjgMq7hc6mJldQdlxO6Jbu0QINnEoY3I9DjarPgweAfvp6yYPLf7pDsgePjnBec7BwbzH-CEX5wUvCU1hVDZFzqVrVMtjGODzRQRFIa93DsxCs2G9TyDgvzEfGju5KcC_J3TFGpb7342fgrAYQbXc',
                    'calories' => '280 kcal',
                    'protein' => '14g Protein',
                    'time' => '10m Prep',
                ],
            ],
            'search' => $search,
            'selectedCategory' => $categorieId !== null && $categorieId !== '' ? (int) $categorieId : null,
            'recipes' => $recipes,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRecipes' => $totalRecipes,
            'from' => $totalRecipes === 0 ? 0 : (($page - 1) * $limit) + 1,
            'to' => min($page * $limit, $totalRecipes),
        ]);
    }
}