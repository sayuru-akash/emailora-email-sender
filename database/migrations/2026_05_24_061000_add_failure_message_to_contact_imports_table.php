<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_imports', function (Blueprint $table): void {
            $table->text('failure_message')->nullable()->after('preview_rows');
        });
    }

    public function down(): void
    {
        Schema::table('contact_imports', function (Blueprint $table): void {
            $table->dropColumn('failure_message');
        });
    }
};
