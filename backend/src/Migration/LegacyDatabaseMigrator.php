<?php

declare(strict_types=1);

namespace App\Migration;

use Doctrine\DBAL\Connection;
use RuntimeException;
use Symfony\Component\Uid\Uuid;
use Throwable;

final class LegacyDatabaseMigrator
{
    private const DEFAULT_FEED_COLOR = '#337dff';

    /** @var list<string> */
    private const REQUIRED_SOURCE_TABLES = [
        'users',
        'feed_categories',
        'feeds',
        'feed_items',
        'user_feeds',
        'user_feed_items',
        'checklist_items',
        'notes',
        'user_settings',
        'tags',
        'errors',
        'weather_forecasts',
    ];

    /** @var list<string> */
    private const REQUIRED_TARGET_TABLES = [
        'user',
        'feed_category',
        'feed',
        'feed_item',
        'feed_user',
        'feed_user_item',
        'user_checklist_item',
        'user_note',
        'user_setting',
        'feed_tag',
        'feed_error',
        'weather_forecast',
        'weather_forecast_day',
    ];

    /** @var list<string> */
    private const EMPTY_TARGET_TABLES = [
        'weather_forecast_day',
        'feed_user_item',
        'feed_item_content',
        'feed_user',
        'user_setting',
        'user_checklist_item',
        'user_note',
        'feed_tag',
        'feed_item',
        'feed_error',
        'feed',
        'feed_category',
        'weather_forecast',
        'user',
    ];

    public function __construct(private readonly Connection $targetConnection)
    {
    }

    /**
     * @return array<string, int>
     */
    public function migrate(Connection $sourceConnection): array
    {
        $this->assertExpectedSchema($sourceConnection, $this->targetConnection);
        $this->assertTargetIsEmpty();

        $counts = [];
        $this->targetConnection->beginTransaction();

        try {
            $counts['users'] = $this->migrateUsers($sourceConnection);
            $counts['feed_categories'] = $this->migrateFeedCategories($sourceConnection);
            $counts['feeds'] = $this->migrateFeeds($sourceConnection);
            $counts['feed_items'] = $this->migrateFeedItems($sourceConnection);
            $counts['feed_item_contents'] = $this->migrateFeedItemContents($sourceConnection);
            $counts['tags'] = $this->migrateTags($sourceConnection);

            [$counts['user_feeds'], $feedUserMap] = $this->migrateUserFeeds($sourceConnection);
            $counts['user_feed_items'] = $this->migrateUserFeedItems($sourceConnection, $feedUserMap);

            $counts['checklist_items'] = $this->migrateChecklistItems($sourceConnection);
            $counts['notes'] = $this->migrateNotes($sourceConnection);
            $counts['user_settings'] = $this->migrateUserSettings($sourceConnection);
            $counts['errors'] = $this->migrateErrors($sourceConnection);

            [$counts['weather_forecasts'], $counts['weather_forecast_days']] =
                $this->migrateWeatherForecasts($sourceConnection);

            $this->targetConnection->commit();
        } catch (Throwable $throwable) {
            $this->targetConnection->rollBack();
            throw $throwable;
        }

        return $counts;
    }

    /**
     * @return array<string, int>
     */
    public function sourceCounts(Connection $sourceConnection): array
    {
        $this->assertTablesExist($sourceConnection, self::REQUIRED_SOURCE_TABLES, 'legacy source');

        $counts = [];
        foreach (self::REQUIRED_SOURCE_TABLES as $table) {
            $counts[$table] = $this->countRows($sourceConnection, $table);
        }

        return $counts;
    }

    private function assertExpectedSchema(Connection $sourceConnection, Connection $targetConnection): void
    {
        $this->assertTablesExist($sourceConnection, self::REQUIRED_SOURCE_TABLES, 'legacy source');
        $this->assertTablesExist($targetConnection, self::REQUIRED_TARGET_TABLES, 'target');
    }

    /**
     * @param list<string> $tables
     */
    private function assertTablesExist(Connection $connection, array $tables, string $databaseLabel): void
    {
        $schemaManager = $connection->createSchemaManager();

        foreach ($tables as $table) {
            if (!$schemaManager->tablesExist([$table])) {
                throw new RuntimeException(sprintf(
                    'Expected table "%s" was not found in the %s database.',
                    $table,
                    $databaseLabel,
                ));
            }
        }
    }

