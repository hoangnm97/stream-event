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
        Schema::create('failed_streams', function (Blueprint $table) {
			$table->id();
			$table->string('platform', 20)->default('kafka');
			$table->text('body')->nullable();
			$table->json('headers')->nullable();
			$table->json('properties')->nullable();
			$table->text('exception')->nullable();
			$table->string('topic', 255)->default('order');
			$table->boolean('handle')->default(false)->comment('0: produce, 1: consume');
			$table->timestamp('failed_at',0)->nullable();
			$table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('failed_streams');
    }
};
