<?php

declare(strict_types=1);

namespace App\Shared\Http;

use CurlHandle;
use RuntimeException;
use Throwable;

final readonly class ParallelFeedHttpClient
{
    public const int MAX_CONCURRENCY = 10;
    private const int MAX_REDIRECTS = 5;
    private const int TIMEOUT_SECONDS = 12;

    public function __construct(private PublicUrlGuard $urlGuard)
    {
    }

    /**
     * Download one bounded batch. Each result retains its request key.
     *
     * @param array<int, array{url: string, headers: array<string, string>}> $requests
     * @return array<int, array{response: HttpResponse|Throwable, seconds: float}>
     */
    public function getMany(array $requests): array
    {
        if (count($requests) > self::MAX_CONCURRENCY) {
            throw new RuntimeException('Too many concurrent feed requests.');
        }

        $results = [];
        $started = [];
        foreach ($requests as $key => $request) {
            $started[$key] = hrtime(true);
        }

        for ($redirects = 0; $requests !== []; ++$redirects) {
            $multi = curl_multi_init();
            $active = [];
            $next = [];
            try {
                foreach ($requests as $key => $request) {
                    try {
                        $connection = $this->urlGuard->resolveForConnection($request['url']);
                        $transfer = new FeedHttpTransfer();
                        $handle = $this->createHandle($connection, $request['headers'], $transfer);
                        if (curl_multi_add_handle($multi, $handle) !== CURLM_OK) {
                            throw new RuntimeException('Unable to queue feed request.');
                        }

                        $active[spl_object_id($handle)] = [
                            'key' => $key,
                            'handle' => $handle,
                            'transfer' => $transfer,
                            'url' => $connection['url'],
                        ];
                    } catch (Throwable $exception) {
                        $results[$key] = [
                            'response' => $exception,
                            'seconds' => (hrtime(true) - $started[$key]) / 1_000_000_000,
                        ];
                    }
                }

                do {
                    $status = curl_multi_exec($multi, $running);
                    if ($status !== CURLM_OK) {
                        throw new RuntimeException('Unable to execute concurrent feed requests.');
                    }

                    while (($info = curl_multi_info_read($multi)) !== false) {
                        $id = spl_object_id($info['handle']);
                        $entry = $active[$id];
                        $key = $entry['key'];
                        $handle = $entry['handle'];
                        $transfer = $entry['transfer'];

                        try {
                            if ($transfer->tooLarge) {
                                throw new RuntimeException('The remote response is too large.');
                            }
                            if ($info['result'] !== CURLE_OK) {
                                throw new RuntimeException('Unable to fetch feed: ' . curl_error($handle));
                            }

                            $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
                            if (in_array($statusCode, [301, 302, 303, 307, 308], true)) {
                                if ($redirects >= self::MAX_REDIRECTS) {
                                    throw new RuntimeException('Too many HTTP redirects.');
                                }
                                $url = curl_getinfo($handle, CURLINFO_REDIRECT_URL);
                                if (!is_string($url) || preg_match('~^https?://~i', $url) !== 1) {
                                    throw new RuntimeException('Invalid HTTP redirect URL.');
                                }

                                // Do not carry conditional headers to a different resource.
                                $headers = $requests[$key]['headers'];
                                if ($url !== $entry['url']) {
                                    unset($headers['If-None-Match'], $headers['If-Modified-Since']);
                                }
                                $next[$key] = ['url' => $url, 'headers' => $headers];
                                continue;
                            }

                            $response = new HttpResponse(
                                $statusCode,
                                $transfer->headers,
                                $transfer->body,
                                $entry['url'],
                            );
                        } catch (Throwable $exception) {
                            $response = $exception;
                        } finally {
                            curl_multi_remove_handle($multi, $handle);
                            unset($active[$id]);
                        }

                        $results[$key] = [
                            'response' => $response,
                            'seconds' => (hrtime(true) - $started[$key]) / 1_000_000_000,
                        ];
                    }

                    if ($running > 0 && curl_multi_select($multi, 1.0) === -1) {
                        usleep(1_000);
                    }
                } while ($running > 0);
            } catch (Throwable $exception) {
                foreach ($active as $entry) {
                    $key = $entry['key'];
                    $results[$key] = [
                        'response' => $exception,
                        'seconds' => (hrtime(true) - $started[$key]) / 1_000_000_000,
                    ];
                }
            } finally {
                foreach ($active as $entry) {
                    curl_multi_remove_handle($multi, $entry['handle']);
                }
            }

            $requests = $next;
        }

        return $results;
    }

    /**
     * @param array{url: string, resolve: list<string>} $connection
     * @param array<string, string> $headers
     */
    private function createHandle(array $connection, array $headers, FeedHttpTransfer $transfer): CurlHandle
    {
        $headers += ['User-Agent' => 'FeedNews/2.0 (+https://feednews.me)', 'Accept' => '*/*'];
        $headerLines = [];
        foreach ($headers as $name => $value) {
            if (preg_match('~[\r\n]~', $name . $value) === 1) {
                throw new RuntimeException('Invalid HTTP request header.');
            }
            $headerLines[] = $name . ': ' . $value;
        }

        $handle = curl_init($connection['url']);
        if ($handle === false) {
            throw new RuntimeException('Unable to initialize feed request.');
        }

        $configured = curl_setopt_array($handle, [
            CURLOPT_HTTPHEADER => $headerLines,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS_STR => 'HTTP,HTTPS',
            CURLOPT_PROXY => '',
            CURLOPT_RESOLVE => $connection['resolve'],
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_ENCODING => '',
            CURLOPT_HEADERFUNCTION => $transfer->receiveHeader(...),
            CURLOPT_WRITEFUNCTION => $transfer->receiveBody(...),
        ]);
        if (!$configured) {
            throw new RuntimeException('Unable to configure feed request.');
        }

        return $handle;
    }
}
