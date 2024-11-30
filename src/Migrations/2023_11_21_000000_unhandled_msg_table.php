<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('unhandled_messages', function (Blueprint $table) {
			$table->uuid('id')->primary();
			$table->string('topic', 255)->index();
			$table->string('action', 255)->index();
			$table->string('object', 255)->index();
			$table->json('body');
			$table->json('headers');
			$table->json('properties');
			$table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unhandled_messages');
    }
};
