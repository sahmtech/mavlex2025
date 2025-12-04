<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('zatca_documents', function (Blueprint $table) {
            if (!Schema::hasColumn('zatca_documents', 'response_source')) {
                $table->string('response_source')->nullable()->after('response');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('zatca_documents', function (Blueprint $table) {
            if (Schema::hasColumn('zatca_documents', 'response_source')) {
                $table->dropColumn('response_source');
            }
        });
    }
};


