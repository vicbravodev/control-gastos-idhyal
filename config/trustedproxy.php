<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | When the app sits behind a reverse proxy (Traefik, nginx, Caddy, Cloudflare,
    | Dokploy, load balancers), Laravel must trust X-Forwarded-* headers so URLs use
    | HTTPS, sessions/cookies match the public host, and client IPs are correct.
    |
    | Use "*" to trust the connecting address as the only proxy (typical in Docker).
    | For stricter setups, use a comma-separated list of IPs or CIDRs, or "REMOTE_ADDR".
    |
    */

    'proxies' => env('TRUSTED_PROXIES', '*'),

];
