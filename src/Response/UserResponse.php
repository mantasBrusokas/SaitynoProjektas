<?php

namespace App\Response;

use App\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserResponse extends JsonResponse
{
    /**
     * @var User[]
     */
    private $users;

    public function __construct(array $users)
    {
        $this->users = $users;

        if (!empty($this->users)) {

            parent::__construct($this->serialize(), 200);

        } else {

            parent::__construct(
                $data = [
                    'status' => '404',
                    'errors' => 'User not found',
                ],
                404);
        }
    }

    public function serialize()
    {
        $data = [];

        foreach ($this->users as $user) {
            $data[] =
                [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                ];
        }
        return $data;
    }
}