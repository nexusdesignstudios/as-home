<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class RewriteApiResponseUrls
{
    public function handle($request, Closure $next)
    {
        // Only rewrite when using S3
        $disk = env('FILESYSTEM_DISK', config('filesystems.default', 'local'));
        if ($disk !== 's3') {
            return $next($request);
        }

        $response = $next($request);

        // Skip binary/streamed responses
        if ($response instanceof BinaryFileResponse) {
            return $response;
        }

        $contentType = $response->headers->get('Content-Type', '');

        try {
            if ($response instanceof JsonResponse || Str::contains($contentType, 'application/json')) {
                $data = $response instanceof JsonResponse ? $response->getData(true) : json_decode($response->getContent(), true);
                if (is_array($data)) {
                    $rewritten = $this->rewriteArrayWithMap($data, []);
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
                $updated = $this->rewriteStringUrls($content);
                $response->setContent($updated);
            }
        } catch (\Throwable $e) {
            Log::warning('RewriteApiResponseUrls failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $response;
    }

    private function rewriteArrayWithMap($data, array $map)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    // Recursively process nested arrays
                    $data[$key] = $this->rewriteArrayWithMap($value, $map);
                } elseif (is_string($value)) {
                    // Process string values for URL rewriting
                    $originalValue = $value;
                    $value = $this->rewriteStringUrls($value);

                    if ($value !== $originalValue) {
                    }

                    $data[$key] = $value;
                }
                // Leave other data types (int, bool, null, etc.) unchanged
            }
        }
        return $data;
    }

    private function rewriteStringUrls($value)
    {
        $s3Base = rtrim((string) config('filesystems.disks.s3.url')
            ?: (string) config('filesystems.disks.s3.endpoint'), '/');
        if ($s3Base === '') {
            $bucket = config('filesystems.disks.s3.bucket');
            $region = config('filesystems.disks.s3.region');
            $s3Base = "https://{$bucket}.s3.{$region}.amazonaws.com";
        }

        // Match URLs that contain /images/, /json/, or /assets/images/ from any domain
        if (preg_match('#^https?://[^/]+(/images/[^/]+.*)$#', $value, $matches)) {
            return $s3Base . $matches[1];
        } elseif (preg_match('#^https?://[^/]+(/json/[^/]+.*)$#', $value, $matches)) {
            return $s3Base . $matches[1];
        } elseif (preg_match('#^https?://[^/]+(/assets/images/[^/]+.*)$#', $value, $matches)) {
            return $s3Base . $matches[1];
        }

        return $value;
    }
}
