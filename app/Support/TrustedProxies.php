<?php

namespace App\Support;

/**
 * In production a request reaches PHP through two proxies:
 *   visitor -> Cloudflare edge -> host Apache (Docker gateway) -> this container
 * so REMOTE_ADDR is the Docker gateway and X-Forwarded-For is "<visitor>, <Cloudflare edge>".
 * Trusting both hops makes $request->ip() the visitor. A client bypassing Cloudflare can't
 * spoof it: the hop next to the host Apache is then its own, untrusted, IP.
 */
final class TrustedProxies
{
    // Docker bridge networks (the host Apache reaches the container from 172.17.0.1).
    private const DOCKER = ['127.0.0.1', '172.16.0.0/12'];

    // https://www.cloudflare.com/ips-v4 and /ips-v6, fetched 2026-11-22.
    private const CLOUDFLARE = [
        '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22',
        '141.101.64.0/18', '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20',
        '197.234.240.0/22', '198.41.128.0/17', '162.158.0.0/15', '104.16.0.0/13',
        '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
        '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32',
        '2405:8100::/32', '2a06:98c0::/29', '2c0f:f248::/32',
    ];

    /**
     * NT_TRUSTED_PROXIES: "cloudflare" (default) = Docker gateway + Cloudflare ranges,
     * "*" = whatever connects, or a comma-separated list of IPs/CIDRs.
     */
    public static function from(?string $setting): array|string
    {
        $setting = trim((string) $setting);

        return match ($setting) {
            '', 'cloudflare' => [...self::DOCKER, ...self::CLOUDFLARE],
            '*'              => '*',
            default          => array_values(array_filter(array_map('trim', explode(',', $setting)))),
        };
    }
}
