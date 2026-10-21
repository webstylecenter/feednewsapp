<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Feed\Service\ChromeExtensionArchiveService;
use JsonException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final readonly class ChromeExtensionController
{
    public function __construct(private ChromeExtensionArchiveService $archiveService)
    {
    }

    /** @throws JsonException */
    #[Route(
        '/api/settings/chrome-extension',
        name: 'api_settings_chrome_extension',
        methods: ['GET'],
    )]
    public function __invoke(Request $request): Response
    {
        $archive = $this->archiveService->create($request->getSchemeAndHttpHost());

        return new Response($archive, Response::HTTP_OK, [
            'Content-Type' => 'application/zip',
            'Content-Length' => (string) strlen($archive),
            'Content-Disposition' => HeaderUtils::makeDisposition(
                ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                'feednews-chrome-extension.zip',
            ),
            'Cache-Control' => 'private, no-store',
        ]);
    }
}
