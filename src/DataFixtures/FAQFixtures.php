<?php

namespace App\DataFixtures;

use App\Entity\FAQ;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FAQFixtures extends Fixture
{
    public const MAX_FAQ_COUNT = 3;
    public function load(ObjectManager $manager)
    {
        for ($i = 0; $i < self::MAX_FAQ_COUNT; $i++) {
            $question = new FAQ();
            $question->setNumero($i + 1);
            $question->setTitle('FAQ ' . $i);
            $question->setContent('Lorem Ipsum ' . $i);
            $manager->persist($question);
        }
        $manager->flush();
    }
}
