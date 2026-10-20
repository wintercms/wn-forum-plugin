<?php

namespace Winter\Forum\Components;

use Illuminate\Support\Facades\Response;
use Winter\Forum\Classes\ForumBaseComponent;
use Winter\Forum\Models\Channel as ForumChannel;
use Winter\Forum\Models\Topic as ForumTopic;

class RssFeed extends ForumBaseComponent
{
    /**
     * A collection of topics to display
     * @var \Illuminate\Support\Collection
     */
    public $topics;

    /**
     * If the post list should be filtered by a channel, the model to use.
     * @var \Winter\Forum\Models\Channel
     */
    public $channel;

    /**
     * Reference to the page name for the main blog page.
     * @var string
     */
    public $forumPage;

    /**
     * Reference to the page name for linking to topics.
     * @var string
     */
    public $topicPage;

    /**
     * Gets the details for the component
     */
    public function componentDetails()
    {
        return [
            'name'        => 'winter.forum::lang.components.rss_feed.name',
            'description' => 'winter.forum::lang.components.rss_feed.description'
        ];
    }

    /**
     * Returns the properties provided by the component
     */
    public function defineProperties()
    {
        return [
            'channelFilter' => [
                'title'       => 'winter.forum::lang.components.rss_feed.properties.channels_filter',
                'description' => 'winter.forum::lang.components.rss_feed.properties.channels_filter_description',
                'type'        => 'string',
                'default'     => '',
            ],
            'topicsPerPage' => [
                'title'             => 'winter.forum::lang.components.topics.properties.per_page',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'winter.forum::lang.components.topics.properties.per_page_validation',
                'default'           => '20',
            ],
            'forumPage' => [
                'title'       => 'winter.forum::lang.components.rss_feed.properties.forum',
                'description' => 'winter.forum::lang.components.rss_feed.properties.forum_description',
                'type'        => 'dropdown',
                'options'     => self::allPages(),
                'default'     => 'forum/home',
                'group'       => 'winter.forum::lang.components.general.groups.links',
            ],
            'topicPage' => [
                'title'       => 'winter.forum::lang.components.general.properties.topic_page',
                'description' => 'winter.forum::lang.components.general.properties.topic_page_desc',
                'type'        => 'dropdown',
                'default'     => 'forum/topic',
                'group'       => 'winter.forum::lang.components.general.groups.links',
            ],
        ];
    }

    public function onRun()
    {
        $this->prepareVars();

        $xmlFeed = $this->renderPartial('@default');

        return Response::make($xmlFeed, '200')->header('Content-Type', 'text/xml');
    }

    protected function prepareVars()
    {
        $this->setVar('forumPage', $this->property('forumPage'));
        $this->setVar('topicPage', $this->property('topicPage'));
        $this->setVar('channel', $this->loadChannel());
        $this->setVar('topics', $this->listTopics());

        $this->page['link'] = $this->pageUrl($this->forumPage);
        $this->page['rssLink'] = $this->currentPageUrl();
    }

    /**
     * Lists the topics for the RSS feed based on the current channel filter and pagination settings.
     *
     * @return void
     */
    protected function listTopics()
    {
        $channel = $this->channel ? $this->channel->id : null;

        /*
         * List all the topics, eager load their categories
         */
        $topics = ForumTopic::with('channel')->listFrontEnd([
            'perPage'  => $this->property('topicsPerPage'),
            'channels' => $channel,
            'sticky'   => false,
        ]);

        /*
         * Add a "url" helper attribute for linking to each post and channel
         */
        $topics->each(function ($post) {
            $post->setUrl($this->topicPage, $this->controller);
        });

        return $topics;
    }

    /**
     * Loads the current channel based on the channel filter property.
     *
     * @return \Winter\Forum\Models\Channel|null
     */
    protected function loadChannel()
    {
        if (!$channelId = $this->property('channelFilter')) {
            return null;
        }

        if (!$channel = ForumChannel::whereSlug($channelId)->first()) {
            return null;
        }

        return $channel;
    }
}
