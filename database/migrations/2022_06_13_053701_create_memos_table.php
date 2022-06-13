<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMemosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('memos', function (Blueprint $table) {
            $table->unsignedBigInteger('id', true);
            //ユーザーのID
            //BigInteger:桁数の大きい数値型のカラム
            //unsigned:符号がなしになる（数字のみ）（外部キー制約を使えるようにするため）
            //true（第二引数）：自動的に１づつ増えていく
            $table->longText('content');
            //メモの内容
            $table->unsignedBigInteger('user_id');
            $table->softDeletes();
            //論理削除（形式上削除（最悪復活させられる））を定義：deleted_atを自動生成する
            $table->timestamp('updated_at')->default(\DB::raw('CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP'));
            //更新時間
            //timestampと書くと、レコード挿入時、更新時に値が入らないので、DB::rawで直接書いている
            //default：デフォルトの値を設定
            $table->timestamp('created_at')->default(\DB::raw('CURRENT_TIMESTAMP'));
            $table->foreign('user_id')->references('id')->on('users');
            //今回の外部キー制約：user_idテーブルに入る値は、usersテーブルのidとして存在するものでないとダメ
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('memos');
        //もしmemosテーブルが存在するなら、削除する
    }
}
