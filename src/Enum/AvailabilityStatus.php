<?php

namespace App\Enum;

enum AvailabilityStatus: string
{
    case IN_STOCK = 'In Stock';
    case ON_ORDER = 'On Order';
    case REPLENISHING = 'Replenishing';
    case PREORDER = 'Preorder';
    case OUT_OF_STOCK = 'Out of Stock';

    // Cette méthode sera utile lorsque vous implémenterez les traductions.
    // Pour l'instant, elle retourne la valeur brute qui est déjà en anglais.
    public function getLabel(): string
    {
        return $this->value; // Retourne directement la valeur anglaise
        // Plus tard, pour la traduction :
        // return match ($this) {
        //     self::IN_STOCK => 'availability.in_stock', // Clé de traduction
        //     self::ON_ORDER => 'availability.on_order',
        //     self::REPLENISHING => 'availability.replenishing',
        //     self::PREORDER => 'availability.preorder',
        //     self::OUT_OF_STOCK => 'availability.out_of_stock',
        // };
    }
}