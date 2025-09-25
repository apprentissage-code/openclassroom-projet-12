<?php

namespace App\Controller;

use App\Repository\AdviceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
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

  private function getAdvicePerMonth(AdviceRepository $adviceRepository, int $month)
  {
    return array_filter(
      $adviceRepository->findAll(),
      fn($advice) => in_array($month, $advice->getMonths())
    );
  }
}
