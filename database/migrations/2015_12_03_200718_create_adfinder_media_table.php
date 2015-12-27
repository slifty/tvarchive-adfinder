<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAdfinderMediaTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('adfinder_media', function (Blueprint $table) {

            // Create fields
            $table->increments('id');
            $table->integer('duplitron_id')->nullable();
            $table->text('archive_id')->nullable();
            $table->text('status')->nullable();
            $table->text('process')->nullable();
            $table->text('media_path')->nullable();
            $table->text('afpt_path')->nullable();
            $table->timestamps();
            $table->index(['duplitron_id']);
            $table->index(['archive_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('adfinder_media');
    }
}
