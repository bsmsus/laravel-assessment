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
            $table->enum('type', ['flat', 'percent']);
            $table->decimal('value', 8, 2);
            $table->boolean('active')->default(true);
            $table->timestamp('expires_at')->nullable();
            $table->integer('usage_limit_per_user')->nullable();
            $table->integer('usage_limit_total')->nullable();
            $table->integer('usage_count')->default(0); // global usage counter
            $table->timestamps();
        });

        Schema::create('user_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discount_id')->constrained('discounts')->cascadeOnDelete();
            $table->integer('usage_count')->default(0);
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'discount_id']);
        });

        Schema::create('discount_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
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
