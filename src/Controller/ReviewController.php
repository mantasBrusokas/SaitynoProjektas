<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CommentController
 * @package App\Controller
 * @Route("/api", name="comment_api")
 */
class ReviewController extends AbstractController
{
    /**
     * @param UserRepository $userRepository
     * @param int $userId
     * @param int $productId
     * @return JsonResponse
     * @Route("/users/{userId}/products/{productId}/reviews", name="review", methods={"GET"})
     */
    public function getReviews(UserRepository $userRepository, int $userId, int $productId)
    {
        $response = new JsonResponse();
        $reviews = [];
        $data = [];
        $products = [];

        $user = $userRepository->findOneBy(['id' => $userId]);
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
            return $response;
        }

        foreach ($products as $product) {

            if ($product->getId() == $productId) {
                $reviews = $product->getReviews();
                break;
            }
        }

        foreach ($reviews as $review) {

            $data[] =
                [
                    'id' => $review->getId(),
                    'title' => $review->getTitle(),
                    'content' => $review->getContent(),
                    'create_date' =>$review->getCreateDate(),
                ];
        }
        if (empty($data)) {
            $response->setData(
                [
                    'status' => '404',
                    'errors' => 'Review not found',
                ]
            );
            $response->setStatusCode(404);
            return $response;
        }

        return $this->response($data, 200);
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param ProductRepository $productRepository
     * @param  $userId
     * @param  $productId
     * @return JsonResponse
     * @throws \Exception
     * @Route("/users/{userId}/products/{productId}/reviews", name="reviews_add", methods={"POST"})
     */
    public function addReview(Request $request, EntityManagerInterface $entityManager,
                              ProductRepository $productRepository, $userId, $productId)
    {

        try {
            $request = $this->transformJsonBody($request);

            if (!$request || !$request->get('title') || !$request->request->get('content')) {
                throw new \Exception();
            }

            $product = $productRepository->findOneBy(['id' => $productId]);
            if (!empty($product)) {
                if ($product->getUser()->getId() == $userId) {

                    $review = new Review();
                    $review->setTitle($request->get('title'));
                    $review->setContent($request->get('content'));
                    $review->setCreateDate(new \DateTime($request->get('create_date')));
                    $review->setProduct($product);
                    $entityManager->persist($review);
                    $entityManager->flush();

                    $data = [
                        'status' => 201,
                        'success' => "Review added successfully",
                    ];
                    return $this->response($data);
                } else {
                    $data = [
                        'status' => 404,
                        'errors' => "User not found",
                    ];
                    return $this->response($data, 404);
                }
            } else {
                $data = [
                    'status' => 404,
                    'errors' => "Product not found",
                ];
                return $this->response($data, 404);
            }

        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }
    }

    /**
     * @param ReviewRepository $reviewRepository
     * @param ProductRepository $productRepository
     * @param  $reviewId
     * @param  $userId
     * @param  $productId
     * @return JsonResponse
     * @Route("/users/{userId}/products/{productId}/reviews/{reviewId}", name="reviews_get", methods={"GET"})
     */
    public function getReview(ReviewRepository $reviewRepository, ProductRepository $productRepository,
                              $reviewId, $userId, $productId)
    {
        $product = $productRepository->findOneBy(['id' => $productId]);
        $response = new JsonResponse();
        if (!empty($product)) {
            if ($product->getUser()->getId() == $userId) {
                $review = $reviewRepository->find($reviewId);
                $response->setData(
                    [
                        'id' => $review->getId(),
                        'name' => $review->getTitle(),
                        'description' => $review->getContent(),
                        'price' => $review->getCreateDate(),
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
        } else {
            $data = [
                'status' => 404,
                'errors' => "Product not found",
            ];
            return $this->response($data, 404);
        }

        if (!$review) {
            $data = [
                'status' => 404,
                'errors' => "Review not found",
            ];
            return $this->response($data, 404);
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param $reviewId
     * @param $userId
     * @param $productId
     * @return JsonResponse
     * @Route("/users/{userId}/products/{productId}/reviews/{reviewId}", name="reviews_put", methods={"PUT"})
     */
    public function updateReview(Request $request, EntityManagerInterface $entityManager,
                                  UserRepository $userRepository, $reviewId, $userId, $productId)
    {
        try {
            $user = $userRepository->find($userId);
            $found = false;

            if (!$user) {
                $data = [
                    'status' => 404,
                    'errors' => "User not found",
                ];
                return $this->response($data, 404);
            } else {
                $products = $user->getProducts();
                if (!empty($products)) {
                    foreach ($products as $product) {
                        if ($product->getId() == $productId) {

                            $reviews = $product->getReviews();
                            if (!empty($reviews)) {
                                foreach ($reviews as $review) {
                                    if ($review->getId() == $reviewId) {
                                        $request = $this->transformJsonBody($request);

                                        if (!$request || !$request->get('title') || !$request->request->get('content')) {
                                            throw new \Exception();
                                        }
                                        $review->setTitle($request->get('title'));
                                        $review->setContent($request->get('content'));
                                        $review->setCreateDate(new \DateTime($request->get('create_date')));
                                        $entityManager->persist($review);
                                        $entityManager->flush();

                                        $data = [
                                            'status' => 200,
                                            'errors' => "Review updated successfully",
                                        ];
                                        return $this->response($data);
                                    }
                                }
                            }
                        }
                    }
                } else {
                    $data = [
                        'status' => 404,
                        'errors' => "Product not found",
                    ];
                    return $this->response($data, 404);
                }
            }

            if (!$found) {
                $data = [
                    'status' => 404,
                    'errors' => "Review not found",
                ];
                return $this->response($data, 404);
            }

        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }
    }

    /**
     * @param EntityManagerInterface $entityManager
     * @param UserRepository $userRepository
     * @param $reviewId
     * @param $userId
     * @param $productId
     * @return JsonResponse
     * @Route("/users/{userId}/products/{productId}/reviews/{reviewId}", name="reviews_delete", methods={"DELETE"})
     */
    public function deleteReview(EntityManagerInterface $entityManager,
                                  UserRepository $userRepository,
                                 $reviewId,
                                  $userId,
                                 $productId
    )
    {
        $found = false;
        $user = $userRepository->find($userId);

        if (!$user) {
            $data = [
                'status' => 404,
                'errors' => "Review not found",
            ];
            return $this->response($data, 404);
        } else {
            $products = $user->getProducts();
            if (!empty($products)) {
                foreach ($products as $product) {
                    if ($product->getId() == $productId) {
                        $reviews = $product->getReviews();
                        if (!empty($reviews)) {
                            foreach ($reviews as $review) {
                                if ($review->getId() == $reviewId) {
                                    $entityManager->remove($review);
                                    $entityManager->flush();
                                    $data = [
                                        'status' => 200,
                                        'errors' => "Review deleted successfully",
                                    ];
                                    return $this->response($data);
                                }
                            }
                        }
                    }
                }
            }
        }
        if (!$found) {
            $data = [
                'status' => 404,
                'errors' => "Review not found",
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