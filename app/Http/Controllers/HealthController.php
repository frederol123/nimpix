<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'timestamp' => now()->toIso8601String(),
        ];

        $healthy = ! in_array(false, [
            $checks['database'],
            $checks['cache'],
        ], true);

        return response()->json($checks, $healthy ? 200 : 503);
    }

    private function checkDatabase(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function checkCache(): bool
    {
        try {
            Cache::put('health-check', true, 10);
            $value = Cache::get('health-check');

            return $value === true;
        } catch (\Throwable) {
            return false;
        }
    }
}
