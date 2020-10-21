<?php

namespace App\Http\Middleware;

use Closure;
use Tymon\JWTAuth\Middleware\GetUserFromToken;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class JWTAuth extends GetUserFromToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        if (!$token = $this->auth->setRequest($request)->getToken()) {
            //return $this->respond('tymon.jwt.absent', 'token_not_provided', 400);
            $cus_response['data']    = '';
            $cus_response['code']    = 403;
            $cus_response['status']  = false;
            $cus_response['message'] = 'Please Login first!';
            return response()->json($cus_response, 200, array(), JSON_NUMERIC_CHECK);
        }
        
        try {
            $user = $this->auth->authenticate($token);
        }
        catch (TokenExpiredException $e) {
            //return $this->respond('tymon.jwt.expired', 'token_expired', $e->getStatusCode(), [$e]);
            $cus_response['data']    = '';
            $cus_response['code']    = 403;
            $cus_response['status']  = false;
            $cus_response['message'] = 'Session Expired, Please Re-Login!';
            return response()->json($cus_response, 200, array(), JSON_NUMERIC_CHECK);
        }
        catch (UnauthorizedHttpException $e) {
            //return $this->respond('tymon.jwt.expired', 'token_expired', $e->getStatusCode(), [$e]);
            $cus_response['data']    = '';
            $cus_response['code']    = 403;
            $cus_response['status']  = false;
            $cus_response['message'] = 'Session Expired, Please Re-Login!';
            return response()->json($cus_response, 200, array(), JSON_NUMERIC_CHECK);
        }
        catch (JWTException $e) {
        //            return response()->json([
        //                'meta' => [
        //                    'code' => 401, 
        //                    'error_type' => 'token_invalid',
        //                    'error_message' => 'Please provide the correct token'
        //                ]], 401);
            $cus_response['data']    = '';
            $cus_response['code']    = 403;
            $cus_response['status']  = false;
            $cus_response['message'] = 'Some Error Occured, Please Retry!';
            return response()->json($cus_response, 200, array(), JSON_NUMERIC_CHECK);
        }
        
        if (!$user) {
            //return $this->respond('tymon.jwt.user_not_found', 'user_not_found', 404);
            $cus_response['data']    = '';
            $cus_response['code']    = 403;
            $cus_response['status']  = false;
            $cus_response['message'] = 'Token Invalid, Please Retry!';
            return response()->json($cus_response, 200, array(), JSON_NUMERIC_CHECK);
        }
        
        $this->events->fire('tymon.jwt.valid', $user);
        
        return $next($request);
    }
}