<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'branch_id')) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('description');
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            }
        });
    }

    public function down()
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'branch_id')) {
                $table->dropForeign(['branch_id']);
                $table->dropColumn('branch_id');
            }
        });
    }
};
