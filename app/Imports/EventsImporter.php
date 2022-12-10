<?php

namespace App\Imports;

use App\Models\Event;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class EventsImporter implements toModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Event([
            'title' => $row['title'],
            'happened_at' => new Carbon(Date::excelToTimestamp($row['happened_at'])),
            'location' => $row['location'],
            'category' => $row['category'],
            'url' => $row['url'],
            'description' => $row['description'],
        ]);
    }
}
