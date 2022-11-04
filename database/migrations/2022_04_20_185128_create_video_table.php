<?php

use Yhdccc\Video\Enums\VideoEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVideoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('video', function (Blueprint $table) {
            $table->id();
            $table->string('video_id',16)->comment('阿里云点播id');
            $table->string('title',64)->comment('标题');
            $table->string('image',255)->comment('封面');
            $table->string('desc',255)->nullable()->comment('简介');
            $table->string('content',1024)->nullable()->comment('内容');
            $table->integer('duration')->default(0)->comment('时间长度');
            $table->integer('publish_by')->nullable()->comment('发布人');
            $table->timestamp('publish_at')->nullable()->comment('发布时间');
            $table->tinyInteger('status')->default(VideoEnum::NORMAL)->comment('状态 1:未发布 2已发布');
            $table->tinyInteger('flag')->default(0)->comment('推荐状态 0:默认 1:已推荐');
            $table->tinyInteger('top')->default(0)->comment('置顶状态 0:默认 1:置顶');
            $table->integer('weight')->default(0)->comment('权重(排序专用)');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('video');
    }
}
