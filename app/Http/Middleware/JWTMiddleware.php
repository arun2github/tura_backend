<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;

class JWTMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
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
}