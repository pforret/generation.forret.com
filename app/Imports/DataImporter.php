<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DataImporter implements WithMultipleSheets
{

    public function sheets(): array
    {
        // https://docs.laravel-excel.com/3.1/imports/multiple-sheets.html
        return [
            "generations" => new GenerationsImporter(),
            "events" => new EventsImporter(),
            "people" => new PeopleImporter(),
            "quotes" => new QuotesImporter(),
        ];

    }
}
