<?php

namespace Winter\Forum\Components;

use Winter\Forum\Classes\ForumBaseComponent;
use Winter\Forum\Models\Channel;
use Winter\Forum\Models\Member as MemberModel;
use Winter\Forum\Classes\TopicTracker;

class Channels extends ForumBaseComponent
{
    /**
     * @var \Winter\Forum\Models\Member Member cache
     */
    protected $member;

    /**
     * @var \Winter\Forum\Models\Channel Channel collection cache
     */
    protected $channels;

    /**
     * @var string Reference to the page name for linking to members.
     */
    public $memberPage;

    /**
     * @var string Reference to the page name for linking to topics.
     */
    public $topicPage;

    /**
     * @var string Reference to the page name for linking to channels.
     */
    public $channelPage;

    /**
     * Gets the details for the component
     */
    public function componentDetails()
    {
        return [
            'name'        => 'winter.forum::lang.components.channels.name',
            'description' => 'winter.forum::lang.components.channels.description'
        ];
    }

    /**
     * Returns the properties provided by the component
     */
    public function defineProperties()
    {
        return [
            'memberPage' => [
                'title'       => 'winter.forum::lang.components.general.properties.member_page',
                'description' => 'winter.forum::lang.components.general.properties.member_page_desc',
                'type'        => 'dropdown',
                'default'     => 'forum/member',
                'group'       => 'Links',
            ],
            'channelPage' => [
                'title'       => 'winter.forum::lang.components.general.properties.channel_page',
                'description' => 'winter.forum::lang.components.general.properties.channel_page_desc',
                'type'        => 'dropdown',
                'default'     => 'forum/channel',
                'group'       => 'Links',
            ],
            'topicPage' => [
                'title'       => 'winter.forum::lang.components.general.properties.topic_page',
                'description' => 'winter.forum::lang.components.general.properties.topic_page_desc',
                'type'        => 'dropdown',
                'default'     => 'forum/topic',
                'group'       => 'Links',
            ],
            'includeStyles' => [
                'title'       => 'winter.forum::lang.components.general.properties.includeStyles',
                'description' => 'winter.forum::lang.components.general.properties.includeStyles_desc',
                'type'        => 'checkbox',
                'default'     => true
            ],
        ];
    }

    public function onRun()
    {
        if ($this->property('includeStyles', true)) {
            $this->addCss('assets/css/forum.css');
        }

        $this->prepareVars();
        $this->page['channels'] = $this->listChannels();
    }

    protected function prepareVars()
    {
        /*
         * Page links
         */
        $this->setVar('memberPage', $this->property('memberPage'));
        $this->setVar('channelPage', $this->property('channelPage'));
        $this->setVar('topicPage', $this->property('topicPage'));
    }

    /**
     * Returns a list of channels for the component
     */
    public function listChannels()
    {
        if ($this->channels !== null) {
            return $this->channels;
        }

        $channels = Channel::with('first_topic')->isVisible()->get();

        /*
         * Add a "url" helper attribute for linking to each channel
         */
        $channels->each(function ($channel) {
            $channel->setUrl($this->channelPage, $this->controller);

            if ($channel->first_topic) {
                $channel->first_topic->setUrl($this->topicPage, $this->controller);
            }
        });

        $this->page['member'] = $this->member = MemberModel::getFromUser();

        if ($this->member) {
            $channels = TopicTracker::instance()->setFlagsOnChannels($channels, $this->member);
        }

        $channels = $channels->toNested();

        return $this->channels = $channels;
    }
}
