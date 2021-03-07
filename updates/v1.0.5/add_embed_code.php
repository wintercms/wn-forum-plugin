<?php namespace Winter\Forum\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class AddEmbedCode extends Migration
{
    public function up()
    {
        Schema::table('rainlab_forum_channels', function($table)
        {
            $table->string('embed_code')->nullable()->index();
        });

        Schema::table('rainlab_forum_topics', function($table)
        {
            $table->string('embed_code')->nullable()->index();
        });
    }

    public function down()
    {
        Schema::table('rainlab_forum_channels', function($table)
        {
            $table->dropColumn('embed_code');
        });

        Schema::table('rainlab_forum_topics', function($table)
        {
            $table->dropColumn('embed_code');
        });
    }
}
