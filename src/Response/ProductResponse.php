<?php

namespace App\Response;

use App\Entity\Product;
use Symfony\Component\HttpFoundation\JsonResponse;

class ProductResponse extends JsonResponse
{
    /**
     * @var Product[]
     */
    private $products;

    public function __construct(array $products)
    {
        $this->products = $products;

        if (!empty($this->products)) {

            parent::__construct($this->serialize(), 200);

        } else {

            parent::__construct(
                $data = [
                    'status' => '404',
                    'errors' => 'Products not found',
                ],
                404);
        }
    }

    public function serialize()
    {
        $data = [];

        foreach ($this->products as $product) {
            $data[] =
                [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                ];
        }
        return $data;
    }
}
