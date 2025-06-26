<?php

namespace App\Serializer;

use Brick\Money\Money;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class MoneyNormalizer implements NormalizerInterface
{
    /**
     * @param Money $object
     * @param string|null $format
     * @param array $context
     * @return array
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        return [
            // On envoie le montant en centimes (minor amount) sous forme de chaîne 
            // pour éviter les limites des entiers en JS sur de très grands nombres.
            'amount' => (string) $object->getMinorAmount()->toInt(),
            'currency' => $object->getCurrency()->getCurrencyCode(),
        ];
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Money;
    }
    
    // Vous pouvez laisser getSupportedTypes comme ceci pour les versions récentes de Symfony
    public function getSupportedTypes(?string $format): array
    {
        return [
            Money::class => true,
        ];
    }
}