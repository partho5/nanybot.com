<?php

namespace App\Support;

/**
 * SSRF guard for URLs supplied by API clients: http(s) only, standard ports,
 * and a host that is (or resolves to) a public IP address.
 */
class PublicUrl
{
    /**
     * @param  bool  $resolve  also resolve DNS and make sure every address is public
     */
    public static function isSafe(string $url, bool $resolve = false): bool
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        if (isset($parts['port']) && ! in_array($parts['port'], [80, 443], true)) {
            return false;
        }

        $host = strtolower(trim($parts['host'], '[]'));

        if ($host === '' || $host === 'localhost' || preg_match('/\.(localhost|local|internal|lan|home|corp)$/', $host)) {
            return false;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::isPublicIp($host);
        }

        // Obfuscated IPv4 forms (decimal, hex, octal) are not valid hostnames here.
        if (preg_match('/^(0x[0-9a-f]+|\d+)(\.(0x[0-9a-f]+|\d+))*$/i', $host)) {
            return false;
        }

        if (! $resolve) {
            return true;
        }

        $ips = gethostbynamel($host) ?: [];
        foreach (dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            if (isset($record['ipv6'])) {
                $ips[] = $record['ipv6'];
            }
        }

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! self::isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private static function isPublicIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
            return false;
        }

        // Carrier-grade NAT (100.64.0.0/10) isn't covered by the PHP flags.
        if (! str_contains($ip, ':') && (ip2long($ip) & 0xFFC00000) === 0x64400000) {
            return false;
        }

        // IPv4-mapped IPv6 (::ffff:10.0.0.1) and link-local/ULA ranges the flags may miss.
        if (str_contains($ip, ':')) {
            if (preg_match('/^::ffff:(\d+\.\d+\.\d+\.\d+)$/i', $ip, $m)) {
                return self::isPublicIp($m[1]);
            }

            return ! preg_match('/^(fe[89ab][0-9a-f]:|f[cd][0-9a-f]{2}:|::1?$)/i', $ip);
        }

        return true;
    }
}
