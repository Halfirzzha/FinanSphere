<?php

namespace App\Http\Middleware;

use App\Services\UserAgentService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrackUserActivity
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            $info = UserAgentService::getFullInfo();

            // Update current session info
            $request->user()->update([
                'current_ip_private' => $info['ip_private'],
                'current_ip_public' => $info['ip_public'],
                'current_browser' => $info['browser_name'],
                'current_browser_version' => $info['browser_version'],
                'current_platform' => $info['platform'],
                'current_user_agent' => $info['user_agent'],
            ]);
        }

        return $next($request);
    }
}
