<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('order_products', function (Blueprint $table) { 
            $table->foreignId('size_id')->nullable()->constrained()->onDelete('set null'); 
            $table->foreignId('color_id')->nullable()->constrained()->onDelete('set null'); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('order_products', function (Blueprint $table) { 
            $table->dropForeign(['size_id']); 
            $table->dropColumn('size_id'); 
            $table->dropForeign(['color_id']); 
            $table->dropColumn('color_id'); });
    }
};
