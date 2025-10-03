<?php

namespace App\Controller;

use App\Entity\User;
use App\Exceptions\CityNotFoundException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
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
    $data = $this->callToApiOpenWeatherMap($city, $cacheId, $client, $cache);

    return new JsonResponse($data["content"], $data["statusCode"], [], true);
  }

  #[Route('/api/external/meteo', name: 'meteo', methods: ['GET'])]
  public function getMeteo(HttpClientInterface $client, TagAwareCacheInterface $cache, Request $request): JsonResponse
  {
    $zipCode = $request->query->get('zipCode');
    if (!$zipCode) {
      $user = $this->getUser();
      if (!$user instanceof User || !$user->getPostcode()) {
        throw new BadRequestHttpException('No city specified.');
      }
      $zipCode = $user->getPostcode();
    }

    if (empty($zipCode)) {
      throw new BadRequestHttpException('The postal code is mandatory.');
    }

    $cacheId = "meteo_zip_" . $zipCode;
    $data = $this->callToApiOpenWeatherMap($zipCode, $cacheId, $client, $cache);

    return new JsonResponse($data["content"], $data["statusCode"], [], true);
  }

  private function callToApiOpenWeatherMap(string $cityOrZip, string $cacheId, HttpClientInterface $client, TagAwareCacheInterface $cache)
  {
    $data = $cache->get($cacheId, function (ItemInterface $item) use ($client, $cityOrZip) {
      $item->expiresAfter(600);
      $item->tag('meteoCache');

      if (is_numeric($cityOrZip)) {
        $queryParam = $cityOrZip . ',' . $this->country;
        $queryKey = 'zip';
      } else {
        $queryParam = $cityOrZip;
        $queryKey = 'q';
      }

      $response = $client->request(
        'GET',
        "https://api.openweathermap.org/data/2.5/weather",
        [
          'query' => [
            $queryKey => $queryParam,
            'appid' => $this->apiKey,
            'units' => 'metric',
          ],
        ]
      );

      if ($response->getStatusCode() !== 200) {
        throw new CityNotFoundException((string) $cityOrZip);
      }

      return [
        "content" => $response->getContent(),
        "statusCode" => $response->getStatusCode()
      ];
    });

    return $data;
  }
}
