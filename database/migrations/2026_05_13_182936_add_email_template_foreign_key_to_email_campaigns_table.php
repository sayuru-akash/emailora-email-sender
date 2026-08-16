<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql' || $this->hasTemplateForeignKey()) {
            return;
        }

        Schema::table('email_campaigns', function (Blueprint $table): void {
            $table->foreign('email_template_id')->references('id')->on('email_templates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql' || ! $this->hasTemplateForeignKey()) {
            return;
        }

        Schema::table('email_campaigns', function (Blueprint $table): void {
            $table->dropForeign(['email_template_id']);
        });
    }

    private function hasTemplateForeignKey(): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::getDatabaseName())
            ->where('table_name', 'email_campaigns')
            ->where('constraint_name', 'email_campaigns_email_template_id_foreign')
            ->where('constraint_type', 'FOREIGN KEY')
            ->exists();
    }
};
