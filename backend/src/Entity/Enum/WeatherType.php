<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum WeatherType: string
{
    case CLOUD = 'clouds';
    case RAIN = 'rain';
    case PARTLY_CLOUD = 'partly_cloud';
    case SUN = 'sun';
    case CLEAR = 'clear';
    case SNOW = 'snow';
    case THUNDER = 'thunderstorm';
    case UNKNOWN = 'unknown';
}
