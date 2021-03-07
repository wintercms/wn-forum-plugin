<?php namespace Winter\Forum\Updates;

use Winter\Storm\Database\Updates\Migration;
use DbDongle;

class UpdateTimestampsNullable extends Migration
{
    public function up()
    {
        DbDongle::disableStrictMode();

        DbDongle::convertTimestamps('rainlab_forum_channels');
        DbDongle::convertTimestamps('rainlab_forum_members');
        DbDongle::convertTimestamps('rainlab_forum_posts');
        DbDongle::convertTimestamps('rainlab_forum_topic_followers');
        DbDongle::convertTimestamps('rainlab_forum_topics');
    }

    public function down()
    {
        // ...
    }
}
