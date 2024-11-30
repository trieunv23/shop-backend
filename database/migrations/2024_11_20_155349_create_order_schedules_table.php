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
        Schema::create('order_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
            $table->unsignedBigInteger('user_id');
            $table->enum('status', ['pending', 'confirmed', 'in_transit', 'shipped', 'delivered', 'canceled'])->default('pending');
            $table->date('order_date');
            // $table->date('delivery_date')->nullable();
            $table->date('delivered_date')->nullable();
            // $table->string('carrier_name')->nullable();
            // $table->string('tracking_number')->nullable();
            $table->text('shipping_address');
            $table->string('recipient_name');
            $table->string('recipient_phone')->nullable();
            $table->decimal('shipping_cost', 10, 2)->nullable(); 
            $table->text('schedule_description')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Foreign Key Constraints
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('order_schedules');
    }
};
