<?php

namespace App\DataFixtures;

use App\Entity\Advice;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
  private UserPasswordHasherInterface $passwordHasher;

  public function __construct(UserPasswordHasherInterface $passwordHasher)
  {
    $this->passwordHasher = $passwordHasher;
  }

  public function load(ObjectManager $manager): void
  {
    for ($i = 0; $i < 7; $i++) {
      $user = new User();
      $user->setLogin("user" . $i);
      $user->setPostcode($i . "5000");

      $plainPassword = "password" . $i;
      $hashedPassword = $this->passwordHasher->hashPassword($user, $plainPassword);
      $user->setPassword($hashedPassword);

      $manager->persist($user);
    }

    for ($i = 0; $i < 10; $i++) {
      $advice = new Advice();
      $advice->setContent("Conseil n°" . $i);
      $advice->setMonths([$i, $i + 1]);

      $manager->persist($advice);
    }

    $manager->flush();
  }
}
