<?php
declare(strict_types=1);

final class VersionController
{
    private const CACHE_TTL = 300;
    private const ERROR_CACHE_TTL = 60;

    public function index(): void
    {
        $installed = env('APP_VERSION', 'dev') ?? 'dev';
        $commit = env('APP_COMMIT', 'unknown') ?? 'unknown';
        $repo = env('GITHUB_REPOSITORY', 'ForgeRGX/TR-Tracking')
            ?? 'ForgeRGX/TR-Tracking';

        $cacheFile = sys_get_temp_dir()
            . '/tr-tracking-release-'
            . hash('sha256', $repo)
            . '.json';

        $now = time();

        if (is_file($cacheFile)) {
            $cached = json_decode(
                (string) file_get_contents($cacheFile),
                true
            );

            if (
                is_array($cached)
                && isset($cached['cachedAt'], $cached['response'])
                && is_array($cached['response'])
            ) {
                $ttl = ($cached['response']['status'] ?? '') === 'error'
                    ? self::ERROR_CACHE_TTL
                    : self::CACHE_TTL;

                if ($now - (int) $cached['cachedAt'] < $ttl) {
                    $this->respond($cached['response']);
                }
            }
        }

        $response = [
            'status' => 'error',
            'installed' => $installed,
            'commit' => $commit,
            'latest' => null,
            'updateAvailable' => null,
            'upToDate' => null,
            'releaseUrl' => null,
            'checkedAt' => gmdate('c'),
            'message' => 'No se pudo comprobar la versión en GitHub.',
        ];

        try {
            if (!preg_match(
                '/^[A-Za-z0-9_.-]+\/[A-Za-z0-9_.-]+$/',
                $repo
            )) {
                throw new RuntimeException('Repositorio no válido.');
            }

            $headers = [
                'Accept: application/vnd.github+json',
                'X-GitHub-Api-Version: 2022-11-28',
                'User-Agent: TR-Tracking',
            ];

            $url = 'https://api.github.com/repos/'
                . $repo
                . '/releases/latest';

            $context = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'header' => implode("\r\n", $headers),
                    'timeout' => 5,
                    'ignore_errors' => true,
                ],
            ]);

            $body = @file_get_contents($url, false, $context);

            $httpStatus = 0;

            foreach ($http_response_header ?? [] as $header) {
                if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $m)) {
                    $httpStatus = (int) $m[1];
                }
            }

            if ($body === false || $httpStatus !== 200) {
                throw new RuntimeException('Error consultando GitHub.');
            }

            $release = json_decode($body, true);

            if (
                !is_array($release)
                || !isset($release['tag_name'], $release['html_url'])
                || !is_string($release['tag_name'])
                || !is_string($release['html_url'])
                || !str_starts_with(
                    $release['html_url'],
                    'https://github.com/' . $repo . '/releases/tag/'
                )
            ) {
                throw new RuntimeException('Respuesta de GitHub no válida.');
            }

            $latest = $release['tag_name'];
            $installedSemver = ltrim($installed, 'vV');
            $latestSemver = ltrim($latest, 'vV');

            $semverPattern =
                '/^\d+\.\d+\.\d+(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$/';

            $installedValid = preg_match(
                $semverPattern,
                $installedSemver
            ) === 1;

            $latestValid = preg_match(
                $semverPattern,
                $latestSemver
            ) === 1;

            if (!$latestValid) {
                throw new RuntimeException('Versión publicada no válida.');
            }

            $response = [
                'status' => 'ok',
                'installed' => $installed,
                'commit' => $commit,
                'latest' => $latest,
                'updateAvailable' => $installedValid
                    ? version_compare(
                        $installedSemver,
                        $latestSemver,
                        '<'
                    )
                    : null,
                'upToDate' => $installedValid
                    ? version_compare(
                        $installedSemver,
                        $latestSemver,
                        '>='
                    )
                    : null,
                'releaseUrl' => $release['html_url'],
                'checkedAt' => gmdate('c'),
                'message' => null,
            ];
        } catch (Throwable $e) {
            // Los detalles internos se guardan en el log, no se exponen.
            error_log('VersionController: ' . $e->getMessage());
        }

        $cache = json_encode([
            'cachedAt' => $now,
            'response' => $response,
        ], JSON_UNESCAPED_SLASHES);

        if ($cache !== false) {
            $tempFile = $cacheFile . '.' . bin2hex(random_bytes(4));

            if (@file_put_contents($tempFile, $cache, LOCK_EX) !== false) {
                @rename($tempFile, $cacheFile);
            }
        }

        $this->respond($response);
    }

    private function respond(array $data): never
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        http_response_code(200);

        echo json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
        );

        exit;
    }
}