<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;

class NotificationController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->success([]);
    }

    public function readAll(): JsonResponse
    {
        return $this->success(null);
    }

    public function read(int $id): JsonResponse
    {
        return $this->success(null);
    }
}
