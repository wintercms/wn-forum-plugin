<?php

namespace Winter\Forum\Components;

use Winter\Forum\Classes\ForumBaseComponent;
use Winter\Forum\Models\Topic as TopicModel;
use Winter\Forum\Models\Channel as ChannelModel;
use Exception;

class EmbedTopic extends ForumBaseComponent
{
    /**
     * @var boolean Determine if this component is being used by the EmbedChannel component.
     */
    public $embedMode = true;

    /**
     * Gets the details for the component
     */
    public function componentDetails()
    {
        return [
            'name'        => 'winter.forum::lang.components.embed_topic.topic_name',
            'description' => 'winter.forum::lang.components.embed_topic.topic_self_desc'
        ];
    }

    /**
     * Returns the properties provided by the component
     */
    public function defineProperties()
    {
        return [
            'embedCode' => [
                'title'       => 'winter.forum::lang.components.embed_topic.embed_title',
                'description' => 'winter.forum::lang.components.embed_topic.embed_desc',
                'type'        => 'string',
            ],
            'channelSlug' => [
                'title'       => 'winter.forum::lang.components.embed_topic.target_name',
                'description' => 'winter.forum::lang.components.embed_topic.target_desc',
                'type'        => 'dropdown'
            ],
            'memberPage' => [
                'title'       => 'winter.forum::lang.components.general.properties.member_page',
                'description' => 'winter.forum::lang.components.general.properties.member_page_desc',
                'type'        => 'dropdown',
                'group'       => 'Links',
            ],
        ];
    }

    /**
     * Returns the options for the channelSlug property dropdown
     *
     * @return array
     */
    public function getChannelSlugOptions()
    {
        return ChannelModel::listsNested('title', 'slug', ' - ');
    }

    public function init()
    {
        $mode = $this->property('mode');
        $code = $this->property('embedCode');

        if (!$code) {
            throw new Exception('No code specified for the Forum Embed component');
        }

        $channel = ($channelSlug = $this->property('channelSlug'))
            ? ChannelModel::whereSlug($channelSlug)->first()
            : null;

        if (!$channel) {
            throw new Exception('No channel specified for Forum Embed component');
        }

        $properties = $this->getProperties();

        /*
         * Proxy as topic
         */
        if ($topic = TopicModel::forEmbed($channel, $code)->first()) {
            $properties['slug'] = $topic->slug;
        }

        $component = $this->addComponent('Winter\Forum\Components\Topic', $this->alias, $properties);

        /*
         * If a topic does not already exist, generate it when the page ends.
         * This can be disabled by the page setting embedMode to FALSE, for example,
         * if the page returns 404 a topic should not be generated.
         */
        if (!$topic) {
            $this->controller->bindEvent('page.end', function () use ($component, $channel, $code) {
                if ($component->embedMode !== false) {
                    $topic = TopicModel::createForEmbed($code, $channel, $this->page->title);
                    $component->setProperty('slug', $topic->slug);
                    $component->onRun();
                }
            });
        }

        /*
         * Set the embedding mode: Single topic
         */
        $component->embedMode = 'single';
    }
}
