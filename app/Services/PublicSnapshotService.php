<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Small file-backed stale-while-revalidate snapshot for public, read-only API
 * payloads. It intentionally lives outside Laravel's normal cache so
 * `php artisan optimize:clear` does not erase the last known-good homepage.
 */
class PublicSnapshotService
{
    private const REFRESH_RETRY_SECONDS = 30;

    public function serve(string $key, int $freshSeconds, callable $resolver): JsonResponse
    {
        $snapshot = $this->read($key);
        $now = time();

        if ($snapshot && ($now - (int) $snapshot['generated_at']) <= $freshSeconds) {
            return $this->response($snapshot, 'HIT');
        }

        if ($snapshot) {
            $this->scheduleRefresh($key, $resolver);
            return $this->response($snapshot, 'STALE');
        }

        $data = $this->resolvePayload($resolver);
        $snapshot = [
            'generated_at' => $now,
            'data' => $data,
        ];
        $this->write($key, $snapshot);

        return $this->response($snapshot, 'MISS');
    }

    public function refreshNow(string $key, callable $resolver): array
    {
        $data = $this->resolvePayload($resolver);
        $snapshot = [
            'generated_at' => time(),
            'data' => $data,
        ];
        $this->write($key, $snapshot);
        return $snapshot;
    }

    private function scheduleRefresh(string $key, callable $resolver): void
    {
        $attempt = $this->attemptPath($key);
        $lastAttempt = is_file($attempt) ? (int) @filemtime($attempt) : 0;
        if ($lastAttempt && (time() - $lastAttempt) < self::REFRESH_RETRY_SECONDS) {
            return;
        }

        $this->ensureDirectory();
        @touch($attempt);

        app()->terminating(function () use ($key, $resolver) {
            $lockHandle = @fopen($this->lockPath($key), 'c');
            if (!$lockHandle) {
                return;
            }

            if (!@flock($lockHandle, LOCK_EX | LOCK_NB)) {
                @fclose($lockHandle);
                return;
            }

            $queryCount = 0;
            $dbMs = 0.0;
            DB::listen(function ($query) use (&$queryCount, &$dbMs) {
                $queryCount++;
                $dbMs += (float) $query->time;
            });

            $started = microtime(true);
            try {
                $this->refreshNow($key, $resolver);
                $totalMs = (microtime(true) - $started) * 1000;
                if ($totalMs >= 1000) {
                    Log::warning('SPAD public snapshot refresh was slow.', [
                        'snapshot' => $key,
                        'total_ms' => round($totalMs, 1),
                        'db_queries' => $queryCount,
                        'db_ms' => round($dbMs, 1),
                    ]);
                }
            } catch (Throwable $e) {
                Log::error('SPAD public snapshot refresh failed; stale data preserved.', [
                    'snapshot' => $key,
                    'message' => $e->getMessage(),
                    'exception' => get_class($e),
                    'db_queries' => $queryCount,
                    'db_ms' => round($dbMs, 1),
                ]);
            } finally {
                @flock($lockHandle, LOCK_UN);
                @fclose($lockHandle);
            }
        });
    }

    private function resolvePayload(callable $resolver): array
    {
        $value = $resolver();

        if ($value instanceof JsonResponse || $value instanceof Response) {
            $decoded = json_decode((string) $value->getContent(), true);
            if (is_array($decoded)) {
                return $decoded;
            }
            throw new \RuntimeException('Public snapshot resolver returned invalid JSON.');
        }

        if (is_array($value)) {
            return $value;
        }

        if ($value instanceof \JsonSerializable) {
            $decoded = json_decode(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        throw new \RuntimeException('Public snapshot resolver returned an unsupported payload.');
    }

    private function response(array $snapshot, string $state): JsonResponse
    {
        $age = max(0, time() - (int) $snapshot['generated_at']);
        return response()->json($snapshot['data'])
            ->header('X-SPAD-Snapshot', $state)
            ->header('X-SPAD-Snapshot-Age', (string) $age)
            ->header('Cache-Control', 'public, max-age=10, stale-if-error=86400');
    }

    private function read(string $key): ?array
    {
        $path = $this->snapshotPath($key);
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded) || !isset($decoded['generated_at']) || !array_key_exists('data', $decoded) || !is_array($decoded['data'])) {
            return null;
        }

        return $decoded;
    }

    private function write(string $key, array $snapshot): void
    {
        $this->ensureDirectory();
        $path = $this->snapshotPath($key);
        $tmp = $path . '.' . getmypid() . '.tmp';
        $json = json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false || @file_put_contents($tmp, $json, LOCK_EX) === false) {
            @unlink($tmp);
            throw new \RuntimeException('Unable to write public snapshot.');
        }
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            throw new \RuntimeException('Unable to publish public snapshot.');
        }
    }

    private function ensureDirectory(): void
    {
        $directory = $this->directory();
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new \RuntimeException('Unable to create public snapshot directory.');
        }
    }

    private function directory(): string
    {
        return storage_path('app/spad-runtime');
    }

    private function safeKey(string $key): string
    {
        return preg_replace('/[^A-Za-z0-9_.-]/', '_', $key) ?: 'snapshot';
    }

    private function snapshotPath(string $key): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . $this->safeKey($key) . '.json';
    }

    private function lockPath(string $key): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . $this->safeKey($key) . '.lock';
    }

    private function attemptPath(string $key): string
    {
        return $this->directory() . DIRECTORY_SEPARATOR . $this->safeKey($key) . '.attempt';
    }
}
