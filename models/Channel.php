<?php namespace Winter\Forum\Models;

use Model;
use ApplicationException;
use Url;
use Cms\Classes\Page as CmsPage;
use Cms\Classes\Theme;

/**
 * Channel Model
 */
class Channel extends Model
{

    use \Winter\Storm\Database\Traits\Purgeable;
    use \Winter\Storm\Database\Traits\Sluggable;
    use \Winter\Storm\Database\Traits\Validation;
    use \Winter\Storm\Database\Traits\NestedTree;

    public $implement = ['@Winter.Translate.Behaviors.TranslatableModel'];

    /**
     * @var string The database table used by the model.
     */
    public $table = 'winter_forum_channels';

    /**
     * @var array Guarded fields
     */
    protected $guarded = [];

    /**
     * @var array Fillable fields
     */
    protected $fillable = ['title', 'description', 'parent_id'];

    /**
     * @var array The attributes that should be visible in arrays.
     */
    protected $visible = ['title', 'description'];

    /**
     * @var array List of attribute names which should not be saved to the database.
     */
     protected $purgeable = ['url'];

    /**
     * @var array Validation rules
     */
    public $rules = [
        'title' => 'required'
    ];

    /**
     * @var array Auto generated slug
     */
    protected $slugs = ['slug' => 'title'];

    /**
     * @var array Relations
     */
    public $hasMany = [
        'topics' => ['Winter\Forum\Models\Topic']
    ];

    /**
     * @var array Relations
     */
    public $hasOne = [
        'first_topic' => ['Winter\Forum\Models\Topic', 'order' => 'updated_at desc']
    ];

    /**
     * @var array Attributes that support translation, if available.
     */
    public $translatable = ['title', 'description'];

    /**
     * @var boolean Channel has new posts for member, set by TopicTracker model
     */
    public $hasNew = false;

    /**
     * Handler for the pages.menuitem.getTypeInfo event.
     * Returns a menu item type information. The type information is returned as array
     * with the following elements:
     * - references - a list of the item type reference options. The options are returned in the
     *   ["key"] => "title" format for options that don't have sub-options, and in the format
     *   ["key"] => ["title"=>"Option title", "items"=>[...]] for options that have sub-options. Optional,
     *   required only if the menu item type requires references.
     * - nesting - Boolean value indicating whether the item type supports nested items. Optional,
     *   false if omitted.
     * - dynamicItems - Boolean value indicating whether the item type could generate new menu items.
     *   Optional, false if omitted.
     * - cmsPages - a list of CMS pages (objects of the Cms\Classes\Page class), if the item type requires a CMS page reference to
     *   resolve the item URL.
     * @param string $type Specifies the menu item type
     * @return array Returns an array
     */
    public static function getMenuTypeInfo($type)
    {
        $result = [];

        if ($type == 'forum-channel') {
            $result = [
                'references' => self::listChildrenOptions(),
                'nesting' => true,
                'dynamicItems' => true
            ];
        }

        if ($type == 'all-forum-channels') {
            $result = [
                'dynamicItems' => true
            ];
        }

        if ($result) {
            $theme = Theme::getActiveTheme();

            $pages = CmsPage::listInTheme($theme, true);
            $cmsPages = [];
            foreach ($pages as $page) {
                if (!$page->hasComponent('forumChannel')) {
                    continue;
                }

                /*
                 * Component must use a slug channel filter with a routing parameter
                 * eg: slug = "{{ :somevalue }}"
                 */
                $properties = $page->getComponentProperties('forumChannel');
                if (!isset($properties['slug']) || !preg_match('/{{\s*:/', $properties['slug'])) {
                    continue;
                }

                $cmsPages[] = $page;
            }

            $result['cmsPages'] = $cmsPages;
        }

        return $result;
    }

    /**
     * Return the list of children of this channel. The children list is returned as array
     * identified by the child id as key and with the following elements for each key:
     *  - title - The channel title
     *  - items - An array containing the channel children (recursive)
     * @return array
     */
    protected static function listChildrenOptions(): array
    {
        $channel = self::getNested();

        $iterator = function($children) use (&$iterator) {
            $result = [];

            foreach ($children as $child) {
                if (!$child->children) {
                    $result[$child->id] = $child->title;
                }
                else {
                    $result[$child->id] = [
                        'title' => $child->title,
                        'items' => $iterator($child->children)
                    ];
                }
            }

            return $result;
        };

        return $iterator($channel);
    }

