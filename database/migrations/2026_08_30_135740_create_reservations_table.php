<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// 💡 수정된 부분: return new class 대신 클래스명을 명시적으로 지정
class CreateReservationsTable extends Migration
{
    public function up()
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->integer('menu_id'); // 코스 ID (1: Beauty Care, 2: About Business)
            $table->date('date');       // 예약 날짜
            $table->string('time', 10); // 예약 시간 (예: '10:00')
            $table->string('name', 100);
            $table->string('phone', 20);
            $table->string('email', 150);
            $table->text('memo')->nullable(); // 비고 (선택)
            $table->string('introducer_name', 100)->nullable(); // 소개자 이름 (선택)
            $table->string('introducer_id', 50)->nullable();    // 소개자 ID (선택)
            $table->timestamps();

            // 💡 핵심: 같은 날짜(date)와 시간(time)에 중복 데이터가 들어가지 못하도록 고유키 설정!
            $table->unique(['date', 'time'], 'unique_date_time_booking');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservations');
    }
}