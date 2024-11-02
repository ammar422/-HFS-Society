<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMembersTable extends Migration
{
    public function up()
    {
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade'); // Link to users table
            $table->foreignId('sponsor_id')->nullable()->constrained('members', 'id')->onDelete('set null');  
            $table->foreignId('left_leg_id')->nullable()->constrained('members', 'id')->onDelete('set null');  
            $table->foreignId('right_leg_id')->nullable()->constrained('members', 'id')->onDelete('set null');  
            $table->integer('sales_volume')->default(0);
            $table->string('rank')->default('undefined');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('members');
    }
}
