<?php namespace Winter\Forum\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class MembersAddSpamGuard extends Migration
{
    public function up()
    {
        Schema::table('winter_forum_members', function($table)
        {
            $table->boolean('is_approved')->default(0)->index();
        });

        Schema::table('winter_forum_channels', function($table)
        {
            $table->boolean('is_guarded')->default(0);
        });

        Schema::table('winter_forum_posts', function($table)
        {
            $table->integer('count_links')->default(0);
        });

        // Automatically approve users with more than 25 posts
        // Db::table('winter_forum_members')->where('count_posts', '>=', 25)->update([
        //     'is_approved' => 1
        // ]);

        // Make all channels guarded
        // Db::table('winter_forum_channels')->update([
        //     'is_guarded' => 1
        // ]);
    }

    public function down()
    {
        Schema::table('winter_forum_members', function($table)
        {
            $table->dropColumn('is_approved');
        });

        Schema::table('winter_forum_channels', function($table)
        {
            $table->dropColumn('is_guarded');
        });

        Schema::table('winter_forum_posts', function($table)
        {
            $table->dropColumn('count_links');
        });
    }
}
