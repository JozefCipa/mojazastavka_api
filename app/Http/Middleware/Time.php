<?php

namespace App\Http\Middleware;

use Closure;

class Time
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);


        if($response->headers->get('content-type') == 'application/json')
        {
            //entire request time
            $collection = $response->getData();
            $collection->responseTime = microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"] . 's';

            return response()->json($collection);
        }

        return $response;
    }
}
