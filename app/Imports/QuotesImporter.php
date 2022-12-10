<?php

namespace App\Imports;

use App\Models\Generation;
use App\Models\Quote;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class QuotesImporter implements toModel, WithHeadingRow
{

    private array $generation_ids;

    public function __construct()
    {
        $this->generation_ids = [];
    }

    public function model(array $row)
    {
        $generation_name = $row["generation"];
        $this->generation_ids[$generation_name] = $this->generation_ids[$generation_name] ?? Generation::whereTitle($generation_name)->pluck('id')[0];
        return new Quote([
            'generation_id' => $this->generation_ids[$generation_name],
            'title' => $row['title'],
            'author' => $row['author'],
            'url' => $row['url'],
            'description' => $row['description'],
            'image' => $row['image'],
        ]);
    }
}
