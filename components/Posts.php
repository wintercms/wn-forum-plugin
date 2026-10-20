<?php

namespace Winter\Forum\Components;

use Exception;
use Winter\Forum\Classes\ForumBaseComponent;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Winter\Forum\Models\Member as MemberModel;
use Winter\Forum\Models\Post as PostModel;
use Winter\Storm\Exception\ApplicationException;
use Winter\Storm\Support\Facades\Flash;

/**
 * Post list component
 *
 * Displays a list of all posts.
 */
class Posts extends ForumBaseComponent
{
    /**
     * @var \Winter\Forum\Models\Member Authenticated member cache
     */
    protected $member = null;

    /**
     * @var \Winter\Forum\Models\Member Other member cache
     */
    protected $otherMember = null;

    /**
     * @var string Reference to the page name for linking to members.
     */
    public $memberPage;

    /**
     * @var string Reference to the page name for linking to topics.
     */
    public $topicPage;

    /**
     * @var int Number of posts to display per page.
     */
    public $postsPerPage;

    /**
     * Gets the details for the component
     */
    public function componentDetails()
    {
        return [
            'name'        => 'winter.forum::lang.components.posts.name',
            'description' => 'winter.forum::lang.components.posts.description',
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
                'group'       => 'winter.forum::lang.components.general.groups.links',
            ],
            'topicPage' => [
                'title'       => 'winter.forum::lang.components.general.properties.topic_page',
                'description' => 'winter.forum::lang.components.general.properties.topic_page_desc',
                'type'        => 'dropdown',
                'default'     => 'forum/topic',
                'group'       => 'winter.forum::lang.components.general.groups.links',
            ],
            'postsPerPage' =>  [
                'title'             => 'winter.forum::lang.components.general.properties.posts_per_page',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'winter.forum::lang.components.general.properties.posts_per_page_validation',
                'default'           => '20',
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

        return $this->preparePostList();
    }

    protected function prepareVars()
    {
        $this->page['otherMember'] = $this->getOtherMember();

        /*
         * Page links
         */
        $this->setVar('topicPage', $this->property('topicPage'));
        $this->setVar('memberPage', $this->property('memberPage'));
        $this->setVar('postsPerPage', $this->property('postsPerPage'));
    }

    /**
     * Prepares the list of posts for display on the page. This includes handling pagination,
     * search filtering, and setting URLs for related topics and members.
     *
     * @return void
     */
    protected function preparePostList()
    {
        $currentPage = (int) input('page');
        $searchString = trim(input('search'));

        $posts = PostModel::with('member', 'topic');
        $posts = $posts->whereHas('member', function ($member) {
            $member->where('is_approved', false);
            $member->where('is_banned', false);
        });

        $posts = $posts->listFrontEnd([
            'page'      => $currentPage,
            'perPage'   => $this->postsPerPage,
            'sort'      => 'created_at',
            'direction' => 'desc',
            'search'    => $searchString,
        ]);

        /*
         * Add a "url" helper attribute for linking to each topic
         */
        $posts->each(function ($post) {
            if ($post->topic) {
                $post->topic->setUrl($this->topicPage, $this->controller);
            }

            if ($post->member) {
                $post->member->setUrl($this->memberPage, $this->controller);
            }
        });

        /*
         * Signed in member
         */
        $this->page['member'] = $this->member = MemberModel::getFromUser();

        if ($this->member) {
            $this->member->setUrl($this->memberPage, $this->controller);
        }

        $this->page['posts'] = $this->posts = $posts;

        /*
         * Pagination
         */
        if ($posts) {
            $queryArr = [];
            if ($searchString) {
                $queryArr['search'] = $searchString;
            }
            $queryArr['page'] = '';
            $paginationUrl = Request::url() . '?' . http_build_query($queryArr);

            if ($currentPage > ($lastPage = $posts->lastPage()) && $currentPage > 1) {
                return Redirect::to($paginationUrl . $lastPage);
            }

            $this->page['paginationUrl'] = $paginationUrl;
        }
    }

    /**
     * Prepares the post list for AJAX updates.
     *
     * @return array
     */
    public function onApprove()
    {
        try {
            $otherMember = $this->getOtherMember();
            if (!$otherMember || !$otherMember->is_moderator) {
                throw new ApplicationException('Access denied');
            }

            $post = PostModel::find(post('post'));

            if (!$post || !$post->canEdit()) {
                throw new ApplicationException('Permission denied.');
            }

            if ($member = $post->member) {
                $member->approveMember();
            }

            $this->prepareVars();

            return $this->preparePostList();
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
     * Flags a post as spam and deletes all posts by the member.
     *
     * @return array
     */
    public function onFlagSpam()
    {
        try {
            $otherMember = $this->getOtherMember();
            if (!$otherMember || !$otherMember->is_moderator) {
                throw new ApplicationException('Access denied');
            }

            $post = PostModel::find(post('post'));

            if (!$post || !$post->canEdit()) {
                throw new ApplicationException('Permission denied.');
            }

            if ($member = $post->member) {
                foreach ($member->posts as $post) {
                    $post->delete();
                }

                $member->banMember();
            }

            $this->prepareVars();

            return $this->preparePostList();
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
     * Retrieves the currently logged-in member, if available.
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
}
