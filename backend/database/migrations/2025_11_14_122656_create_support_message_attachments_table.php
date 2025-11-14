<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_message_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_message_id')->constrained()->onDelete('cascade');
            $table->string('file_name'); // Оригинальное имя файла
            $table->string('file_path'); // Путь к файлу в storage
            $table->string('file_url'); // URL для доступа к файлу
            $table->string('mime_type')->nullable(); // MIME тип файла
            $table->unsignedBigInteger('file_size')->nullable(); // Размер файла в байтах
            $table->timestamps();
            
            $table->index('support_message_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_message_attachments');
    }
};