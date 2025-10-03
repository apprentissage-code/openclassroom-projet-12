<?php

namespace App\Controller;

use App\Entity\Advice;
use App\Repository\AdviceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

final class AdviceController extends AbstractController
{
  #[Route('/api/advices/{month}', name: 'advices-month', methods: ['GET'])]
  public function getAdvicesByMonth(int $month, AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
  {
    if ($month < 1 || $month > 12) {
      throw new HttpException(Response::HTTP_UNPROCESSABLE_ENTITY, 'Invalid month.');
    }

    $advices = $adviceRepository->findByMonth($month);
    $jsonAdvices = $serializer->serialize($advices, 'json');
    return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
  }

  #[Route('/api/advices', name: 'advices', methods: ['GET'])]
  public function getAdvices(AdviceRepository $adviceRepository, SerializerInterface $serializer): JsonResponse
  {
    $currentMonth = (int) date('n');
    $advices = $adviceRepository->findByMonth($currentMonth);
    $jsonAdvices = $serializer->serialize($advices, 'json');
    return new JsonResponse($jsonAdvices, Response::HTTP_OK, [], true);
  }

  #[Route('/api/advices/{id}', name: 'delete-advice', methods: ['DELETE'])]
  #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to delete a advice.')]
  public function deleteAdvice(
    Advice $advice,
    EntityManagerInterface $entityManager
  ): JsonResponse {
    if (!$advice) {
      throw new NotFoundHttpException('Advice not found.');
    }

    $entityManager->remove($advice);
    $entityManager->flush();

    return new JsonResponse(
      ['message' => "Advice {$advice->getId()} deleted successfully."],
      Response::HTTP_OK,
      [],
    );
  }

  #[Route('/api/advices/{id}', name: 'modify-advice', methods: ['PUT'])]
  #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to modify a advice.')]
  public function modifyAdvice(
    Advice $advice,
    EntityManagerInterface $entityManager,
    Request $request,
  ): JsonResponse {
    if (!$advice) {
      throw new NotFoundHttpException('Advice not found.');
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
      ['message' => "Advice {$advice->getId()} updated successfully."],
      Response::HTTP_OK,
      [],
    );
  }

  #[Route('/api/advices', name: 'create-advice', methods: ['POST'])]
  #[IsGranted('ROLE_ADMIN', message: 'You do not have sufficient rights to create a advice.')]
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
          'months' => $advice->getMonths(),
        ]
      ],
      Response::HTTP_CREATED,
      [],
    );
  }
}
