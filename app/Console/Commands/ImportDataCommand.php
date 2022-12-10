<?php

namespace App\Console\Commands;

use App\Imports\DataImporter;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class ImportDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'import data from Excel';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $file = __DIR__ . '/../../../database/files/import.xlsx';
        $file = realpath($file);
        if(!file_exists($file)){
            $this->error("file [$file] not found");
            return Command::FAILURE;
        }
        $this->info("Importing [$file] ...");
        Excel::import(new DataImporter(), $file);
        return Command::SUCCESS;
    }
}
