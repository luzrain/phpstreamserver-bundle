<?php

declare(strict_types=1);

namespace Luzrain\PHPStreamServerBundle\Test\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class RequestTestController extends AbstractController
{
    #[Route('/request_test', name: 'app_request_test')]
    public function __invoke(Request $request): Response
    {
        $files = $request->files->all();
        \array_walk_recursive($files, $this->normalizeFiles(...));

        return $this->json([
            'headers' => $request->headers->all(),
            'get' => $request->query->all(),
            'post' => $request->request->all(),
            'files' => $files,
            'cookies' => $request->cookies->all(),
            'raw_request' => $request->getContent(),
        ]);
    }

    private function normalizeFiles(UploadedFile &$file): void
    {
        $file = [
            'name' => $file->getClientOriginalName(),
            'filename' => $file->getClientOriginalName(),
            'extension' => $file->getClientOriginalExtension(),
            'sha1' => \hash_file('sha1', $file->getRealPath()),
            'size' => $file->getSize(),
        ];
    }
}
