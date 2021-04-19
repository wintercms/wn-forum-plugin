<?php namespace Winter\Forum;

use Event;
use Backend;
use Winter\User\Models\User;
use Winter\Forum\Models\Member;
use System\Classes\PluginBase;
use Winter\User\Controllers\Users as UsersController;

/**
 * Forum Plugin Information File
 */
class Plugin extends PluginBase
{
    public $require = ['Winter.User'];

    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'winter.forum::lang.plugin.name',
            'description' => 'winter.forum::lang.plugin.description',
            'author'      => 'Winter CMS',
            'icon'        => 'icon-comments',
            'homepage'    => 'https://github.com/wintercms/wn-forum-plugin',
            'replaces'    => ['RainLab.Forum' => '<= 1.2.2'],
        ];
    }

    public function boot()
    {
        User::extend(function($model) {
            $model->hasOne['forum_member'] = ['Winter\Forum\Models\Member'];

            $model->bindEvent('model.beforeDelete', function() use ($model) {
                $model->forum_member && $model->forum_member->delete();
            });
        });

        UsersController::extendFormFields(function($widget, $model, $context) {
            // Prevent extending of related form instead of the intended User form
            if (!$widget->model instanceof \Winter\User\Models\User) {
                return;
            }
            if ($context != 'update') {
                return;
            }
            if (!Member::getFromUser($model)) {
                return;
            }

            $widget->addFields([
                'forum_member[username]' => [
                    'label'   => 'winter.forum::lang.settings.username',
                    'tab'     => 'Forum',
                    'comment' => 'winter.forum::lang.settings.username_comment'
                ],
                'forum_member[is_moderator]' => [
                    'label'   => 'winter.forum::lang.settings.moderator',
                    'type'    => 'checkbox',
                    'tab'     => 'Forum',
                    'span'    => 'auto',
                    'comment' => 'winter.forum::lang.settings.moderator_comment'
                ],
                'forum_member[is_banned]' => [
                    'label'   => 'winter.forum::lang.settings.banned',
                    'type'    => 'checkbox',
                    'tab'     => 'Forum',
                    'span'    => 'auto',
                    'comment' => 'winter.forum::lang.settings.banned_comment'
                ]
            ], 'primary');
        });

        UsersController::extendListColumns(function($widget, $model) {
            if (!$model instanceof \Winter\User\Models\User) {
                return;
            }

            $widget->addColumns([
                'forum_member_username' => [
                    'label'      => 'winter.forum::lang.settings.forum_username',
                    'relation'   => 'forum_member',
                    'select'     => 'username',
                    'searchable' => false,
                    'invisible'  => true
                ]
            ]);
        });
    }

    public function registerComponents()
    {
        return [
           '\Winter\Forum\Components\Channels'     => 'forumChannels',
           '\Winter\Forum\Components\Channel'      => 'forumChannel',
           '\Winter\Forum\Components\Topic'        => 'forumTopic',
           '\Winter\Forum\Components\Topics'       => 'forumTopics',
           '\Winter\Forum\Components\Posts'        => 'forumPosts',
           '\Winter\Forum\Components\Member'       => 'forumMember',
           '\Winter\Forum\Components\EmbedTopic'   => 'forumEmbedTopic',
           '\Winter\Forum\Components\EmbedChannel' => 'forumEmbedChannel',
           '\Winter\Forum\Components\RssFeed'      => 'forumRssFeed'
        ];
    }

    public function registerPermissions()
    {
        return [
            'winter.forum::lang.settings.channels' => [
                'tab'   => 'winter.forum::lang.settings.channels',
                'label' => 'winter.forum::lang.settings.channels_desc'
            ]
        ];
    }

    public function registerSettings()
    {
        return [
            'settings' => [
                'label'       => 'winter.forum::lang.settings.channels',
                'description' => 'winter.forum::lang.settings.channels_desc',
                'icon'        => 'icon-comments',
                'url'         => Backend::url('winter/forum/channels'),
                'category'    => 'winter.forum::lang.plugin.name',
                'order'       => 500,
                'permissions' => ['winter.forum::lang.settings.channels'],
            ]
        ];
    }

    public function registerMailTemplates()
    {
        return [
            'winter.forum::mail.topic_reply'   => 'Notification to followers when a post is made to a topic.',
            'winter.forum::mail.member_report' => 'Notification to moderators when a member is reported to be a spammer.'
        ];
    }
}
