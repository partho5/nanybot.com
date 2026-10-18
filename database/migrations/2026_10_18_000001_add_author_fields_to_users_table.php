<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every user is an author: these fields power the public author card,
     * author page and Person schema.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 60)->nullable()->unique()->after('name');
            $table->string('role', 20)->default('author')->after('email');
            $table->string('avatar', 2048)->nullable();
            $table->string('headline', 160)->nullable();
            $table->text('bio')->nullable();
            $table->json('social_links')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'role', 'avatar', 'headline', 'bio', 'social_links']);
        });
    }
};
