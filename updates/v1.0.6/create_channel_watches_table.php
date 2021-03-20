<?php namespace Winter\Forum\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class CreateChannelWatchesTable extends Migration
{
    public function up()
    {
        Schema::create('winter_forum_channel_watches', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('channel_id')->unsigned()->index()->nullable();
            $table->integer('member_id')->unsigned()->index()->nullable();
            $table->integer('count_topics')->index()->default(0);
            $table->dateTime('watched_at')->nullable()->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('winter_forum_channel_watches');
    }
}
