<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * External reference URLs per person, filled by `php artisan people:enrich`.
     */
    public function up()
    {
        Schema::table('people', function (Blueprint $table) {
            $table->string('url_wikipedia')->nullable()->after('description');
            $table->string('url_imdb')->nullable()->after('url_wikipedia');
        });
    }

    public function down()
    {
        Schema::table('people', function (Blueprint $table) {
            $table->dropColumn(['url_wikipedia', 'url_imdb']);
        });
    }
};
