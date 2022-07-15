<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssessmentCoposTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('assessment_copos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('co_id')->unique();
            $table->json('indirect_assessment')->nullable();
            $table->json('direct_attainment');
            $table->json('copo')->nullable();
            $table->json('indirect_attainment')->nullable();
            $table->json('consolidated_copo')->nullable();
            $table->double('copo_avarage', 4, 2)->nullable();
            $table->json('feed_back')->nullable();
            $table->timestamps();
            $table->foreign('co_id')->references('id')->on('studentmarks')
                                                    ->onDelete('cascade')
                                                    ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('assessment_copos');
    }
}
