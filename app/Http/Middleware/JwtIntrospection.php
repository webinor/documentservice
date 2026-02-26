<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JwtIntrospection
{

    

    public function handle($request, Closure $next)
    {
        $token = $request->bearerToken();

        Log::info('Token reçu : ' . ($token ?? 'NULL'));

        if (!$token) {
            return response()->json(['error' => 'Token manquant'], 401);
        }

        $response = Http::withHeaders([
            'Authorization' => "Bearer $token"
        ])->get(config('services.user_service.base_url') . '/auth/validate');

        if ($response->failed()) {
            return response()->json(['error' => 'Token invalide'], 401);
        }

        $request->attributes->add(['user' => $response->json()]);

        return $next($request);
    }
}
