<?php

use Illuminate\Foundation\Vite;
use Illuminate\Support\Facades\File;

test('font preload hrefs are root relative so https pages do not block them', function () {
    $vite = app(Vite::class);
    $directory = 'vite-fonts-test';
    $path = public_path($directory);
    $reflection = new ReflectionClass($vite);
    $originalDirectory = $reflection->getProperty('buildDirectory')->getValue($vite);
    $originalHotFile = $reflection->getProperty('hotFile')->getValue($vite);

    File::ensureDirectoryExists($path);
    File::put($path.'/fonts-manifest.json', json_encode([
        'version' => 1,
        'families' => [
            'instrument-sans' => [],
        ],
        'style' => [
            'inline' => <<<'CSS'
@font-face {
  font-family: "Instrument Sans";
  src: url("/build/assets/instrument-sans.woff2") format("woff2");
}
CSS,
        ],
        'preloads' => [
            [
                'alias' => 'instrument-sans',
                'file' => 'assets/instrument-sans.woff2',
                'as' => 'font',
                'type' => 'font/woff2',
                'crossorigin' => 'anonymous',
            ],
        ],
    ], JSON_THROW_ON_ERROR));

    try {
        $html = $vite
            ->useBuildDirectory($directory)
            ->useHotFile($path.'/missing-hot')
            ->fonts()
            ->toHtml();

        expect($html)
            ->toContain('rel="preload"')
            ->toContain('as="font"')
            ->toContain('href="/'.$directory.'/assets/instrument-sans.woff2"')
            ->not->toContain('http://')
            ->not->toContain('https://');
    } finally {
        $vite->useBuildDirectory($originalDirectory);
        $vite->useHotFile($originalHotFile ?? public_path('/hot'));
        $vite->flush();
        File::deleteDirectory($path);
    }
});