    private function assertTargetIsEmpty(): void
    {
        foreach (self::EMPTY_TARGET_TABLES as $table) {
            if (!$this->targetConnection->createSchemaManager()->tablesExist([$table])) {
                continue;
            }

            $count = $this->countRows($this->targetConnection, $table);

            if ($count > 0) {
                throw new RuntimeException(sprintf(
                    'Target table "%s" is not empty (%d row(s)). Use a fresh target database.',
                    $table,
                    $count,
                ));
            }
        }
    }

    private function migrateUsers(Connection $source): int
    {
        $count = 0;

        foreach ($source->iterateAssociative('SELECT * FROM users ORDER BY id') as $row) {
            $this->targetConnection->insert('user', [
                'id' => $this->integer($row, 'id'),
                'uuid' => $this->uuid(),
                'email' => $this->string($row, 'email'),
                'name' => $this->string($row, 'name'),
                'hide_xframe_notice' => $this->booleanInt($row, 'hide_xframe_notice'),
                'enabled' => $this->booleanInt($row, 'enabled'),
                'is_admin' => $this->booleanInt($row, 'is_admin'),
                'avatar' => $this->nullableString($row, 'avatar'),
                'last_ip_address' => $this->nullableString($row, 'ip_address'),
                'last_user_agent' => $this->nullableString($row, 'user_agent'),
                'roles' => '[]',
                'password' => $this->string($row, 'password'),
                'last_login' => $this->nullableString($row, 'last_login'),
                'created_at' => $this->dateString($row, 'created_at'),
                'updated_at' => $this->dateString($row, 'updated_at'),
            ]);
            ++$count;
        }

        return $count;
    }

    private function migrateFeedCategories(Connection $source): int
    {
        $count = 0;

        foreach ($source->iterateAssociative('SELECT * FROM feed_categories ORDER BY id') as $row) {
            $this->targetConnection->insert('feed_category', [
                'id' => $this->integer($row, 'id'),
                'uuid' => $this->uuid(),
                'name' => $this->string($row, 'name'),
            ]);
            ++$count;
        }

        return $count;
    }

    private function migrateFeeds(Connection $source): int
    {
        $count = 0;
        $sql = <<<'SQL'
            SELECT
                f.*,
                (
                    SELECT uf.color
                    FROM user_feeds uf
                    WHERE uf.feed_id = f.id
                      AND uf.color IS NOT NULL
                      AND uf.color <> ''
                    ORDER BY uf.id DESC
                    LIMIT 1
                ) AS legacy_color
            FROM feeds f
            ORDER BY f.id
            SQL;

        foreach ($source->iterateAssociative($sql) as $row) {
            $this->targetConnection->insert('feed', [
                'id' => $this->integer($row, 'id'),
                'uuid' => $this->uuid(),
                'category_id' => $this->nullableInteger($row, 'category_id'),
                'name' => $this->string($row, 'name'),
                'color' => $this->normalizeColor($this->nullableString($row, 'legacy_color'), self::DEFAULT_FEED_COLOR),
                'url' => $this->string($row, 'url'),
                'created_at' => $this->dateString($row, 'created_at'),
                'updated_at' => $this->dateString($row, 'updated_at'),
            ]);
            ++$count;
        }

        return $count;
    }

    private function migrateFeedItems(Connection $source): int
    {
        $count = 0;

        foreach ($source->iterateAssociative('SELECT * FROM feed_items ORDER BY id') as $row) {
            $this->targetConnection->insert('feed_item', [
                'id' => $this->integer($row, 'id'),
                'uuid' => $this->uuid(),
                'feed_id' => $this->nullableInteger($row, 'feed_id'),
                'title' => $this->string($row, 'title'),
                'description' => $this->nullableString($row, 'description'),
                'guid' => $this->string($row, 'guid'),
                'url' => $this->string($row, 'url'),
                'created_at' => $this->dateString($row, 'created_at'),
                'updated_at' => $this->dateString($row, 'updated_at'),
            ]);
            ++$count;
        }

        return $count;
    }

