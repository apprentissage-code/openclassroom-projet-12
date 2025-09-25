<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class AdviceController extends AbstractController
{
  #[Route('/api/advices/{month}', name: 'advices-month', methods: ['GET'])]
  public function getAdvicesByMonth(int $month, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
  {
    $advices = $this->getAdvicePerMonth($adviceRepository, $month);
    $jsonAdvices = $serializer->serialize($advices, 'json');
    return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
  }

  #[Route('/api/advices', name: 'advices', methods: ['GET'])]
  public function getAdvices(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
  {
    $currentMonth = (int) date('n');
    $advices = $this->getAdvicePerMonth($adviceRepository, $currentMonth);
    $jsonAdvices = $serializer->serialize($advices, 'json');
    return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
  }

  #[Route('/api/advice/{id}', name: 'delete-advice', methods: ['DELETE'])]
  #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour supprimer un conseil.')]
  public function deleteAdvice(int $id, AdviceRepository $adviceRepository, EntityManagerInterface $entityManager): JsonResponse
  {
    $advice = $adviceRepository->find($id);

    if (!$advice) {
      return new JsonResponse(
        ['error' => 'Advice not found'],
        Response::HTTP_NOT_FOUND
      );
    }

    $entityManager->remove($advice);
    $entityManager->flush();

    return new JsonResponse(
      ['message' => "Advice {$id} deleted successfully"],
      Response::HTTP_OK,
      [],
    );
  }

  #[Route('/api/advice/{id}', name: 'modify-advice', methods: ['PUT'])]
  #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour modifier un conseil.')]
  public function modifyAdvice(
    int $id,
    AdviceRepository $adviceRepository,
    EntityManagerInterface $entityManager,
    Request $request,
  ): JsonResponse {
    $advice = $adviceRepository->find($id);

    if (!$advice) {
      return new JsonResponse(
        ['error' => 'Advice not found'],
        Response::HTTP_NOT_FOUND
      );
    }

    $data = json_decode($request->getContent(), true);

    if (isset($data['content'])) {
      $advice->setContent($data['content']);
    }

    if (isset($data['months'])) {
      $advice->setMonths($data['months']);
    }

    $entityManager->flush();

    return new JsonResponse(
      ['message' => "Advice {$id} updated successfully"],
      Response::HTTP_OK,
      [],
    );
  }

  #[Route('/api/advice/new', name: 'create-advice', methods: ['POST'])]
  #[IsGranted('ROLE_ADMIN', message: 'Vous n\'avez pas les droits suffisants pour créer un conseil.')]
  public function createAdvice(
    EntityManagerInterface $entityManager,
    Request $request,
  ): JsonResponse {

    $advice = new Advice();
    $data = json_decode($request->getContent(), true);

    if (isset($data['content'])) {
      $advice->setContent($data['content']);
    }

    if (isset($data['months'])) {
      $advice->setMonths($data['months']);
    }

    $entityManager->persist($advice);
    $entityManager->flush();

    return new JsonResponse(
      [
        'message' => "Advice created successfully",
        'advice' => [
          'id' => $advice->getId(),
          'content' => $advice->getContent(),
          'mnths' => $advice->getMonths(),
        ]
      ],
      Response::HTTP_OK,
      [],
    );
  }

  private function getAdvicePerMonth(AdviceRepository $adviceRepository, int $month)
  {
    return array_filter(
      $adviceRepository->findAll(),
      fn($advice) => in_array($month, $advice->getMonths())
    );
  }
}
