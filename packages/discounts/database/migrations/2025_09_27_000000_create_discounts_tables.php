<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['flat', 'percentage']);
            $table->decimal('value', 8, 2);
            $table->boolean('active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->integer('usage_limit_per_user')->nullable();
            $table->integer('usage_limit_total')->nullable();
            $table->integer('usage_count')->default(0);
            $table->integer('usage_cap')->nullable();
            $table->timestamps();
        });

        // NOTE: do NOT add an immediate DB foreign key referencing the host app's users table.
        // Create the column, index, and unique constraint; enforce referential integrity in app logic.
        Schema::create('user_discounts', function (Blueprint $table) {
            $table->id();

            // store user id, but do not call constrained() here
            $table->unsignedBigInteger('user_id')->index();

            // foreign key to internal discount table is safe (same migration)
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();

            $table->integer('usage_count')->default(0);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'discount_id']);
        });

        Schema::create('discount_audits', function (Blueprint $table) {
            $table->id();

            // store user id, but do not add FK to users
            $table->unsignedBigInteger('user_id')->index();

            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->string('action'); // assigned, revoked, applied
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('discount_audits');
        Schema::dropIfExists('user_discounts');
        Schema::dropIfExists('discounts');
    }
};
