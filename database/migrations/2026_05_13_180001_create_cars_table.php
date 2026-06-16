<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();

            // Free-text marketing title. Admin types it once in their preferred
            // language; the public site shows it exactly as entered.
            $table->string('title');
            $table->string('slug')->unique();

            // Brand / model / trim stay as a single, canonical Latin value
            // ("Toyota", "Land Cruiser", "Limited") so they index nicely.
            $table->string('brand');
            $table->string('model');
            $table->string('trim')->nullable();
            $table->string('body_type');

            $table->unsignedSmallInteger('year');

            // Color, origin and city store an enum key (e.g. "black", "gcc",
            // "riyadh"). Labels are localised at display time via translation
            // files, so a single value works for both Arabic and English.
            $table->string('color')->nullable();

            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('USD');

            $table->string('origin')->nullable();
            $table->unsignedInteger('mileage')->default(0);

            $table->string('transmission');
            $table->string('fuel_type');
            $table->string('engine_size')->nullable();
            $table->string('drivetrain')->nullable();

            $table->string('condition')->default('used');

            $table->string('city')->nullable();

            // Free-text long description. Optional, single language.
            $table->text('description')->nullable();

            $table->string('whatsapp_number')->nullable();

            $table->string('status')->default('available');
            $table->boolean('is_featured')->default(false);

            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Single-column indexes for filtering
            $table->index('brand');
            $table->index('model');
            $table->index('body_type');
            $table->index('year');
            $table->index('color');
            $table->index('price');
            $table->index('origin');
            $table->index('mileage');
            $table->index('transmission');
            $table->index('fuel_type');
            $table->index('status');
            $table->index('is_featured');
            $table->index('published_at');
            $table->index('city');

            // Composite indexes for the most common combined queries
            $table->index(['status', 'is_featured']);
            $table->index(['status', 'published_at']);
            $table->index(['brand', 'model']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
