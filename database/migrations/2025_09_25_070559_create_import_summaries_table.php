<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('import_summaries', function (Blueprint $table) {
            $table->id();
            $table->string('file_path');
            $table->integer('total')->default(0);
            $table->integer('imported')->default(0);
            $table->integer('updated')->default(0);
            $table->integer('invalid')->default(0);
            $table->integer('duplicates')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_summaries');
    }
};
