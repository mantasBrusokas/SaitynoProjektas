<?php
namespace App\Controller;


use App\Entity\Product;
use App\Repository\ProductRepository;
use App\Repository\UserRepository;
use App\Response\ProductResponse;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ProductController
 * @package App\Controller
 * @Route("/api", name="product_api")
 */
class ProductController extends AbstractController
{
    /** @var ProductRepository */
    private $productRespository;


    public function __construct(
        ProductRepository $productRespository
    )
    {
        $this->productRespository = $productRespository;
    }

    /**
     * @Route("/products/{productId}", name="get-product", methods={"GET"})
     *
     * @param int $productId
     * @return JsonResponse
     */
    public function getOneProduct(int $productId)
    {
        $response = new JsonResponse();
        $product = $this->productRespository->findOneBy(['id' => $productId]);

        if (!empty($product)) {

            $response->setData(
                [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                    'price' => $product->getPrice(),
                ]
            );
            $response->setStatusCode(200);

        } else {
            $response->setData(
                [
                    'status' => '404',
                    'errors' => 'Product not found',
                ]
            );
            $response->setStatusCode(404);
        }
        return $response;
    }

    public function getProductsList()
    {
        return new ProductResponse($this->productRespository->findAll());
    }

    /**
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @param $productId
     * @return JsonResponse
     * @Route("/products/{productId}", name="products_delete_one", methods={"DELETE"})
     */
    public function deleteProductDirectly(EntityManagerInterface $entityManager,
                                       ProductRepository $productRepository, int $productId)
    {
        $product = $productRepository->find($productId);

        if (!$product) {
            $data = [
                'status' => 404,
                'errors' => "Product not found",
            ];
            return $this->response($data, 404);
        }

        $entityManager->remove($product);
        $entityManager->flush();
        $data = [
            'status' => 200,
            'errors' => "Product deleted successfully",
        ];
        return $this->response($data);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param ProductRepository $productRepository
     * @param $productId
     * @return JsonResponse
     * @Route("/products/{productId}", name="products_put_one", methods={"PUT"})
     */
    public function updateProductAdmin(Request $request, EntityManagerInterface $entityManager,
                                       ProductRepository $productRepository, $productId)
    {
        try {
            $product = $productRepository->find($productId);

            if (!$product) {
                $data = [
                    'status' => 404,
                    'errors' => "Product not found",
                ];
                return $this->response($data, 404);
            }

            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('name') || !$request->request->get('description')) {
                throw new \Exception();
            }

            $product->setName($request->get('name'));
            $product->setDescription($request->get('description'));
            $product->setPrice($request->get('price'));
            $product->setCreateDate(new \DateTime($request->get('create_date')));
            $entityManager->persist($product);
            $entityManager->flush();

            $data = [
                'status' => 200,
                'errors' => "Product updated successfully",
            ];
            return $this->response($data);

        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }
    }


    /**
     * @param int $userId
     * @param UserRepository $userRepository
     * @return JsonResponse
     * @Route("/users/{userId}/products", name="products", methods={"GET"})
     */
    public function getProducts(int $userId, UserRepository $userRepository){

        $response = new JsonResponse();
        $user = $userRepository->findOneBy(['id' => $userId]);
        $products = [];
        if (!empty($user)) {
            $products = $user->getProducts();
        } else {
            $response->setData(
                [
                    'status' => '404',
                    'errors' => 'User not found',
                ]
            );
            $response->setStatusCode(404);
        }
        $data = [];
        foreach ($products as $product) {

            $data[] =
                [
                    'id' => $product->getId(),
                    'name' => $product->getName(),
                    'description' => $product->getDescription(),
                    'price' => $product->getPrice(),
                ];
        }
        if (empty($data)) {
            $response->setData(
                [
                    'status' => '404',
                    'errors' => 'Product not found',
                ]
            );
            $response->setStatusCode(404);
        }

        return $response->setData($data);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param int $userId
     * @return JsonResponse
     * @throws \Exception
     * @Route("/users/{userId}/products", name="products_add", methods={"POST"})
     */
    public function addProduct(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository,
                               int $userId){

        try{
            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('name') || !$request->request->get('description')){
                throw new \Exception();
            }

            $user = $userRepository->findOneBy(['id' => $userId]);

            if (!empty($user)) {

                $product = new Product();
                $product->setUser($user);
                $product->setName($request->get('name'));
                $product->setDescription($request->get('description'));
                $product->setPrice($request->get('price'));
                $product->setCreateDate(new \DateTime($request->get('create_date')));
                $entityManager->persist($product);
                $entityManager->flush();

                $data = [
                    'status' => 201,
                    'success' => "Product added successfully",
                ];
                return $this->response($data, 201);

            }else{
                $data = [
                    'status' => 404,
                    'errors' => "User not found",
                ];
                return $this->response($data, 404);
            }

        }catch (\Exception $e){
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }

    }


