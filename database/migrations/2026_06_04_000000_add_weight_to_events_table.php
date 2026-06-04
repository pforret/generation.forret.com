<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Importance/impact score 0–5. Weight-5 events are the defining milestones
     * shown on every generation page ("how old they were when X happened").
     */
    public function up()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->unsignedTinyInteger('weight')->default(0)->after('happened_at')->index();
        });
    }

    public function down()
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('weight');
        });
    }
};
