<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            // Daily-rotating salted hash of the visitor's IP — groups a day of
            // activity into a "session" without cookies or stored IPs.
            $table->string('session', 64)->index();
            $table->string('type', 24)->index();
            $table->string('path');
            $table->string('target')->nullable();
            $table->unsignedInteger('value')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
