<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RewriteMediaUrls
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Only rewrite when using S3
        $disk = env('FILESYSTEM_DISK', config('filesystems.default', 'local'));
        if ($disk !== 's3') {
            return $response;
        }

        // Compute bases
        $appBase = rtrim(url(''), '/');
        $imagesPath = rtrim((string) config('global.IMG_PATH', '/images'), '/');
        $jsonPath = rtrim((string) config('global.JSON_PATH', '/json'), '/');
        $roots = array_unique(array_filter([
            $imagesPath,
            $jsonPath,
            '/assets',
        ]));

        $s3Base = rtrim((string) config('filesystems.disks.s3.url')
            ?: (string) config('filesystems.disks.s3.endpoint'), '/');
        if ($s3Base === '') {
            $bucket = config('filesystems.disks.s3.bucket');
            $region = config('filesystems.disks.s3.region');
            $s3Base = "https://{$bucket}.s3.{$region}.amazonaws.com";
        }
        // Build mapping list from local to S3 for known media roots
        $replacements = [];
        foreach ($roots as $root) {
            $replacements[$appBase . $root . '/'] = $s3Base . $root . '/';
        }

        // Skip binary/streamed responses
        if ($response instanceof BinaryFileResponse) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');

        try {
            if ($response instanceof JsonResponse || Str::contains($contentType, 'application/json')) {
                $data = $response instanceof JsonResponse ? $response->getData(true) : json_decode($response->getContent(), true);
                if (is_array($data)) {
                    $rewritten = $this->rewriteArrayWithMap($data, $replacements);
                    if ($response instanceof JsonResponse) {
                        $response->setData($rewritten);
                    } else {
                        $response->setContent(json_encode($rewritten));
                    }
                }
                return $response;
            }

            // Handle HTML/text responses by simple base replace
            $content = $response->getContent();
            if (is_string($content) && $content !== '') {
                $updated = $content;
                foreach ($replacements as $from => $to) {
                    $updated = str_replace($from, $to, $updated);
                }
                $response->setContent($updated);
            }
        } catch (\Throwable $e) {
            Log::warning('RewriteMediaUrls failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    private function rewriteArrayWithMap($data, array $map)
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->rewriteArrayWithMap($value, $map);
            } elseif (is_string($value)) {
                foreach ($map as $from => $to) {
                    if (Str::startsWith($value, $from)) {
                        $value = $to . substr($value, strlen($from));
                        break;
                    }
                }
                $data[$key] = $value;
            }
        }
        return $data;
    }
}
