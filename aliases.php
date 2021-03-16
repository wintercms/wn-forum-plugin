<?php
/**
 * To allow compatibility with plugins that extend the original RainLab.Forum plugin, this will alias those classes to
 * use the new Winter.Forum classes.
 */
$aliases = [
    // Regular aliases
    Winter\Forum\Plugin::class                   => 'Winter\Forum\Plugin',
    Winter\Forum\Classes\TopicTracker::class     => 'Winter\Forum\Classes\TopicTracker',
    Winter\Forum\Components\Channels::class      => 'Winter\Forum\Components\Channels',
    Winter\Forum\Components\Member::class        => 'Winter\Forum\Components\Member',
    Winter\Forum\Components\Topics::class        => 'Winter\Forum\Components\Topics',
    Winter\Forum\Components\Channel::class       => 'Winter\Forum\Components\Channel',
    Winter\Forum\Components\Posts::class         => 'Winter\Forum\Components\Posts',
    Winter\Forum\Components\Topic::class         => 'Winter\Forum\Components\Topic',
    Winter\Forum\Components\RssFeed::class       => 'Winter\Forum\Components\RssFeed',
    Winter\Forum\Components\EmbedChannel::class  => 'Winter\Forum\Components\EmbedChannel',
    Winter\Forum\Components\EmbedTopic::class    => 'Winter\Forum\Components\EmbedTopic',
    Winter\Forum\Controllers\Channels::class     => 'Winter\Forum\Controllers\Channels',
    Winter\Forum\Models\TopicFollow::class       => 'Winter\Forum\Models\TopicFollow',
    Winter\Forum\Models\Post::class              => 'Winter\Forum\Models\Post',
    Winter\Forum\Models\Member::class            => 'Winter\Forum\Models\Member',
    Winter\Forum\Models\Channel::class           => 'Winter\Forum\Models\Channel',
    Winter\Forum\Models\Topic::class             => 'Winter\Forum\Models\Topic',
];

foreach ($aliases as $original => $alias) {
    if (!class_exists($alias)) {
        class_alias($original, $alias);
    }
}