    private function migrateFeedItemContents(Connection $source): int
    {
        if (!$source->createSchemaManager()->tablesExist(['feed_item_contents'])) {
            return 0;
        }

        $count = 0;
        foreach ($source->iterateAssociative('SELECT * FROM feed_item_contents ORDER BY id') as $row) {
            $feedItemId = $this->nullableInteger($row, 'feed_item_id');
            if ($feedItemId === null) {
                continue;
            }

            $this->targetConnection->insert('feed_item_content', [
                'id' => $this->integer($row, 'id'),
                'feed_item_id' => $feedItemId,
                'content' => $this->nullableString($row, 'content'),
            ]);
            ++$count;
        }

        return $count;
    }

    private function migrateTags(Connection $source): int
    {
        $count = 0;

        foreach ($source->iterateAssociative('SELECT * FROM tags ORDER BY id') as $row) {
            $this->targetConnection->insert('feed_tag', [
                'id' => $this->integer($row, 'id'),
                'uuid' => $this->uuid(),
                'user_id' => $this->integer($row, 'user_id'),
                'name' => $this->string($row, 'name'),
                'color' => $this->normalizeColor($this->nullableString($row, 'color'), self::DEFAULT_FEED_COLOR),
                'created_at' => $this->legacyDateOrNow($row, 'created_at'),
                'updated_at' => $this->legacyDateOrNow($row, 'updated_at'),
            ]);
            ++$count;
        }

        return $count;
    }

    /**
     * @return array{0: int, 1: array<int, int>}
     */
    private function migrateUserFeeds(Connection $source): array
    {
        $count = 0;
        $canonicalByPair = [];
        $sourceToTarget = [];

        foreach ($source->iterateAssociative('SELECT * FROM user_feeds ORDER BY id DESC') as $row) {
            $sourceId = $this->integer($row, 'id');
            $userId = $this->integer($row, 'user_id');
            $feedId = $this->integer($row, 'feed_id');
            $pair = $userId . ':' . $feedId;

            if (isset($canonicalByPair[$pair])) {
                $sourceToTarget[$sourceId] = $canonicalByPair[$pair];
                continue;
            }

            $this->targetConnection->insert('feed_user', [
                'id' => $sourceId,
                'user_id' => $userId,
                'feed_id' => $feedId,
                'icon' => $this->nullableString($row, 'icon'),
                'color' => $this->normalizeNullableColor($this->nullableString($row, 'color')),
                'auto_pin' => $this->booleanInt($row, 'auto_pin'),
                'created_at' => $this->dateString($row, 'created_at'),
                'updated_at' => $this->dateString($row, 'updated_at'),
            ]);

            $canonicalByPair[$pair] = $sourceId;
            $sourceToTarget[$sourceId] = $sourceId;
            ++$count;
        }

        return [$count, $sourceToTarget];
    }

    /**
     * @param array<int, int> $feedUserMap
     */
    private function migrateUserFeedItems(Connection $source, array $feedUserMap): int
    {
        $count = 0;
        $seen = [];

        foreach ($source->iterateAssociative('SELECT * FROM user_feed_items ORDER BY id DESC') as $row) {
            $userId = $this->integer($row, 'user_id');
            $feedItemId = $this->integer($row, 'feed_item_id');
            $pair = $userId . ':' . $feedItemId;

            if (isset($seen[$pair])) {
                continue;
            }
            $seen[$pair] = true;

            $sourceFeedUserId = $this->nullableInteger($row, 'user_feed_id');
            $targetFeedUserId = $sourceFeedUserId !== null
                ? ($feedUserMap[$sourceFeedUserId] ?? null)
                : null;

            $this->targetConnection->insert('feed_user_item', [
                'id' => $this->integer($row, 'id'),
                'uuid' => $this->uuid(),
                'user_id' => $userId,
                'feed_item_id' => $feedItemId,
                'user_feed_id' => $targetFeedUserId,
                'tag_id' => $this->nullableInteger($row, 'tag_id'),
                'opened_at' => $this->nullableString($row, 'opened_at'),
                'viewed' => $this->booleanInt($row, 'viewed'),
                'pinned' => $this->booleanInt($row, 'pinned'),
                'created_at' => $this->dateString($row, 'created_at'),
                'updated_at' => $this->dateString($row, 'updated_at'),
            ]);
            ++$count;
        }

        return $count;
    }

