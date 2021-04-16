<?php namespace Winter\Forum\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class ChannelsAddHiddenAndModerated extends Migration
{
    public function up()
    {
        Schema::table('rainlab_forum_channels', function($table)
        {
            $table->boolean('is_hidden')->default(0);
            $table->boolean('is_moderated')->default(0);
        });
    }

    public function down()
    {
        Schema::table('rainlab_forum_channels', function($table)
        {
            $table->dropColumn('is_hidden', 'is_moderated');
        });
    }
}
