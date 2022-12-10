<?php

namespace App\Imports;

use App\Models\Generation;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class GenerationsImporter implements toModel, WithHeadingRow
{

    public function model(array $row)
    {
        return new Generation(
            [
                'slug' => $row['slug'],
                'title' => $row['title'],
                'first_year' => $row['first_year'],
                'last_year' => $row['last_year'],
                'alternatives' => $row['alternatives'],
                'image' => $row['image'],
                'description' => $row['description'],
            ]
        );
    }
}
