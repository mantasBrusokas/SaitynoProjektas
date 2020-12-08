<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Response\UserResponse;
use Symfony\Component\Security\Core\User\UserInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

/**
 * Class UserController
 * @package App\Controller
 * @Route("/api", name="post_api")
 */
class UserController extends ApiController
{

    /** @var UserRepository */
    private $userRepository;


    public function __construct(
        UserRepository $userRepository,
        EntityManagerInterface $entityManagerInterface
    )
    {
        $this->userRepository = $userRepository;
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * @var User $user
     * @Route("/users", name="get-users-list", methods={"GET"})
     */
    public function getUsersList()
    {
        return new UserResponse($this->userRepository->findAll());
        /*
        $response = new JsonResponse();
        $user = $this->getUser();
        if ($user->getRole() == 'ADMIN') {
        }
        $response->setData(
            [
                'status' => '403',
                'errors' => 'Access denied',
            ]
        );
        $response->setStatusCode(403);
        return $response; */
    }

    /**
     * @Route("/users/{userId}", name="get-user", methods={"GET"})
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function getOneUser(int $userId)
    {
        $response = new JsonResponse();
        $user = $this->userRepository->findOneBy(['id' => $userId]);
        $userAuth = $this->getUser()->getId();

        if (!empty($user)&&(($userId == $userAuth)||$user->getRole() == 'ADMIN')) {

            $response->setData(
                [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                ]
            );
            $response->setStatusCode(200);

        }
        elseif (empty($user)){
            $response->setData(
                [
                    'status' => '404',
                    'errors' => 'User not found',
                ]
            );
            $response->setStatusCode(404);
        }
        else {

            $response->setData(
                [
                    'status' => '403',
                    'errors' => 'Access denied',
                ]
            );
            $response->setStatusCode(403);
        }
        return $response;
    }


    /**
     * @Route("/users/{userId}", name="delete-user", methods={"DELETE"})
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function deleteUser(int $userId)
    {
        $response = new JsonResponse();
        $user = $this->userRepository->findOneBy(['id' => $userId]);

        if (!empty($user)) {
            $this->entityManagerInterface->remove($user);
            $this->entityManagerInterface->flush();
            $data = [
                'status' => 200,
                'success' => "User deleted successfully",
            ];
            return $this->response($data);

        } else {
            $response->setData(
                [
                    'status' => '404',
                    'errors' => 'User not found',
                ]
            );
            $response->setStatusCode(404);
        }
        return $response;
    }

    /**
     * @Route("/users/{userId}", name="update-user", methods={"PUT"})
     * @param Request $request
     * @param int $userId
     * @return JsonResponse
     */
    public function updateUser(Request $request, int $userId)
    {
        $response = new JsonResponse();
        $data = json_decode($request->getContent(), true);
        $user = $this->userRepository->findOneBy(['id' => $userId]);
        if (empty($user)) {
            $response->setData(
                [
                    'status' => '404',
                    'errors' => 'User not found',
                ]
            );
            $response->setStatusCode(404);

            return $response;
        }

        if (isset($data['email']) || isset($data['role'])) {
            $response->setStatusCode(200);
        } else {
            $response->setData(
                [
                    'status' => '422',
                    'errors' => 'Data not valid',
                ]
            );
            $response->setStatusCode(422);
            return $response;
        }

        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['role'])) {
            $user->setRole($data['role']);
        }
        $this->entityManagerInterface->persist($user);
        $this->entityManagerInterface->flush();
        $data = [
            'status' => 200,
            'errors' => "User updated successfully",
        ];
        return $this->response($data);
    }

    public function register(Request $request, EntityManagerInterface $entityManager,
                             UserPasswordEncoderInterface $passwordEncoder): Response
    {
        try {

            $request = $this->transformJsonBody($request);

            $user = new User();
            $user->setEmail($request->get('email'));
            $user->setPassword(
                $passwordEncoder->encodePassword(
                    $user,
                    $request->get('password')
                ));
            $user->setRole(
                $request->get('role')
            );
            $entityManager->persist($user);
            $entityManager->flush();

            $data = [
                'status' => 201,
                'success' => "Register successfully",
            ];
            return $this->response($data,201);

        } catch (\Exception $e) {
            $data = [
                'status' => 422,
                'errors' => "Data no valid",
            ];
            return $this->response($data, 422);
        }
    }

    /**
     * @Route("/activeUser", name="get-active-user", methods={"GET"})
     * @return JsonResponse
     */
    public function getActiveUser()
    {
        $response = new JsonResponse();
        if ($this->getUser() == null) {
            $response->setData(['message' => 'Unauthorized']);
            $response->setStatusCode(401);
            return $response;
        }
        $response->setData(
            [
                'id' => $this->getUser()->getId(),
                'email' => $this->getUser()->getEmail(),
                'role' => $this->getUser()->getRole(),
            ]
        );
        $response->setStatusCode(200);
        return $response;
    }

    /**
     * @param UserInterface $user
     * @param JWTTokenManagerInterface $JWTManager
     * @return JsonResponse
     */
    public function getTokenUser(UserInterface $user, JWTTokenManagerInterface $JWTManager)
    {
        return new JsonResponse(['token' => $JWTManager->create($user)]);
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