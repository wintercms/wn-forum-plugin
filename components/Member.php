<?php

namespace Winter\Forum\Components;

use Exception;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Winter\Forum\Classes\ForumBaseComponent;
use Winter\Forum\Models\Member as MemberModel;
use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Support\Facades\Flash;
use Winter\Storm\Support\Facades\Mail;
use Winter\User\Facades\Auth;
use Winter\User\Models\MailBlocker;
use Winter\User\Models\User as UserModel;

class Member extends ForumBaseComponent
{
    /**
     * @var \Winter\Forum\Models\Member Member cache
     */
    protected $member = null;

    /**
     * @var \Winter\Forum\Models\Member Other member cache
     */
    protected $otherMember = null;

    /**
     * @var array Mail preferences cache
     */
    protected $mailPreferences = null;

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
            'name'        => 'winter.forum::lang.components.member.name',
            'description' => 'winter.forum::lang.components.member.description'
        ];
    }

    /**
     * Returns the properties provided by the component
     */
    public function defineProperties()
    {
        return [
            'slug' => [
                'title'       => 'winter.forum::lang.components.member.properties.slug_name',
                'description' => 'winter.forum::lang.components.member.properties.slug_desc',
                'default'     => '{{ :slug }}',
                'type'        => 'string'
            ],
            'viewMode' => [
                'title'       => 'winter.forum::lang.components.member.properties.view_title',
                'description' => 'winter.forum::lang.components.member.properties.view_desc',
                'type'        => 'dropdown',
                'options'     => 'winter.forum::lang.components.member.properties.view_options',
                'default'     => ''
            ],
            'channelPage' => [
                'title'       => 'winter.forum::lang.components.general.properties.channel_page',
                'description' => 'winter.forum::lang.components.general.properties.channel_page_desc',
                'type'        => 'dropdown',
                'default'     => 'forum/channel',
                'group'       => 'winter.forum::lang.components.general.groups.links',
            ],
            'topicPage' => [
                'title'       => 'winter.forum::lang.components.member.properties.topic_title',
                'description' => 'winter.forum::lang.components.member.properties.topic_desc',
                'type'        => 'dropdown',
                'default'     => 'forum/topic',
                'group'       => 'winter.forum::lang.components.general.groups.links',
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
    }

    protected function prepareVars()
    {
        $this->page['member'] = $this->getMember();
        $this->page['otherMember'] = $this->getOtherMember();
        $this->page['mailPreferences'] = $this->getMailPreferences();
        $this->page['canEdit'] = $this->canEdit();
        $this->page['mode'] = $this->getMode();

        /*
         * Page links
         */
        $this->setVar('topicPage', $this->property('topicPage'));
        $this->setVar('channelPage', $this->property('channelPage'));
    }

    public function getRecentPosts()
    {
        $member = $this->getMember();
        $posts = $member->posts()->with('topic')->limit(10)->get();

        $posts->each(function ($post) {
            $post->topic->setUrl($this->topicPage, $this->controller);
        });

        return $posts;
    }

    /**
     * Get the current member based on the slug or logged-in user.
     *
     * @return \Winter\Forum\Models\Member|null
     */
    public function getMember()
    {
        if ($this->member !== null) {
            return $this->member;
        }

        if (!$slug = $this->property('slug')) {
            $member = MemberModel::getFromUser();
        }
        else {
            $member = MemberModel::whereSlug($slug)->first();
        }

        return $this->member = $member;
    }

    /**
     * Get the currently logged-in forum member.
     *
     * @return \Winter\Forum\Models\Member|null
     */
    public function getOtherMember()
    {
        if ($this->otherMember !== null) {
            return $this->otherMember;
        }

        return $this->otherMember = MemberModel::getFromUser();
    }

    /**
     * Get the mail preferences for the current logged-in member.
     *
     * @return array
     */
    public function getMailPreferences()
    {
        if ($this->mailPreferences !== null) {
            return $this->mailPreferences;
        }

        $member = $this->getMember();
        if (!$member || !$member->user) {
            return [];
        }

        $preferences = [];
        $blocked = MailBlocker::checkAllForUser($member->user);
        foreach ($this->getMailTemplates() as $alias => $template) {
            $preferences[$alias] = !in_array($template, $blocked);
        }

        return $this->mailPreferences = $preferences;
    }

    /**
     * Get the current view mode for the member component.
     *
     * @return string
     */
    public function getMode()
    {
        return $this->property('viewMode') ?: input('mode', 'view');
    }

    /**
     * Determine if the current member can edit their profile.
     *
     * @return bool
     */
    public function canEdit()
    {
        if ($this->property('viewMode') == 'view') {
            return false;
        }

        if (!$member = $this->getMember()) {
            return false;
        }

        return $member->canEdit(MemberModel::getFromUser());
    }

    /**
     * Handle the update action for the member component.
     *
     * @return void
     */
    public function onUpdate()
    {
        try {
            if (!$this->canEdit()) {
                throw new ApplicationException('Permission denied.');
            }

            $member = $this->getMember();
            if (!$member) {
                return;
            }

            /*
             * Process mail preferences
             */
            if ($member->user) {
                MailBlocker::setPreferences(
                    $member->user,
                    post('MailPreferences'),
                    ['aliases' => $this->getMailTemplates()]
                );
            }

            /*
             * Save member
             */
            $data = array_except(post(), 'MailPreferences');
            $member->fill($data);
            $member->save();

            Flash::success(post('flash', 'Settings successfully saved!'));

            return $this->redirectToSelf();
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }
    }

    /**
     * Get the mail templates used for the member component.
     *
     * @return array
     */
    protected function getMailTemplates()
    {
        return ['topic_reply' => 'winter.forum::mail.topic_reply'];
    }

    /**
     * Handle the purge posts action for the member component.
     *
     * @return void
     */
    public function onPurgePosts()
    {
        try {
            $otherMember = $this->getOtherMember();
            if (!$otherMember || !$otherMember->is_moderator) {
                throw new ApplicationException('Access denied');
            }

            if ($member = $this->getMember()) {
                foreach ($member->posts as $post) {
                    $post->delete();
                }
            }

            Flash::success(post('flash', 'Posts deleted!'));

            return $this->redirectToSelf();
        }
        catch (Exception $ex) {
            Flash::error($ex->getMessage());
        }
    }

    /**
     * Handle the approve action for the member component.
     *
     * @return void
     */
    public function onApprove()
    {
        try {
            $otherMember = $this->getOtherMember();
            if (!$otherMember || !$otherMember->is_moderator) {
                throw new ApplicationException('Access denied');
            }

            if ($member = $this->getMember()) {
                $member->approveMember();
            }

            $this->prepareVars();
        }
        catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    /**
     * Handle the ban action for the member component.
     *
     * @return void
     */
    public function onBan()
    {
        try {
            $otherMember = $this->getOtherMember();
            if (!$otherMember || !$otherMember->is_moderator) {
                throw new ApplicationException('Access denied');
            }

            if ($member = $this->getMember()) {
                $member->banMember();
            }

            $this->prepareVars();
        }
        catch (Exception $ex) {
            if (Request::ajax()) {
                throw $ex;
            } else {
                Flash::error($ex->getMessage());
            }
        }
    }

    /**
     * Handle the report action for the member component.
     *
     * @return void
     */
    public function onReport()
    {
        if (!Auth::check()) {
            throw new ApplicationException('You must be logged in to perform this action!');
        }

        Flash::success(post('flash', 'User has been reported for spamming, thank-you for your assistance!'));

        $moderators = UserModel::whereHas('forum_member', function ($member) {
            $member->where('is_moderator', true);
        })->lists('name', 'email');

        if ($moderators) {
            $member = $this->getMember();
            $memberUrl = $this->currentPageUrl(['slug' => $member->slug]);
            $otherMember = $this->getOtherMember();
            $otherMemberUrl = $this->currentPageUrl(['slug' => $otherMember->slug]);
            $params = [
                'member'         => $member,
                'memberUrl'      => $memberUrl,
                'otherMember'    => $otherMember,
                'otherMemberUrl' => $otherMemberUrl,
            ];
            Mail::sendTo($moderators, 'winter.forum::mail.member_report', $params);
        }

        return $this->redirectToSelf();
    }

    /**
     * Redirect the user to the current member page.
     *
     * @return \Illuminate\Http\RedirectResponse|false
     */
    protected function redirectToSelf()
    {
        if (!$member = $this->getMember()) {
            return false;
        }

        /*
         * Redirect to the intended page after successful update
         */
        $redirectUrl = post('redirect', $this->currentPageUrl([
            'slug' => $member->slug
        ]));

        return Redirect::to($redirectUrl);
    }
}
