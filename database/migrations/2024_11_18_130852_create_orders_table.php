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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('total_amount', 10, 2)->default(0.00);
            $table->foreignId('voucher_id')->nullable()->constrained()->onDelete('set null');
            $table->string('shipping_name');
            $table->string('shipping_phone');
            $table->string('shipping_province');
            $table->string('shipping_district');
            $table->string('shipping_ward');
            $table->enum('shipping_status', [
                'not_shipped',        
                'in_transit',     
                'delivered',    
                'failed',         
                'returned'
            ])->default('not_shipped');
            $table->enum('shipping_method', ['fast', 'save'])->default('fast');
            $table->enum('payment_method', ['direct_payment', 'bank_payment']);
            $table->enum('payment_status', ['pending', 'complete', 'failed'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->enum('order_status', ['pending', 'complete', 'delivered', 'cancelled'])->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
};
