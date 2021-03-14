<?php namespace Winter\Forum\Updates;

use Winter\Storm\Database\Updates\Migration;
use DbDongle;

class UpdateTimestampsNullable extends Migration
{
    public function up()
    {
        DbDongle::disableStrictMode();

        DbDongle::convertTimestamps('winter_forum_channels');
        DbDongle::convertTimestamps('winter_forum_members');
        DbDongle::convertTimestamps('winter_forum_posts');
        DbDongle::convertTimestamps('winter_forum_topic_followers');
        DbDongle::convertTimestamps('winter_forum_topics');
    }

    public function down()
    {
        // ...
    }
}
