<?php namespace Winter\Forum\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

class RenameTables extends Migration
{
    const TABLES = [
        'channels',
        'members',
        'posts',
        'topics',
        'topic_followers',
    ];

    public function up()
    {
        foreach (self::TABLES as $table) {
            $from = 'rainlab_forum_' . $table;
            $to   = 'winter_forum_' . $table;
            if (Schema::hasTable($from) && !Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }
    }

    public function down()
    {
        foreach (self::TABLES as $table) {
            $from = 'winter_forum_' . $table;
            $to   = 'rainlab_forum_' . $table;
            if (Schema::hasTable($from) && !Schema::hasTable($to)) {
                Schema::rename($from, $to);
            }
        }
    }
}
