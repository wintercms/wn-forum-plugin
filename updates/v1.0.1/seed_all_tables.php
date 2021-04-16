<?php namespace Winter\Forum\Updates;

use Schema;
use Winter\Forum\Models\Channel;
use Winter\Storm\Database\Updates\Seeder;

class SeedAllTables extends Seeder
{
    public function run()
    {
        Channel::extend(function($model) {
            $model->setTable('rainlab_forum_channels');
        });

        $orange = Channel::create([
            'title' => 'Channel Orange',
            'description' => 'A root level forum channel',
        ]);

        $autumn = $orange->children()->create([
            'title' => 'Autumn Leaves',
            'description' => 'Discussion about the season of falling leaves.'
        ]);

        $autumn->children()->create([
            'title' => 'September',
            'description' => 'The start of the fall season.'
        ]);

        $winter = $autumn->children()->create([
            'title' => 'Winter CMS',
            'description' => 'The middle of the fall season.'
        ]);

        $autumn->children()->create([
            'title' => 'November',
            'description' => 'The end of the fall season.'
        ]);

        $orange->children()->create([
            'title' => 'Summer Breeze',
            'description' => 'Discussion about the wind at the ocean.'
        ]);

        $green = Channel::create([
            'title' => 'Channel Green',
            'description' => 'A root level forum channel',
        ]);

        $green->children()->create([
            'title' => 'Winter Snow',
            'description' => 'Discussion about the frosty snow flakes.'
        ]);

        $green->children()->create([
            'title' => 'Spring Trees',
            'description' => 'Discussion about the blooming gardens.'
        ]);

        Channel::extend(function($model) {
            $model->setTable('winter_forum_channels');
        });
    }
}
