<?php

namespace App\Enum;

enum TypeTransport: string
{
    case AVION = 'AVION';
    case TRAIN = 'TRAIN';
    case BUS = 'BUS';
    case BATEAU = 'BATEAU';
    case TAXI = 'TAXI';
    case VOITURE = 'VOITURE';

    public function getLabel(): string
    {
        return match($this) {
            self::AVION => 'Avion',
            self::TRAIN => 'Train',
            self::BUS => 'Bus',
            self::BATEAU => 'Bateau',
            self::TAXI => 'Taxi',
            self::VOITURE => 'Voiture',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::AVION => '✈️',
            self::TRAIN => '🚆',
            self::BUS => '🚌',
            self::BATEAU => '🚢',
            self::TAXI => '🚕',
            self::VOITURE => '🚗',
        };
    }
}
