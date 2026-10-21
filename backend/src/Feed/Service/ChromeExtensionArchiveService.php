<?php

declare(strict_types=1);

namespace App\Feed\Service;

use JsonException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use ZipArchive;

final readonly class ChromeExtensionArchiveService
{
    private const string EXTENSION_NAME = 'Feednews Save';
    private const string EXTENSION_VERSION = '1.0.0';

    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        private string $projectDirectory,
    ) {
    }

    /** @throws JsonException */
    public function create(string $origin): string
    {
        if (class_exists(ZipArchive::class) === false) {
            throw new RuntimeException('The PHP ZIP extension is required to download the Chrome extension.');
        }

        $archivePath = tempnam(sys_get_temp_dir(), 'feednews-extension-');
        if ($archivePath === false) {
            throw new RuntimeException('Could not create a temporary extension archive.');
        }

        $archive = new ZipArchive();
        $archiveIsOpen = false;

        try {
            $openResult = $archive->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($openResult !== true) {
                throw new RuntimeException('Could not open the extension archive.');
            }
            $archiveIsOpen = true;

            $this->addJson($archive, 'manifest.json', $this->manifest($origin));
            $this->addJson($archive, 'config.json', ['feednewsOrigin' => rtrim($origin, '/')]);
            $this->addResource($archive, 'popup.html');
            $this->addResource($archive, 'popup.css');
            $this->addResource($archive, 'popup.js');
            $this->addIcon($archive);

            $closed = $archive->close();
            $archiveIsOpen = false;
            if ($closed === false) {
                throw new RuntimeException('Could not finish the extension archive.');
            }

            $contents = file_get_contents($archivePath);
            if ($contents === false) {
                throw new RuntimeException('Could not read the extension archive.');
            }

            return $contents;
        } finally {
            if ($archiveIsOpen) {
                $archive->close();
            }
            if (is_file($archivePath)) {
                unlink($archivePath);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function manifest(string $origin): array
    {
        $manifest = [
            'manifest_version' => 3,
            'name' => self::EXTENSION_NAME,
            'version' => self::EXTENSION_VERSION,
            'description' => 'Save the current page directly to Feednews.',
            'permissions' => ['activeTab', 'scripting'],
            'host_permissions' => [rtrim($origin, '/') . '/*'],
            'action' => [
                'default_title' => 'Save to Feednews',
                'default_popup' => 'popup.html',
            ],
        ];

        if (is_file($this->iconPath())) {
            $manifest['icons'] = [
                '16' => 'icon.png',
                '32' => 'icon.png',
                '48' => 'icon.png',
                '128' => 'icon.png',
            ];
            $manifest['action']['default_icon'] = 'icon.png';
        }

        return $manifest;
    }

    /**
     * @param array<string, mixed> $data
     *
     * @throws JsonException
     */
    private function addJson(ZipArchive $archive, string $filename, array $data): void
    {
        $contents = json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );

        if ($archive->addFromString($filename, $contents . "\n") === false) {
            throw new RuntimeException(sprintf('Could not add %s to the extension archive.', $filename));
        }
    }

    private function addResource(ZipArchive $archive, string $filename): void
    {
        $path = $this->projectDirectory . '/resources/chrome-extension/' . $filename;
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Could not read extension resource %s.', $filename));
        }

        if ($archive->addFromString($filename, $contents) === false) {
            throw new RuntimeException(sprintf('Could not add %s to the extension archive.', $filename));
        }
    }

    private function addIcon(ZipArchive $archive): void
    {
        $path = $this->iconPath();
        if (is_file($path) === false) {
            return;
        }

        $contents = file_get_contents($path);
        if ($contents === false || $archive->addFromString('icon.png', $contents) === false) {
            throw new RuntimeException('Could not add the Feednews icon to the extension archive.');
        }
    }

    private function iconPath(): string
    {
        return dirname($this->projectDirectory) . '/frontend/public/icons/icon-192.png';
    }
}
