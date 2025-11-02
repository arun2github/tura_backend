<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Closure;

class Authenticate extends Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string[]  ...$guards
     * @return mixed
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function handle($request, Closure $next, ...$guards)
    {
        // Check if this is an API request that needs JWT authentication
        if ($request->is('api/*') && $this->needsJWTAuth($request)) {
            return $this->handleJWTAuth($request, $next);
        }

        // For non-API routes, use default Laravel authentication
        return parent::handle($request, $next, ...$guards);
    }

    /**
     * Check if the request needs JWT authentication
     */
    protected function needsJWTAuth($request)
    {
        // List of API endpoints that require JWT authentication
        $jwtRoutes = [
            'api/jobs',
            'api/getApplicationProgress',
            'api/savePersonalDetails',
            'api/saveEmploymentDetails',
            'api/getEmploymentDetails',
            'api/saveQualificationDetails',
            'api/getQualificationDetails',
            'api/getDocumentRequirements',
            'api/uploadDocuments',
            'api/getUploadedDocuments',
            'api/downloadDocument',
            'api/getCompleteApplicationDetails',
            'api/getAvailableJobsForApplication',
            'api/startJobApplication'
        ];

        $currentPath = trim($request->getPathInfo(), '/');
        
        // Check if current path matches any JWT route
        foreach ($jwtRoutes as $route) {
            if (str_contains($currentPath, $route) || fnmatch($route . '/*', $currentPath)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Handle JWT authentication
     */
    protected function handleJWTAuth($request, $next)
    {
        try {
            // Check if token is provided in Authorization header
            $token = $request->header('Authorization');
            
            if (!$token) {
                // Also check for token in request body for backward compatibility
                $token = $request->input('token');
            }
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token not provided. Please login first.',
                    'error_code' => 'TOKEN_MISSING'
                ], 401);
            }
            
            // Remove "Bearer " prefix if present
            if (strpos($token, 'Bearer ') === 0) {
                $token = substr($token, 7);
            }
            
            // Set the token for JWTAuth
            JWTAuth::setToken($token);
            
            // Authenticate the user
            $user = JWTAuth::parseToken()->authenticate();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'error_code' => 'USER_NOT_FOUND'
                ], 404);
            }
            
            // Add user to request for use in controllers
            $request->merge(['authenticated_user' => $user]);
            
        } catch (TokenExpiredException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token expired. Please login again.',
                'error_code' => 'TOKEN_EXPIRED'
            ], 401);
            
        } catch (TokenInvalidException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid token. Please login again.',
                'error_code' => 'TOKEN_INVALID'
            ], 401);
            
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token error. Please login again.',
                'error_code' => 'TOKEN_ERROR'
            ], 401);
        }
        
        return $next($request);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
