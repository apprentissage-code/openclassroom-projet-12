<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CityNotFoundException extends NotFoundHttpException
{
  public function __construct(string $city)
  {
    parent::__construct("City '{$city}' not found.");
  }
}