    /**
     * @param ProductRepository $productRepository
     * @param $productId
     * @param int $userId
     * @return JsonResponse
     * @Route("/users/{userId}/products/{productId}", name="products_get", methods={"GET"})
     */
    public function getProduct(ProductRepository $productRepository, int $productId, int $userId){
        $product = $productRepository->find($productId);
        $response = new JsonResponse();

        if (!$product){
            $data = [
                'status' => 404,
                'errors' => "Product not found",
            ];
            return $this->response($data, 404);
        }
        else{
            if ($product->getUser()->getId() == $userId) {
                $response->setData(
                    [
                        'id' => $product->getId(),
                        'name' => $product->getName(),
                        'description' => $product->getDescription(),
                        'price' => $product->getPrice(),
                    ]
                );
                $response->setStatusCode(200);
            } else {
                $data = [
                    'status' => 404,
                    'errors' => "User not found",
                ];
                return $this->response($data, 404);
            }
            return $response;
        }
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param ProductRepository $productRepository
     * @param $productId
     * @param int $userId
     * @return JsonResponse
     * @Route("/users/{userId}/products/{productId}", name="products_put", methods={"PUT"})
     */
    public function updateProduct(Request $request, EntityManagerInterface $entityManager, ProductRepository $productRepository, $productId,
                                  int $userId){

        try{
            $product = $productRepository->find($productId);

            if (!$product){
                $data = [
                    'status' => 404,
                    'errors' => "Product not found",
                ];
                return $this->response($data, 404);
            }

            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('name') || !$request->request->get('description')){
                throw new \Exception();
            }
            if ($product->getUser()->getId() == $userId) {

            $product->setName($request->get('name'));
            $product->setDescription($request->get('description'));
            $product->setPrice($request->get('price'));
            $entityManager->persist($product);
            $entityManager->flush();

            $data = [
                'status' => 200,
                'errors' => "Product updated successfully",
            ];
                return $this->response($data);

            }
            else{
                $data = [
                    'status' => 404,
                    'errors' => "Product not found",
                ];
                return $this->response($data, 404);
            }

        }catch (\Exception $e){
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }

    }


    /**
     * @param ProductRepository $productRepository
     * @param EntityManagerInterface $entityManager
     * @param $productId
     * @param int $userId
     * @return JsonResponse
     * @Route("/users/{userId}/products/{productId}", name="products_delete", methods={"DELETE"})
     */
    public function deleteProduct(EntityManagerInterface $entityManager, ProductRepository $productRepository,
                                  int $productId, int $userId){
        $product = $productRepository->find($productId);

        if (!$product){
            $data = [
                'status' => 404,
                'errors' => "Product not found",
            ];
            return $this->response($data, 404);
        }
        if ($product->getUser()->getId() == $userId) {

            $entityManager->remove($product);
            $entityManager->flush();
            $data = [
                'status' => 200,
                'errors' => "Product deleted successfully",
            ];
            return $this->response($data);

        }
        else{
            $data = [
                'status' => 404,
                'errors' => "User not found",
            ];
            return $this->response($data, 404);
        }
    }

    /**
     * Returns a JSON response
     *
     * @param array $data
     * @param $status
     * @param array $headers
     * @return JsonResponse
     */
    public function response($data, $status = 200, $headers = [])
    {
        return new JsonResponse($data, $status, $headers);
    }

    protected function transformJsonBody(\Symfony\Component\HttpFoundation\Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if ($data === null) {
            return $request;
        }

        $request->request->replace($data);

        return $request;
    }

}