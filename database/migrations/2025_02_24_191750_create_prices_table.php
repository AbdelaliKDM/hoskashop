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
    Schema::create('prices', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('product_id');
      $table->unsignedBigInteger('user_type_id');
      $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
      $table->foreign('user_type_id')->references('id')->on('user_types')->onDelete('cascade');
      $table->decimal('unit_price', 10, 2);
      $table->decimal('pack_price', 10, 2);
      $table->softDeletes();
      $table->timestamps();

    });
  }

  /**
   * Reverse the migrations.
   */
  public function down(): void
  {
    Schema::dropIfExists('prices');
  }
};
