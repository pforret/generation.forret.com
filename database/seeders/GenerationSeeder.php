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
        $this->insert('Baby Boomers','boomers','',1946,1964);
        $this->insert('Generation X','x','',1965,1980);
        $this->insert('Generation Y','y','millenials',1981,1996);
        $this->insert('Generation Z','z','Gen-Z',1997,2012);
        $this->insert('Generation Alpha','alpha','',2013,2024);
    }

    private function insert(string $title, string $slug, string $alternatives, int $first_year, int $last_year)
    {
        DB::table('generations')->insert([
            'title' => $title,
            'slug' => $slug,
            'alternatives' => $alternatives,
            'first_year' => $first_year,
            'last_year' => $last_year,
        ]);

    }
}
