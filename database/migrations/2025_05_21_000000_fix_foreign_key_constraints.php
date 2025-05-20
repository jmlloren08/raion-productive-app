<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations to fix foreign key constraints for deals
     */
    public function up(): void
    {
        // First, handle deals with invalid project_id references
        // Set project_id to null for deals where the project doesn't exist
        DB::statement("
            UPDATE productive_deals 
            SET project_id = NULL 
            WHERE project_id IS NOT NULL 
              AND project_id NOT IN (SELECT id FROM productive_projects)
        ");
        
        // Next, handle deals with invalid company_id references
        // Set company_id to null for deals where the company doesn't exist
        DB::statement("
            UPDATE productive_deals 
            SET company_id = NULL 
            WHERE company_id IS NOT NULL 
              AND company_id NOT IN (SELECT id FROM productive_companies)
        ");
        
        // Also fix projects with invalid company_id references
        DB::statement("
            UPDATE productive_projects 
            SET company_id = NULL 
            WHERE company_id IS NOT NULL 
              AND company_id NOT IN (SELECT id FROM productive_companies)
        ");
    }

    /**
     * Reverse the migrations.
     * This is a data fix, so no reversal needed
     */
    public function down(): void
    {
        // Nothing to undo for this fix
    }
};