    /**
     * Handler for the pages.menuitem.resolveItem event.
     * Returns information about a menu item. The result is an array
     * with the following keys:
     * - url - the menu item URL. Not required for menu item types that return all available records.
     *   The URL should be returned relative to the website root and include the subdirectory, if any.
     *   Use the Url::to() helper to generate the URLs.
     * - isActive - determines whether the menu item is active. Not required for menu item types that
     *   return all available records.
     * - items - an array of arrays with the same keys (url, isActive, items) + the title key.
     *   The items array should be added only if the $item's $nesting property value is TRUE.
     * @param \Winter\Pages\Classes\MenuItem $item Specifies the menu item.
     * @param \Cms\Classes\Theme $theme Specifies the current theme.
     * @param string $url Specifies the current page URL, normalized, in lower case
     * The URL is specified relative to the website root, it includes the subdirectory name, if any.
     * @return mixed Returns an array. Returns null if the item cannot be resolved.
     */
    public static function resolveMenuItem($item, $url, $theme)
    {
        $result = null;

        if ($item->type == 'forum-channel') {
            if (!$item->reference || !$item->cmsPage) {
                return;
            }

            $channel = self::find($item->reference);
            if (!$channel) {
                return;
            }

            $pageUrl = self::getChannelPageUrl($item->cmsPage, $channel, $theme);
            if (!$pageUrl) {
                return;
            }

            $pageUrl = Url::to($pageUrl);

            $result = [];
            $result['url'] = $pageUrl;
            $result['isActive'] = $pageUrl == $url;
            $result['mtime'] = $channel->updated_at;

            if ($item->nesting) {
                $channels = $channel->getNested();
                $iterator = function($channels) use (&$iterator, &$item, &$theme, $url) {
                    $branch = [];

                    foreach ($channels as $channel) {
                        $branchItem = [];
                        $branchItem['url'] = self::getChannelPageUrl($item->cmsPage, $channel, $theme);
                        $branchItem['isActive'] = $branchItem['url'] == $url;
                        $branchItem['title'] = $channel->name;
                        $branchItem['mtime'] = $channel->updated_at;

                        if ($channel->children) {
                            $branchItem['items'] = $iterator($channel->children);
                        }

                        $branch[] = $branchItem;
                    }

                    return $branch;
                };

                $result['items'] = $iterator($channels);
            }
        }
        elseif ($item->type == 'all-forum-channels') {
            $result = [
                'items' => []
            ];

            $channels = self::orderBy('title')->get();
            foreach ($channels as $channel) {
                $channelItem = [
                    'title' => $channel->name,
                    'url'   => self::getChannelPageUrl($item->cmsPage, $channel, $theme),
                    'mtime' => $channel->updated_at
                ];

                $channelItem['isActive'] = $channelItem['url'] == $url;

                $result['items'][] = $channelItem;
            }
        }

        return $result;
    }

    /**
     * Returns URL of a channel page.
     *
     * @param $pageCode
     * @param $channel
     * @param $theme
     */
    protected static function getChannelPageUrl($pageCode, $channel, $theme)
    {
        $page = CmsPage::loadCached($theme, $pageCode);
        if (!$page) {
            return;
        }

        $properties = $page->getComponentProperties('forumChannel');
        if (!isset($properties['slug'])) {
            return;
        }

        /*
         * Extract the routing parameter name from the slug filter
         * eg: {{ :someRouteParam }}
         */
        if (!preg_match('/^\{\{([^\}]+)\}\}$/', $properties['slug'], $matches)) {
            return;
        }

        $paramName = substr(trim($matches[1]), 1);
        $url = CmsPage::url($page->getBaseFileName(), [$paramName => $channel->slug]);

        return $url;
    }

   /**
    * Apply embed code to channel.
    */
    public function scopeForEmbed($query, $channel, $code)
    {
        return $query
            ->where('embed_code', $code)
            ->where('parent_id', $channel->id);
    }

    /**
     * Auto creates a channel based on embed code and a parent channel
     * @param  string $code          Embed code
     * @param  string $parentChannel Channel to create the topic in
     * @param  string $title         Title for the channel (if created)
     * @return self
     */
    public static function createForEmbed($code, $parentChannel, $title = null, $isGuarded = false)
    {
        $channel = self::forEmbed($parentChannel, $code)->first();

        if (!$channel) {
            $channel = new self;
            $channel->title = $title;
            $channel->embed_code = $code;
            $channel->parent = $parentChannel;
            $channel->is_guarded = $isGuarded;
            $channel->save();
        }

        return $channel;
    }

    /**
     * Rebuilds the statistics for the channel
     * @return void
     */
    public function rebuildStats()
    {
        $this->count_topics = $this->topics()->count();
        $this->count_posts = $this->topics()->sum('count_posts');

        return $this;
    }

    /**
     * Filters if the channel should be visible on the front-end.
     */
    public function scopeIsVisible($query)
    {
        return $query->where('is_hidden', '<>', true);
    }

    public function afterDelete()
    {
        foreach ($this->topics as $topic) {
            $topic->delete();
        }
    }

    /**
     * Sets the "url" attribute with a URL to this object
     * @param string $pageName
     * @param Cms\Classes\Controller $controller
     */
    public function setUrl($pageName, $controller)
    {
        $params = [
            'id'   => $this->id,
            'slug' => $this->slug,
        ];

        return $this->url = $controller->pageUrl($pageName, $params);
    }
}
