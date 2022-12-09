<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class GenerationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->insert('Lost Generation', 'lost', '', 1883, 1900);
        $this->insert('Interbellum Generation', 'interbellum', '', 1901, 1913);
        $this->insert('Greatest Generation', 'greatest', '', 1910, 1924);
        $this->insert('Silent Generation', 'silent', '', 1925, 1945);
        $this->insert('Baby Boomers', 'boomers', 'Boomers', 1946, 1964);
        $this->insert('Generation X', 'x', 'baby bust', 1965, 1980);
        $this->insert('Generation Y', 'y', 'millenials, Gen Next', 1981, 1996);
        $this->insert('Generation Z', 'z', 'Gen-Z, iGen', 1997, 2012);
        $this->insert('Generation Alpha', 'alpha', '', 2013, 2024);
    }

    private function insert(string $title, string $slug, string $alternatives, int $first_year, int $last_year)
    {
        $image = "";
        if (file_exists(__DIR__ . "/../../public/images/generation.$slug.jpg")) {
            $image = "/images/generation.$slug.jpg";
        }
        DB::table('generations')->insert([
            'title' => $title,
            'slug' => $slug,
            'alternatives' => $alternatives,
            'first_year' => $first_year,
            'last_year' => $last_year,
            "image" => $image,
        ]);

    }
}
