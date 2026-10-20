<?php

namespace Winter\Forum\Classes;

use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Cms\Classes\Theme;

/**
 * Base class for all forum components in the Winter Forum plugin.
 */
abstract class ForumBaseComponent extends ComponentBase
{
    /**
     * Return CMS Pages with [forumChannel] component
     * @return array
     */
    public function getChannelPageOptions()
    {
        return $this->getPagesByComponent('forumChannel');
    }

    /**
     * Return CMS Pages with [forumChannels] component
     * @return array
     */
    public function getChannelsPageOptions()
    {
        return $this->getPagesByComponent('forumChannels');
    }

    /**
     * Return CMS Pages with [forumMember] component
     * @return array
     */
    public function getMemberPageOptions()
    {
        return $this->getPagesByComponent('forumMember');
    }

    /**
     * Return CMS Pages with [forumTopic] component
     * @return array
     */
    public function getTopicPageOptions()
    {
        return $this->getPagesByComponent('forumTopic');
    }


    /**
     * Get Pages by CMS Component
     * @param string $component
     * @param bool $allOnEmpty
     * @return array
     */
    protected function getPagesByComponent(string $component, bool $allOnEmpty = true)
    {
        $theme = Theme::getActiveTheme();
        $pages = Page::listInTheme($theme, true);

        $cmsPages = [];

        foreach ($pages as $page) {
            if (!$page->hasComponent($component)) {
                continue;
            }
            $cmsPages[$page->baseFileName] = $page->title ?? $page->id;
        }

        if (count($cmsPages) < 1) {
            return $allOnEmpty ? $this->allPages() : [];
        } else {
            return $cmsPages;
        }
    }

    /**
     * Return all CMS Pages
     * @return array
     */
    protected function allPages()
    {
        return Page
            ::listInTheme(Theme::getActiveTheme(), true)
            ->mapWithKeys(function ($page) {
                return [$page->baseFileName => $page->title];
            })
            ->toArray();
    }

    /**
     * Set a variable for the component and the page.
     *
     * @param string $name
     * @param mixed $value
     * @return mixed
     */
    protected function setVar($name, $value)
    {
        return $this->$name = $this->page[$name] = $value;
    }
}
