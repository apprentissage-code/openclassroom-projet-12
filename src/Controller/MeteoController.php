<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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

  #[Route('/api/external/meteo/{city}', name: 'meteo-zip-code', methods: ['GET'])]
  public function getMeteoPerCity(string $city, HttpClientInterface $client, TagAwareCacheInterface $cache): JsonResponse
  {
    $cacheId = "meteo_city_" . $city;
    $data = $cache->get($cacheId, function (ItemInterface $item) use ($client, $city) {
      $item->tag('meteoCache');
      $response = $client->request(
        'GET',
        "https://api.openweathermap.org/data/2.5/weather?q={$city},{$this->country}&appid={$this->apiKey}"
      );

      return [
        "content" => $response->getContent(),
        "statusCode" => $response->getStatusCode()
      ];
    });

    return new JsonResponse($data["content"], $data["statusCode"], [], true);
  }

  #[Route('/api/external/meteo', name: 'meteo', methods: ['GET'])]
  public function getMeteo(HttpClientInterface $client, TagAwareCacheInterface $cache, Request $request): JsonResponse
  {
    $zipCode = $request->query->get('zipCode');
    if (!$zipCode) {
      $user = $this->getUser();
      if (!$user instanceof User || !$user->getPostcode()) {
        return new JsonResponse(['error' => 'Aucune ville renseignée'], 400);
      }
      $zipCode = $user->getPostcode();
    }

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
