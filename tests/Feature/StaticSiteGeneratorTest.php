<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Generation;
use App\Models\Person;
use App\Models\Quote;
use App\Services\StaticSiteGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class StaticSiteGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private string $docs;

    protected function setUp(): void
    {
        parent::setUp();

        $this->docs = storage_path('framework/testing/static-docs-'.getmypid());
        File::deleteDirectory($this->docs);

        // Three generations (oldest first). "Generation Y" must canonicalise to "millennials".
        Generation::create(['title' => 'Baby Boomers', 'slug' => 'boomers', 'first_year' => 1946, 'last_year' => 1964, 'alternatives' => 'Boomers']);
        Generation::create(['title' => 'Generation X', 'slug' => 'x', 'first_year' => 1965, 'last_year' => 1980, 'alternatives' => 'Gen X']);
        Generation::create(['title' => 'Generation Y', 'slug' => 'y', 'first_year' => 1981, 'last_year' => 1996, 'alternatives' => 'millennials']);

        // People — note the column is `name`, NOT `title` (the headline schema gotcha).
        Person::create(['name' => 'Barack Obama', 'born_at' => '1961-08-04', 'category' => 'Politics', 'country' => 'USA']);
        Person::create(['name' => 'Will Smith', 'born_at' => '1968-09-25', 'category' => 'Actor', 'country' => 'USA']);
        Person::create(['name' => 'Serena Williams', 'born_at' => '1981-09-26', 'category' => 'Sports', 'country' => 'USA']);

        $boomers = Generation::whereTitle('Baby Boomers')->first();
        Quote::create(['generation_id' => $boomers->id, 'description' => 'The times they are a-changin.', 'author' => 'Bob Dylan']);

        Event::create(['title' => 'Moon Landing', 'category' => 'science', 'happened_at' => '1969-07-20', 'description' => 'Apollo 11 lands on the Moon.']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->docs);
        parent::tearDown();
    }

    private function generate(array $opts = []): array
    {
        return app(StaticSiteGenerator::class)->generate($this->docs, array_merge([
            'runDate' => '2026-06-04',
        ], $opts));
    }

    public function test_generates_core_tree_and_canonical_slugs(): void
    {
        $this->generate();

        $this->assertFileExists($this->docs.'/index.md');
        $this->assertFileExists($this->docs.'/generations/index.md');
        $this->assertFileExists($this->docs.'/generations/baby-boomers/index.md');
        // "Generation Y" canonicalises to /millennials/, not /generation-y/.
        $this->assertFileExists($this->docs.'/generations/millennials/index.md');
        $this->assertFileDoesNotExist($this->docs.'/generations/generation-y/index.md');
        $this->assertFileExists($this->docs.'/people/index.md');
        $this->assertFileExists($this->docs.'/events/index.md');
        $this->assertFileExists($this->docs.'/compare/index.md');
        $this->assertFileExists($this->docs.'/born-in/index.md');
        $this->assertFileExists($this->docs.'/what-generation-am-i/index.md');
        $this->assertFileExists($this->docs.'/robots.txt');
    }

    public function test_compare_page_count_is_n_choose_2(): void
    {
        $this->generate();

        $pairs = glob($this->docs.'/compare/*-vs-*.md');
        // 3 generations -> C(3,2) = 3 comparison pages.
        $this->assertCount(3, $pairs);
        $this->assertFileExists($this->docs.'/compare/baby-boomers-vs-generation-x.md');
    }

    public function test_person_page_uses_name_column(): void
    {
        $this->generate();

        $page = File::get($this->docs.'/people/will-smith.md');
        $this->assertStringContainsString('# Will Smith', $page);
        // Serena (born 1981) is anchored to Generation Y -> millennials URL.
        $serena = File::get($this->docs.'/people/serena-williams.md');
        $this->assertStringContainsString('millennials/index.md', $serena);
    }

    public function test_every_page_has_description_faq_and_last_updated(): void
    {
        $this->generate();

        $gen = File::get($this->docs.'/generations/generation-x/index.md');
        $this->assertStringContainsString('description:', $gen);
        $this->assertStringContainsString('Frequently asked questions', $gen);
        $this->assertStringContainsString('1965 and 1980', $gen);
        $this->assertStringContainsString('Last updated: 2026-06-04', $gen);
    }

    public function test_all_internal_links_resolve(): void
    {
        $this->generate();

        $broken = [];
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->docs, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($rii as $file) {
            if ($file->getExtension() !== 'md') {
                continue;
            }
            $dir = $file->getPath();
            preg_match_all('/\]\(([^)]+)\)/', File::get($file->getPathname()), $m);
            foreach ($m[1] as $target) {
                $target = trim($target);
                if ($target === '' || preg_match('~^(https?://|#|mailto:)~', $target)) {
                    continue;
                }
                $target = preg_replace('/#.*$/', '', $target);
                if ($target === '') {
                    continue;
                }
                if (! file_exists($dir.'/'.$target)) {
                    $broken[] = $file->getFilename().' -> '.$target;
                }
            }
        }

        $this->assertSame([], $broken, 'Broken internal links found');
    }

    public function test_empty_generation_hides_people_sections(): void
    {
        // 1997–2012 has no people in the fixture, so this generation is empty.
        Generation::create(['title' => 'Generation Z', 'slug' => 'z', 'first_year' => 1997, 'last_year' => 2012]);
        $this->generate();

        $this->assertFileExists($this->docs.'/generations/generation-z/index.md');
        // No "Best X" listicle page when there are no people.
        $this->assertFileDoesNotExist($this->docs.'/generations/generation-z/famous-people.md');

        $index = File::get($this->docs.'/generations/generation-z/index.md');
        $this->assertStringNotContainsString('Notable people on record', $index);
        $this->assertStringNotContainsString('famous-people.md', $index);
    }

    public function test_output_is_deterministic(): void
    {
        $this->generate();
        $first = $this->hashTree();

        $this->generate();
        $second = $this->hashTree();

        $this->assertSame($first, $second, 'Generation is not byte-stable across runs');
    }

    public function test_phase_b_paths_are_absent_and_reported(): void
    {
        $result = $this->generate();

        $this->assertDirectoryDoesNotExist($this->docs.'/blog');
        $this->assertDirectoryDoesNotExist($this->docs.'/bands');
        $this->assertDirectoryDoesNotExist($this->docs.'/movies');
        $this->assertDirectoryDoesNotExist($this->docs.'/tv-shows');
        $this->assertNotEmpty($result['skipped']);
    }

    private function hashTree(): string
    {
        $hashes = [];
        $rii = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->docs, \FilesystemIterator::SKIP_DOTS)
        );
        foreach ($rii as $file) {
            if ($file->getExtension() === 'md') {
                $rel = str_replace($this->docs.'/', '', $file->getPathname());
                $hashes[$rel] = md5_file($file->getPathname());
            }
        }
        ksort($hashes);

        return md5(json_encode($hashes));
    }
}
