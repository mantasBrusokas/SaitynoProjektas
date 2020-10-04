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
        parent::__construct($this->serialize(), $this->status());
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

    public function status()
    {
        return 200;
    }
}