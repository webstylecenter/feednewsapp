<?php

declare(strict_types=1);

namespace App\Tests\Feed;

use App\Feed\Service\ChromeExtensionArchiveService;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZipArchive;

final class ChromeExtensionArchiveServiceTest extends TestCase
{
    /** @throws JsonException */
    public function testArchiveContainsConfiguredManifestAndPopup(): void
    {
        $service = new ChromeExtensionArchiveService(dirname(__DIR__, 2));
        $contents = $service->create('https://feednews.example');
        $archivePath = tempnam(sys_get_temp_dir(), 'feednews-extension-test-');
        if ($archivePath === false) {
            throw new RuntimeException('Could not create test archive file.');
        }

        try {
            self::assertNotFalse(file_put_contents($archivePath, $contents));

            $archive = new ZipArchive();
            self::assertTrue($archive->open($archivePath) === true);

            $manifestContents = $archive->getFromName('manifest.json');
            $configContents = $archive->getFromName('config.json');
            self::assertIsString($manifestContents);
            self::assertIsString($configContents);

            /** @var array<string, mixed> $manifest */
            $manifest = json_decode($manifestContents, true, 512, JSON_THROW_ON_ERROR);
            /** @var array<string, mixed> $config */
            $config = json_decode($configContents, true, 512, JSON_THROW_ON_ERROR);

            self::assertSame(3, $manifest['manifest_version'] ?? null);
            self::assertSame(['activeTab', 'scripting'], $manifest['permissions'] ?? null);
            self::assertSame(['https://feednews.example/*'], $manifest['host_permissions'] ?? null);
            self::assertSame('https://feednews.example', $config['feednewsOrigin'] ?? null);
            self::assertNotFalse($archive->getFromName('popup.html'));
            self::assertNotFalse($archive->getFromName('popup.css'));
            self::assertNotFalse($archive->getFromName('popup.js'));
            self::assertTrue($archive->close());
        } finally {
            if (is_file($archivePath)) {
                unlink($archivePath);
            }
        }
    }
}
