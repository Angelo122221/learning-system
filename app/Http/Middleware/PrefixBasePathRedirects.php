<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PrefixBasePathRedirects
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $baseUrl = $this->baseUrl($request);
        $location = $response->headers->get('Location');

        if ($baseUrl === '' || ! is_string($location) || $location === '') {
            return $response;
        }

        $prefixedLocation = $this->prefixLocation($location, $baseUrl, $request);

        if ($prefixedLocation !== $location) {
            $response->headers->set('Location', $prefixedLocation);
        }

        return $response;
    }

    private function baseUrl(Request $request): string
    {
        $baseUrl = rtrim($request->getBaseUrl(), '/');

        if ($baseUrl !== '') {
            return $baseUrl;
        }

        $configured = trim((string) config('app.frontend_base_path', ''));

        if ($configured === '' || $configured === '/') {
            return '';
        }

        return '/'.trim($configured, '/');
    }

    private function prefixLocation(string $location, string $baseUrl, Request $request): string
    {
        if (str_starts_with($location, '//')) {
            return $location;
        }

        if (str_starts_with($location, '/')) {
            return str_starts_with($location, $baseUrl.'/') || $location === $baseUrl
                ? $location
                : $baseUrl.$location;
        }

        $parts = parse_url($location);

        if (! isset($parts['scheme'], $parts['host'])) {
            return $location;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $origin = $parts['scheme'].'://'.$parts['host'].$port;

        if ($origin !== $request->getSchemeAndHttpHost()) {
            return $location;
        }

        $path = $parts['path'] ?? '/';

        if ($path === $baseUrl || str_starts_with($path, $baseUrl.'/')) {
            return $location;
        }

        $query = isset($parts['query']) ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $origin.$baseUrl.$path.$query.$fragment;
    }
}
