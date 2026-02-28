<?php

namespace App\Http\Middleware;

use App\Repositories\HospitalRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApiKey
{
    public function __construct(
        private HospitalRepository $hospitalRepository
    ) {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key') ?? $request->input('api_key');

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'API key is required',
            ], 401);
        }

        $hospital = $this->hospitalRepository->findByApiKey($apiKey);

        if (!$hospital || !$hospital->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or inactive API key',
            ], 401);
        }

        $request->merge(['hospital' => $hospital]);

        return $next($request);
    }
}
