<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class UserController extends AbstractController
{
  #[Route('/api/users', name: 'user', methods: ['GET'])]
  public function getUsers(UserRepository $userRepository, SerializerInterface $serializer): JsonResponse
  {
    $users = $userRepository->findAll();
    $jsonUsers = $serializer->serialize($users, 'json');
    return new JsonResponse($jsonUsers, Response::HTTP_OK, [], true);
  }

  #[Route('/api/user/{id}', name: 'delete-user', methods: ['DELETE'])]
  #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un utilisateur')]
  public function deleteUser(int $id, UserRepository $userRepository, EntityManagerInterface $entityManager): JsonResponse
  {
    $user = $userRepository->find($id);

    if (!$user) {
      return new JsonResponse(
        ['error' => 'User not found'],
        Response::HTTP_NOT_FOUND
      );
    }

    $entityManager->remove($user);
    $entityManager->flush();

    return new JsonResponse(
      ['message' => "User {$id} deleted successfully"],
      Response::HTTP_OK,
      [],
    );
  }

  #[Route('/api/user/{id}', name: 'modify-user', methods: ['PUT'])]
  #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un utilisateur')]
  public function modifyUser(
    int $id,
    UserRepository $userRepository,
    EntityManagerInterface $entityManager,
    Request $request,
    UserPasswordHasherInterface $passwordHasher
  ): JsonResponse {
    $user = $userRepository->find($id);

    if (!$user) {
      return new JsonResponse(
        ['error' => 'User not found'],
        Response::HTTP_NOT_FOUND
      );
    }

    $data = json_decode($request->getContent(), true);

    if (isset($data['login'])) {
      $user->setLogin($data['login']);
    }

    if (isset($data['postcode'])) {
      $user->setPostcode($data['postcode']);
    }

    if (isset($data['password'])) {
      $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
      $user->setPassword($hashedPassword);
    }

    $entityManager->flush();

    return new JsonResponse(
      ['message' => "User {$id} updated successfully"],
      Response::HTTP_OK,
      [],
    );
  }

  #[Route('/api/user/new', name: 'create-user', methods: ['POST'])]
  public function createUser(
    EntityManagerInterface $entityManager,
    Request $request,
    UserPasswordHasherInterface $passwordHasher
  ): JsonResponse {
    $data = json_decode($request->getContent(), true);

    foreach (['login', 'postcode', 'password'] as $field) {
      if (empty($data[$field])) {
        return new JsonResponse(['error' => ucfirst($field) . ' is mandatory.'], Response::HTTP_BAD_REQUEST);
      }
    }

    $user = new User();
    $user->setLogin($data['login']);
    $user->setPostcode($data['postcode']);
    $hashedPassword = $passwordHasher->hashPassword($user, $data['password']);
    $user->setPassword($hashedPassword);

    $entityManager->persist($user);
    $entityManager->flush();

    return new JsonResponse(
      [
        'message' => "User created successfully.",
        'user' => [
          'id' => $user->getId(),
          'login' => $user->getLogin(),
          'postcode' => $user->getPostcode()
        ]
      ],
      Response::HTTP_CREATED,
      [],
    );
  }
}
