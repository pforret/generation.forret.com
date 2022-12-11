<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminated\Wikipedia\Wikipedia;
use Soundasleep\Html2Text;

class CleanWiki
{

    static function get(string $topic, int $max_words = 100): string
    {
        if ($contents = Cache::get($topic)) {
            return Str::words($contents, $max_words);
        }
        $text = (new Wikipedia())->preview($topic)->getBody();
        if (!$text) {
            return "";
        }
        $text = str_replace(array("\n", "\\n"), " ", $text);
        $text = preg_replace("|(<style>.*</style>)|m","",$text);
        $text = preg_replace("|<[^>]+>|","",$text);
        $text = trim(str_replace(["(listen)","\n","\\n"],"",$text));
        Cache::set($topic,$text);
        return Str::words($text, $max_words);
    }
}