    private function migrateChecklistItems(Connection $source): int
    {
        $count = 0;

        foreach ($source->iterateAssociative('SELECT * FROM checklist_items ORDER BY id') as $row) {
            $this->targetConnection->insert('user_checklist_item', [
                'id' => $this->integer($row, 'id'),
                'uuid' => $this->uuid(),
                'user_id' => $this->integer($row, 'user_id'),
                'item' => $this->string($row, 'item'),
                'checked' => $this->booleanInt($row, 'checked'),
                'created_at' => $this->dateString($row, 'created_at'),
                'updated_at' => $this->dateString($row, 'updated_at'),
            ]);
            ++$count;
        }

        return $count;
    }

    private function migrateNotes(Connection $source): int
    {
        $count = 0;

        foreach ($source->iterateAssociative('SELECT * FROM notes ORDER BY id') as $row) {
            $name = $this->nullableString($row, 'name');
            $this->targetConnection->insert('user_note', [
                'id' => $this->integer($row, 'id'),
                'uuid' => $this->uuid(),
                'user_id' => $this->integer($row, 'user_id'),
                'name' => $name ?? '',
                'position' => $this->integer($row, 'position'),
                'content' => $this->nullableString($row, 'content'),
                'created_at' => $this->dateString($row, 'created_at'),
                'updated_at' => $this->dateString($row, 'updated_at'),
            ]);
            ++$count;
        }

        return $count;
    }

    private function migrateUserSettings(Connection $source): int
    {
        $count = 0;
        $seen = [];
        $rows = $source->iterateAssociative('SELECT * FROM user_settings ORDER BY id DESC');

        foreach ($rows as $row) {
            $userId = $this->integer($row, 'user_id');
            $setting = $this->string($row, 'setting');
            $pair = $userId . ':' . $setting;

            if (isset($seen[$pair])) {
                continue;
            }
            $seen[$pair] = true;

            $this->targetConnection->insert('user_setting', [
                'id' => $this->integer($row, 'id'),
                'uuid' => $this->uuid(),
                'user_id' => $userId,
                'setting' => $setting,
                'value' => $this->nullableString($row, 'value'),
                'created_at' => $this->dateString($row, 'created_at'),
                'updated_at' => $this->dateString($row, 'updated_at'),
            ]);
            ++$count;
        }

        return $count;
    }

