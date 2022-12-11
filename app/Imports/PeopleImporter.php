<?php

namespace App\Imports;

use App\Models\Person;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class PeopleImporter implements toModel, WithHeadingRow
{
    public function model(array $row)
    {
        if(!$row['image']){
            $filename=Str::slug($row['name']);
            $filepath = dirname(__DIR__, 2) . "/public/images/people/$filename.jpg";
            if(file_exists($filepath)){
                $row['image']="/images/people/$filename.jpg";
            } else {
                echo "? [$filename]\n";
            }
        }
        return new Person([
            'name' => $row['name'],
            'category' => $row['category'],
            'country' => $row['country'],
            'description' => $row['description'],
            'image' => $row['image'],
            'born_at' => new Carbon(Date::excelToTimestamp($row['born_at'])),
        ]);
    }
}
