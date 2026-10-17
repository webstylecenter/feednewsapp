<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class PublicController extends AbstractController
{
    public function __construct(
        #[Autowire('%kernel.project_dir%')] private readonly string $projectDir,
    ) {
    }

    #[Route('/android-import/{encodedUrl}', name: 'android_import', requirements: ['encodedUrl' => '.+'], methods: ['GET'])]
    public function androidImport(string $encodedUrl): RedirectResponse
    {
        $decoded = base64_decode(strtr($encodedUrl, '-_', '+/'), true);
        if ($decoded === false || filter_var($decoded, FILTER_VALIDATE_URL) === false) {
            return $this->redirect('/?importError=1');
        }

        return $this->redirect('/?import=' . rawurlencode($decoded) . '&save=1');
    }

    #[Route('/{path}', name: 'spa', requirements: ['path' => '^(?!api(?:/|$)|android-import(?:/|$)|build(?:/|$)|images(?:/|$)|downloads(?:/|$)|_).*$'], defaults: ['path' => ''], methods: ['GET'])]
    public function spa(string $path): Response
    {
        unset($path);
        $file = $this->projectDir . '/public/index.html';
        if (!is_file($file)) {
            return new Response('Frontend has not been built yet.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new BinaryFileResponse($file);
    }
}
