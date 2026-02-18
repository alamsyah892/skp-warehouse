<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique()->nullable();
            $table->string('name');
            $table->text('description');

            $table->string('alias');
            $table->text('address');
            $table->string('city');
            $table->string('post_code');
            $table->string('contact_person');
            $table->string('contact_person_position');
            $table->string('phone');
            $table->string('fax');
            $table->string('email');
            $table->string('website');
            $table->string('tax_number');

            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
