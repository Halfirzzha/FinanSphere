<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserAgentService
{
    /**
     * Get public IP address with multiple fallback methods
     */
    public static function getPublicIp(): ?string
    {
        // Try to get from cache first (cache for 5 minutes)
        $cacheKey = 'public_ip_' . request()->ip();

        return Cache::remember($cacheKey, 300, function () {
            // Method 1: Try external API services
            $services = [
                'https://api.ipify.org?format=json',
                'https://api.my-ip.io/ip.json',
                'https://ipapi.co/json/',
            ];

            foreach ($services as $service) {
                try {
                    $response = Http::timeout(2)->get($service);
                    if ($response->successful()) {
                        $data = $response->json();
                        if (isset($data['ip']) && filter_var($data['ip'], FILTER_VALIDATE_IP)) {
                            return $data['ip'];
                        }
                    }
                } catch (\Exception $e) {
                    Log::debug("IP service {$service} failed: " . $e->getMessage());
                    continue;
                }
            }

            // Method 2: Check headers for proxy/forwarded IP
            $headers = [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'HTTP_CF_CONNECTING_IP', // Cloudflare
                'HTTP_X_REAL_IP', // Nginx
            ];

            foreach ($headers as $header) {
                if (isset($_SERVER[$header])) {
                    $ips = explode(',', $_SERVER[$header]);
                    foreach ($ips as $ip) {
                        $ip = trim($ip);
                        // Validate and check if it's a public IP
                        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                            return $ip;
                        }
                    }
                }
            }

            // Method 3: Fallback to request IP
            return request()->ip();
        });
    }

    /**
     * Parse user agent to get browser information
     */
    public static function getBrowserInfo(): array
    {
        $userAgent = request()->userAgent() ?? '';

        return [
            'name' => self::getBrowserName($userAgent),
            'version' => self::getBrowserVersion($userAgent),
            'platform' => self::getPlatform($userAgent),
        ];
    }

    /**
     * Get browser name from user agent
     */
    public static function getBrowserName(?string $userAgent = null): string
    {
        $userAgent = $userAgent ?? request()->userAgent() ?? '';

        $browsers = [
            '/Edg/i' => 'Microsoft Edge',
            '/EdgA/i' => 'Microsoft Edge',
            '/MSIE/i' => 'Internet Explorer',
            '/Trident/i' => 'Internet Explorer',
            '/Firefox|FxiOS/i' => 'Mozilla Firefox',
            '/Chrome|CriOS/i' => 'Google Chrome',
            '/Safari/i' => 'Safari',
            '/Opera|OPR/i' => 'Opera',
            '/Brave/i' => 'Brave',
            '/Vivaldi/i' => 'Vivaldi',
            '/Samsung/i' => 'Samsung Browser',
            '/UCBrowser/i' => 'UC Browser',
        ];

        foreach ($browsers as $pattern => $name) {
            if (preg_match($pattern, $userAgent)) {
                // Special case for Chrome vs Safari
                if ($name === 'Safari' && preg_match('/Chrome|CriOS/i', $userAgent)) {
                    continue;
                }
                return $name;
            }
        }

        return 'Unknown Browser';
    }

    /**
     * Get browser version from user agent
     */
    public static function getBrowserVersion(?string $userAgent = null): ?string
    {
        $userAgent = $userAgent ?? request()->userAgent() ?? '';

        $patterns = [
            '/Edg\/([0-9.]+)/i',
            '/EdgA\/([0-9.]+)/i',
            '/Firefox\/([0-9.]+)/i',
            '/FxiOS\/([0-9.]+)/i',
            '/Chrome\/([0-9.]+)/i',
            '/CriOS\/([0-9.]+)/i',
            '/Version\/([0-9.]+).*Safari/i',
            '/OPR\/([0-9.]+)/i',
            '/Opera\/([0-9.]+)/i',
            '/MSIE ([0-9.]+)/i',
            '/rv:([0-9.]+)/i',
            '/Samsung\/([0-9.]+)/i',
            '/UCBrowser\/([0-9.]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $userAgent, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * Get platform/OS from user agent
     */
    public static function getPlatform(?string $userAgent = null): string
    {
        $userAgent = $userAgent ?? request()->userAgent() ?? '';

        $platforms = [
            '/windows nt 10/i' => 'Windows 10/11',
            '/windows nt 6.3/i' => 'Windows 8.1',
            '/windows nt 6.2/i' => 'Windows 8',
            '/windows nt 6.1/i' => 'Windows 7',
            '/windows nt 6.0/i' => 'Windows Vista',
            '/windows nt 5.2/i' => 'Windows Server 2003',
            '/windows nt 5.1/i' => 'Windows XP',
            '/windows|win32|win64/i' => 'Windows',
            '/macintosh|mac os x/i' => 'macOS',
            '/mac_powerpc/i' => 'Mac OS Classic',
            '/linux/i' => 'Linux',
            '/android/i' => 'Android',
            '/iphone/i' => 'iPhone',
            '/ipad/i' => 'iPad',
            '/ipod/i' => 'iPod',
            '/blackberry/i' => 'BlackBerry',
            '/webos/i' => 'Mobile',
        ];

        foreach ($platforms as $pattern => $platform) {
            if (preg_match($pattern, $userAgent)) {
                return $platform;
            }
        }

        return 'Unknown Platform';
    }

    /**
     * Get device type (desktop, mobile, tablet)
     */
    public static function getDeviceType(?string $userAgent = null): string
    {
        $userAgent = $userAgent ?? request()->userAgent() ?? '';

        if (preg_match('/tablet|ipad/i', $userAgent)) {
            return 'Tablet';
        }

        if (preg_match('/mobile|android|iphone|ipod|blackberry|webos/i', $userAgent)) {
            return 'Mobile';
        }

        return 'Desktop';
    }

    /**
     * Check if request is from mobile device
     */
    public static function isMobile(): bool
    {
        return self::getDeviceType() === 'Mobile';
    }

    /**
     * Check if request is from bot/crawler
     */
    public static function isBot(?string $userAgent = null): bool
    {
        $userAgent = $userAgent ?? request()->userAgent() ?? '';

        $botPatterns = [
            '/bot/i',
            '/crawl/i',
            '/spider/i',
            '/slurp/i',
            '/facebook/i',
            '/google/i',
            '/bing/i',
            '/yahoo/i',
            '/baidu/i',
            '/yandex/i',
            '/duckduck/i',
            '/twitter/i',
            '/linkedin/i',
            '/whatsapp/i',
            '/telegram/i',
            '/slack/i',
            '/discourse/i',
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get location data from IP (optional enhancement)
     */
    public static function getLocationFromIp(?string $ip = null): ?array
    {
        $ip = $ip ?? self::getPublicIp();

        if (!$ip || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return null;
        }

        $cacheKey = "ip_location:{$ip}";

        return Cache::remember($cacheKey, 86400, function () use ($ip) {
            try {
                $response = Http::timeout(3)->get("http://ip-api.com/json/{$ip}");

                if ($response->successful()) {
                    $data = $response->json();

                    if ($data['status'] === 'success') {
                        return [
                            'country' => $data['country'] ?? null,
                            'country_code' => $data['countryCode'] ?? null,
                            'region' => $data['regionName'] ?? null,
                            'city' => $data['city'] ?? null,
                            'timezone' => $data['timezone'] ?? null,
                            'isp' => $data['isp'] ?? null,
                        ];
                    }
                }
            } catch (\Exception $e) {
                Log::debug("IP geolocation failed for {$ip}: " . $e->getMessage());
            }

            return null;
        });
    }

    /**
     * Get comprehensive user agent information
     */
    public static function getFullInfo(): array
    {
        $userAgent = request()->userAgent() ?? '';
        $ipPublic = self::getPublicIp();

        $info = [
            'user_agent' => $userAgent,
            'browser_name' => self::getBrowserName($userAgent),
            'browser_version' => self::getBrowserVersion($userAgent),
            'platform' => self::getPlatform($userAgent),
            'device_type' => self::getDeviceType($userAgent),
            'is_mobile' => self::isMobile(),
            'is_bot' => self::isBot($userAgent),
            'ip_private' => request()->ip(),
            'ip_public' => $ipPublic,
        ];

        // Add location data if available
        $location = self::getLocationFromIp($ipPublic);
        if ($location) {
            $info['location'] = $location;
        }

        return $info;
    }

    /**
     * Get security risk score for current request (0-100)
     * Higher score = higher risk
     */
    public static function getSecurityRiskScore(): int
    {
        $score = 0;
        $info = self::getFullInfo();

        // Bot detection
        if ($info['is_bot']) {
            $score += 30;
        }

        // Suspicious user agent
        if (empty($info['user_agent']) || strlen($info['user_agent']) < 10) {
            $score += 20;
        }

        // Unknown browser
        if ($info['browser_name'] === 'Unknown Browser') {
            $score += 15;
        }

        // Private IP access (potential proxy/VPN)
        if ($info['ip_private'] !== $info['ip_public']) {
            $score += 10;
        }

        // Check for common suspicious patterns
        $suspiciousPatterns = ['/curl/i', '/wget/i', '/python/i', '/java/i', '/perl/i'];
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $info['user_agent'])) {
                $score += 25;
                break;
            }
        }

        return min($score, 100);
    }
}
