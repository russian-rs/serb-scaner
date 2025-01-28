<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('laws', function (Blueprint $table) {
            $table->id();
            $table->string('link')->unique()->comment('Ссылка на закон');
            $table->string('name', '1024')->unique()->comment('Название закона');
            $table->bigInteger('size')->nullable()->comment('Размер файла в байтах');
            $table->string('slug', '512')->nullable()->comment('Короткое имя файла из парламента');
            $table->boolean('is_downloaded')->default(false)->comment('Был ли закон скачан с сайта');
            $table->boolean('is_translated')->default(false)->comment('Был ли закон переведен');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('laws');
    }
};
