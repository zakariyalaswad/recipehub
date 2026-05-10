<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\TagRecette;

class TagRecetteFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {   
        $tags = [
        ['key' => 'vegetarien', 'nom' => 'Végétarien', 'couleur' => '#2ecc71'],
        ['key' => 'vegan', 'nom' => 'Vegan', 'couleur' => '#27ae60'],
        ['key' => 'sans_gluten', 'nom' => 'Sans Gluten', 'couleur' => '#f1c40f'],
        ['key' => 'rapide', 'nom' => 'Rapide', 'couleur' => '#3498db'],
        ['key' => 'familial', 'nom' => 'Familial', 'couleur' => '#9b59b6'],
        ['key' => 'festif', 'nom' => 'Festif', 'couleur' => '#e74c3c'],
        ['key' => 'bio', 'nom' => 'Bio', 'couleur' => '#1abc9c'],
        ['key' => 'economique', 'nom' => 'Économique', 'couleur' => '#e67e22'],
    ];
        foreach ($tags as $data) {
            $tag = new TagRecette();
            $tag->setNom($data['nom']);
            $tag->setCouleur($data['couleur']);

            $manager->persist($tag);

            $this->addReference('tag_'.$data['nom'], $tag);
        }

        $manager->flush();
    }
}
