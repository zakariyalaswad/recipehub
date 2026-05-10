<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\CategorieRecette;

class CategorieFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            ['nom' => 'Entrée', 'icone' => '🥗'],
            ['nom' => 'Plat', 'icone' => '🍝'],
            ['nom' => 'Dessert', 'icone' => '🍰'],
            ['nom' => 'Boisson', 'icone' => '🥤'],
            ['nom' => 'Snack', 'icone' => '🍕'],
            ['nom' => 'Soupe', 'icone' => '🥣'],
        ];

        foreach ($categories as $data) {
            $cat = new CategorieRecette();
            $cat->setNom($data['nom']);
            $cat->setIcone($data['icone']);

            $manager->persist($cat);

            $this->addReference('cat_'.$data['nom'], $cat);
        }

        $manager->flush();
    }
}
