<?php

namespace App\Controller\Api;

use App\Entity\Product;
use App\Exception\VatRateNotFoundException;
use App\Service\PriceCalculatorService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints\Country;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * A dedicated API action to calculate the context-aware price of a product.
 * It considers the connected user's default shipping address to apply the correct VAT.
 */
#[AsController]
class ProductPriceAction extends AbstractController
{
    // The constructor does not need the Security service anymore if we use IsGranted
    public function __construct(
        private readonly PriceCalculatorService $priceCalculator,
        private readonly LoggerInterface $logger,
        private readonly ValidatorInterface $validator
    ) {
    }

    #[Route(
        path: '/api/products/{id}/price',
        name: 'api_product_get_price',
        methods: ['GET']
    )]
    // This attribute ensures that only authenticated users can access this endpoint.
    // It also simplifies the logic below, as $this->getUser() will never be null.
    #[IsGranted('ROLE_USER')]
    public function __invoke(Product $product): JsonResponse
    {
        $variant = $product->getVariants()->first();
        if (!$variant) {
            return new JsonResponse(['error' => 'Product has no variants.'], 404);
        }

        // Thanks to #[IsGranted], we are sure to have a user.
        $user = $this->getUser();
        $shippingAddress = $user->getCustomerProfile()?->getDefaultShippingAddress();
        $countryCode = $shippingAddress ? $shippingAddress->getCountry() : null;

        if ($countryCode) {
            $errors = $this->validator->validate($countryCode, new Country());
            if (count($errors) > 0) {
                $this->logger->warning('Invalid country code found on shipping address.', [
                    'country_code' => $countryCode,
                    'customer_id' => $user->getCustomerProfile()?->getId(),
                    'address_id' => $shippingAddress?->getId(),
                    'error' => (string) $errors,
                ]);
                $shippingAddress = null; 
            }
        }
        
        try {
            $calculatedPrice = $this->priceCalculator->calculatePriceBreakdown($variant, $shippingAddress);
        } catch (VatRateNotFoundException $e) {
            // Log the configuration error
            $this->logger->error($e->getMessage(), [
                'product_id' => $product->getId(),
                'failed_country_code' => $countryCode,
                'customer_id' => $user->getCustomerProfile()?->getId(),
            ]);

            // Log the fallback action as a notice for monitoring purposes.
            $this->logger->notice('VatRateNotFound: Falling back to default country VAT calculation.', [
                'product_id' => $product->getId(),
                'customer_id' => $user->getCustomerProfile()?->getId(),
            ]);
            
            // Fallback to default country price if the specific one is not found.
            $calculatedPrice = $this->priceCalculator->calculatePriceBreakdown($variant, null);
        }
        
        return $this->json($calculatedPrice, 200, [], ['groups' => 'price:read']);
    }
}