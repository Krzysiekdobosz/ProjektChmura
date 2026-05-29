<?php

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Api\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends ApiController
{
    public function index(): JsonResponse
    {
        return $this->success([]);
    }

    public function store(Request $request): JsonResponse
    {
        return $this->error('Zostanie zaimplementowane w Kroku 23.', 501);
    }

    public function show(int $id): JsonResponse
    {
        return $this->success(null);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        return $this->error('Zostanie zaimplementowane w Kroku 23.', 501);
    }
}
