<?php

namespace AppBundle\Service;

use Sylius\Component\Order\Context\CartContextInterface;
use Sylius\Component\Order\Model\OrderInterface;
use Symfony\Component\Serializer\SerializerInterface;

class CartProviderService
{
    private $cartContext;

    private $serializer;

    public function __construct(CartContextInterface $cartContext, SerializerInterface $serializer)
    {
        $this->cartContext = $cartContext;
        $this->serializer = $serializer;
    }

    public function getCart()
    {
        return $this->cartContext->getCart();
    }

    // TODO Remove this method
    public function normalize(OrderInterface $cart)
    {
        return $this->serializer->normalize($cart, 'json', [
            'groups' => ['order']
        ]);
    }
}
