<?php namespace Winter\Forum\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class DropWatchesTables extends Migration
{
    public function up()
    {
        Schema::dropIfExists('rainlab_forum_topic_watches');
        Schema::dropIfExists('rainlab_forum_channel_watches');
    }

    public function down()
    {
        // ...
    }
}
