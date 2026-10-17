<?php

declare(strict_types=1);

namespace App\User\Security;

use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class LoginThrottle
{
    private const int MAX_ATTEMPTS = 5;
    private const int WINDOW_SECONDS = 600;

    public function __construct(private CacheItemPoolInterface $cache)
    {
    }

    public function retryAfter(Request $request): ?int
    {
        $state = $this->state($request);
        if ($state === null || $state['attempts'] < self::MAX_ATTEMPTS) {
            return null;
        }

        $remaining = ($state['startedAt'] + self::WINDOW_SECONDS) - time();
        if ($remaining <= 0) {
            $this->clear($request);
            return null;
        }

        return $remaining;
    }

    public function recordFailure(Request $request): void
    {
        $item = $this->cache->getItem($this->key($request));
        $state = $this->normalizeState($item->isHit() ? $item->get() : null);
        $now = time();
        if ($state === null || $state['startedAt'] + self::WINDOW_SECONDS <= $now) {
            $state = ['attempts' => 0, 'startedAt' => $now];
        }

        ++$state['attempts'];
        $item->set($state);
        $item->expiresAfter(self::WINDOW_SECONDS);
        $this->cache->save($item);
    }

    public function clear(Request $request): void
    {
        $this->cache->deleteItem($this->key($request));
    }

    /** @return array{attempts: int, startedAt: int}|null */
    private function state(Request $request): ?array
    {
        $item = $this->cache->getItem($this->key($request));
        return $item->isHit() ? $this->normalizeState($item->get()) : null;
    }

    /** @return array{attempts: int, startedAt: int}|null */
    private function normalizeState(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $attempts = $value['attempts'] ?? null;
        $startedAt = $value['startedAt'] ?? null;
        if (!is_int($attempts) || !is_int($startedAt)) {
            return null;
        }

        return ['attempts' => $attempts, 'startedAt' => $startedAt];
    }

    private function key(Request $request): string
    {
        $email = '';
        $content = $request->getContent();
        if ($content !== '') {
            $payload = json_decode($content, true);
            if (is_array($payload) && is_string($payload['email'] ?? null)) {
                $email = strtolower(trim($payload['email']));
            }
        }

        return 'feednews_login_' . hash('sha256', ($request->getClientIp() ?? 'unknown') . '|' . $email);
    }
}