    private function migrateErrors(Connection $source): int
    {
        $count = 0;

        foreach ($source->iterateAssociative('SELECT * FROM errors ORDER BY id') as $row) {
            $this->targetConnection->insert('feed_error', [
                'id' => $this->integer($row, 'id'),
                'type' => $this->errorType($row['type'] ?? null),
                'user_id' => $this->nullableInteger($row, 'user_id'),
                'feed_id' => $this->nullableInteger($row, 'feed_id'),
                'exception' => $this->string($row, 'exception'),
                'occurrences' => $this->integer($row, 'occurrences'),
                'created_at' => $this->dateString($row, 'created_at'),
                'updated_at' => $this->dateString($row, 'updated_at'),
            ]);
            ++$count;
        }

        return $count;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function migrateWeatherForecasts(Connection $source): array
    {
        $forecastCount = 0;
        $dayCount = 0;

        foreach ($source->iterateAssociative('SELECT * FROM weather_forecasts ORDER BY id') as $row) {
            $forecastId = $this->integer($row, 'id');
            $currentWeather = $this->weatherType($this->nullableString($row, 'current_weather'));

            $this->targetConnection->insert('weather_forecast', [
                'id' => $forecastId,
                'location' => $this->string($row, 'location'),
                'current_temp' => $this->float($row, 'current_temp'),
                'current_weather' => $currentWeather,
                'created_at' => $this->dateString($row, 'created_at'),
                'updated_at' => $this->dateString($row, 'updated_at'),
            ]);
            ++$forecastCount;

            for ($dayOffset = 0; $dayOffset <= 5; ++$dayOffset) {
                $maxKey = $dayOffset === 0 ? 'temp_today_max' : sprintf('temp_in_%d_days_max', $dayOffset);
                $minKey = $dayOffset === 0 ? 'temp_today_min' : sprintf('temp_in_%d_days_min', $dayOffset);
                $weather = $dayOffset === 0
                    ? $currentWeather
                    : $this->weatherType($this->nullableString($row, sprintf('weather_in_%d_days', $dayOffset)));

                $this->targetConnection->insert('weather_forecast_day', [
                    'forecast_id' => $forecastId,
                    'day_offset' => $dayOffset,
                    'temp_max' => $this->float($row, $maxKey),
                    'temp_min' => $this->float($row, $minKey),
                    'weather' => $weather,
                ]);
                ++$dayCount;
            }
        }

        return [$forecastCount, $dayCount];
    }

    private function uuid(): string
    {
        return Uuid::v4()->toBinary();
    }

    /**
     * @param array<string, mixed> $row
     */
    private function string(array $row, string $key): string
    {
        $value = $row[$key] ?? null;
        if (!is_scalar($value)) {
            throw new RuntimeException(sprintf('Expected "%s" to contain a scalar value.', $key));
        }

        return (string) $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function nullableString(array $row, string $key): ?string
    {
        $value = $row[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_scalar($value)) {
            throw new RuntimeException(sprintf('Expected "%s" to contain a scalar or null value.', $key));
        }

        return (string) $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function integer(array $row, string $key): int
    {
        $value = $row[$key] ?? null;
        if (!is_int($value) && !is_string($value) && !is_float($value)) {
            throw new RuntimeException(sprintf('Expected "%s" to contain an integer-compatible value.', $key));
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function nullableInteger(array $row, string $key): ?int
    {
        $value = $row[$key] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_int($value) && !is_string($value) && !is_float($value)) {
            throw new RuntimeException(sprintf('Expected "%s" to contain an integer-compatible value or null.', $key));
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function float(array $row, string $key): float
    {
        $value = $row[$key] ?? null;
        if (!is_int($value) && !is_string($value) && !is_float($value)) {
            throw new RuntimeException(sprintf('Expected "%s" to contain a numeric value.', $key));
        }
        if (!is_numeric((string) $value)) {
            throw new RuntimeException(sprintf('Expected "%s" to contain a numeric value.', $key));
        }

        return (float) $value;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function booleanInt(array $row, string $key): int
    {
        $value = $row[$key] ?? false;

        return filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function dateString(array $row, string $key): string
    {
        $value = $this->nullableString($row, $key);
        if ($value === null || trim($value) === '') {
            return date('Y-m-d H:i:s');
        }

        return $value;
    }

    /**
     * Tags in the legacy schema had no timestamps. This also keeps the method
     * tolerant if a production database contains locally-added timestamp columns.
     *
     * @param array<string, mixed> $row
     */
    private function legacyDateOrNow(array $row, string $key): string
    {
        if (!array_key_exists($key, $row)) {
            return date('Y-m-d H:i:s');
        }

        return $this->dateString($row, $key);
    }

    private function normalizeNullableColor(?string $color): ?string
    {
        if ($color === null || trim($color) === '') {
            return null;
        }

        return $this->normalizeColor($color, self::DEFAULT_FEED_COLOR);
    }

    private function normalizeColor(?string $color, string $fallback): string
    {
        if ($color === null || trim($color) === '') {
            return $fallback;
        }

        $normalized = trim($color);
        if (preg_match('/^[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $normalized) === 1) {
            return '#' . strtolower($normalized);
        }
        if (preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $normalized) === 1) {
            return strtolower($normalized);
        }

        return mb_substr($normalized, 0, 191);
    }

    private function errorType(mixed $value): string
    {
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return match ((int) $value) {
                1 => 'message',
                2 => 'warning',
                3 => 'error',
                4 => 'fatal',
                default => 'error',
            };
        }

        if (is_string($value) && in_array($value, ['message', 'warning', 'error', 'fatal'], true)) {
            return $value;
        }

        return 'error';
    }

    private function weatherType(?string $value): string
    {
        if ($value === null) {
            return 'unknown';
        }

        $normalized = strtolower(trim($value));
        if (
            in_array($normalized, [
            'clouds',
            'rain',
            'partly_cloud',
            'sun',
            'clear',
            'snow',
            'thunderstorm',
            'unknown',
            ], true)
        ) {
            return $normalized;
        }

        return 'unknown';
    }

    private function countRows(Connection $connection, string $table): int
    {
        $value = $connection->fetchOne(
            sprintf('SELECT COUNT(*) FROM `%s`', $table),
        );

        if (!is_int($value) && !is_string($value)) {
            throw new RuntimeException(sprintf(
                'Expected row count for table "%s" to be an integer-compatible value.',
                $table,
            ));
        }

        if (is_string($value) && ctype_digit($value) === false) {
            throw new RuntimeException(sprintf(
                'Expected row count for table "%s" to be numeric.',
                $table,
            ));
        }

        return (int) $value;
    }
}
