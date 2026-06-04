<?php

namespace App\Console\Commands;

use App\Services\StaticSiteGenerator;
use Illuminate\Console\Command;

class GenerateStaticSiteCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'static:generate
                            {--clean : Wipe generated subtrees before writing (keeps about/, icon/, overrides/)}
                            {--with-wiki : Enrich person pages with Wikipedia previews (network, slower)}
                            {--with-images : Download remote image URLs (local images are always copied)}
                            {--path= : Override the docs output directory (default: static/docs)}';

    /**
     * @var string
     */
    protected $description = 'Generate the static-site Markdown tree (static/docs) from the database';

    public function handle(StaticSiteGenerator $generator): int
    {
        $docsRoot = $this->option('path') ?: base_path('static/docs');

        $this->info("Generating Markdown into [{$docsRoot}] ...");
        $generator->setLogger(fn (string $msg) => $this->line("  {$msg}"));

        $result = $generator->generate($docsRoot, [
            'clean' => (bool) $this->option('clean'),
            'withWiki' => (bool) $this->option('with-wiki'),
            'withImages' => (bool) $this->option('with-images'),
        ]);

        $this->newLine();
        $this->info("Wrote {$result['written']} Markdown files, copied {$result['images']} images.");
        if (! empty($result['skipped'])) {
            $this->warn('Skipped (no data — Phase B, needs new schema):');
            foreach ($result['skipped'] as $item) {
                $this->line("  - {$item}");
            }
        }
        $this->info('Done. Build the site yourself with: mkdocs build -f static/mkdocs.yml');

        return Command::SUCCESS;
    }
}
