<?php

namespace App\Enum;

enum StatutBillet: string
{
    case EN_ATTENTE = 'EN_ATTENTE';
    case CONFIRME   = 'CONFIRME';
    case PAYE       = 'PAYE';
    case ANNULE     = 'ANNULE';

    public function getLabel(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::CONFIRME   => 'Confirmé',
            self::PAYE       => 'Payé',
            self::ANNULE     => 'Annulé',
        };
    }

    public function getCouleur(): string
    {
        return match($this) {
            self::EN_ATTENTE => '#ffc107',
            self::CONFIRME   => '#17a2b8',
            self::PAYE       => '#28a745',
            self::ANNULE     => '#dc3545',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'fa-hourglass-half',
            self::CONFIRME   => 'fa-check',
            self::PAYE       => 'fa-credit-card',
            self::ANNULE     => 'fa-ban',
        };
    }
}