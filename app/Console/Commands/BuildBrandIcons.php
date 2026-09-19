<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Rasterises the brand SVGs into the PNG sizes iOS, Android and older browsers
 * need, because none of them will take an SVG icon.
 *
 * It drives the headless browser the test suite already installs rather than
 * adding an image toolchain: the browser is the one renderer guaranteed to
 * agree with what a visitor sees. The PNGs are committed, so a deployment never
 * needs the browser — only whoever changes the logo does.
 */
class BuildBrandIcons extends Command
{
    protected $signature = 'brand:icons {--node=node : Node binary to run the renderer with}';

    protected $description = 'Regenerate the PNG app icons from the brand SVGs';

    /** Each PNG, and which drawing it comes from. */
    private const ICONS = [
        // Small sizes come from the favicon, which is drawn for them.
        ['favicon-16.png', 'favicon.svg', 16],
        ['favicon-32.png', 'favicon.svg', 32],
        ['favicon-48.png', 'favicon.svg', 48],
        // Home-screen icons are large enough for the full mark.
        ['apple-touch-icon.png', 'logo-mark.svg', 180],
        ['icon-192.png', 'logo-mark.svg', 192],
        ['icon-512.png', 'logo-mark.svg', 512],
    ];

    public function handle(): int
    {
        $brand = public_path('assets/brand');

        foreach (self::ICONS as [$out, $source, $size]) {
            if (! is_file($brand.'/'.$source)) {
                $this->error("Missing source artwork: {$source}");

                return self::FAILURE;
            }
        }

        $script = $this->renderer($brand);
        $path = storage_path('app/brand-icons.mjs');
        file_put_contents($path, $script);

        $process = new Process(
            [$this->option('node'), $path],
            base_path(),
            ['NODE_PATH' => $this->modulePaths()],
            timeout: 180,
        );
        $process->run(fn ($type, $buffer) => $this->output->write($buffer));

        @unlink($path);

        if (! $process->isSuccessful()) {
            $this->error('Rendering failed. Playwright and a Chromium build are needed to regenerate icons.');

            return self::FAILURE;
        }

        foreach (self::ICONS as [$out, , $size]) {
            $this->line(sprintf('  %-22s %4dpx  %s', $out, $size, $this->weight($brand.'/'.$out)));
        }

        $this->info('Brand icons rebuilt. Commit them — deployments do not run this.');

        return self::SUCCESS;
    }

    /**
     * Where to look for Playwright. A project install is the normal case; the
     * global root covers a machine where it was installed with `npm i -g`,
     * which is how the CI images carry it.
     */
    private function modulePaths(): string
    {
        $paths = [base_path('node_modules')];

        $global = new Process(['npm', 'root', '-g'], timeout: 20);
        $global->run();

        if ($global->isSuccessful() && ($root = trim($global->getOutput())) !== '') {
            $paths[] = $root;
        }

        return implode(PATH_SEPARATOR, $paths);
    }

    private function weight(string $file): string
    {
        return is_file($file) ? number_format(filesize($file) / 1024, 1).' KB' : 'missing';
    }

    private function renderer(string $brand): string
    {
        $icons = json_encode(self::ICONS);
        $dir = json_encode($brand);
        $executable = json_encode(env('PLAYWRIGHT_CHROMIUM', '/opt/pw-browsers/chromium'));

        return <<<JS
        import { readFileSync } from 'node:fs';
        import { createRequire } from 'node:module';

        // require(), not import: ESM resolution ignores NODE_PATH, and this has
        // to find Playwright whether it is installed in the project or globally.
        const { chromium } = createRequire(import.meta.url)('playwright');

        const icons = {$icons};
        const dir = {$dir};
        const executablePath = {$executable};

        const browser = await chromium.launch(
            executablePath && readFileSyncSafe(executablePath) ? { executablePath } : {},
        );

        function readFileSyncSafe(p) {
            try { readFileSync(p); return true; } catch { return false; }
        }

        for (const [out, source, size] of icons) {
            const svg = readFileSync(dir + '/' + source, 'utf8');
            const page = await browser.newPage({
                viewport: { width: size, height: size },
                deviceScaleFactor: 1,
            });
            // A transparent page: the tile inside the artwork supplies the colour,
            // so a mark with rounded corners keeps them instead of gaining a square.
            await page.setContent(
                '<!doctype html><style>html,body{margin:0;background:transparent}'
                + 'svg{display:block;width:' + size + 'px;height:' + size + 'px}</style>' + svg,
            );
            await page.screenshot({ path: dir + '/' + out, omitBackground: true });
            await page.close();
        }

        await browser.close();
        JS;
    }
}
