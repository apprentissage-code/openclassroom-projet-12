<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class MeteoController extends AbstractController
{
  private string $apiKey;
  private string $country;

  public function __construct(string $openweatherApiKey, string $openweatherCountry)
  {
    $this->apiKey = $openweatherApiKey;
    $this->country = $openweatherCountry;
  }

  #[Route('/api/external/meteo/{zipCode}', name: 'app_meteo', methods: ['GET'])]
  public function getMeteoPerCity(int $zipCode, HttpClientInterface $client, TagAwareCacheInterface $cache): JsonResponse
  {
    $cacheId = "meteo_zip_" . $zipCode;
    $data = $cache->get($cacheId, function (ItemInterface $item) use ($client, $zipCode) {
      $item->tag('meteoCache');
      $response = $client->request(
        'GET',
        "https://api.openweathermap.org/data/2.5/weather?q={$zipCode},{$this->country}&appid={$this->apiKey}"
      );

      return [
        "content" => $response->getContent(),
        "statusCode" => $response->getStatusCode()
      ];
    });

    return new JsonResponse($data["content"], $data["statusCode"], [], true);
  }
}
