<?php namespace Winter\Forum\Components;

use Auth;
use Request;
use Redirect;
use Cms\Classes\Page;
use Cms\Classes\ComponentBase;
use Winter\Forum\Models\Topic as TopicModel;
use Winter\Forum\Models\Member as MemberModel;
use Winter\Forum\Classes\TopicTracker;

/**
 * Topic list component
 *
 * Displays a list of all topics.
 */
class Topics extends ComponentBase
{
    /**
     * @var Winter\Forum\Models\Member Member cache
     */
    protected $member = null;

    /**
     * @var string Reference to the page name for linking to members.
     */
    public $memberPage;

    /**
     * @var string Reference to the page name for linking to topics.
     */
    public $topicPage;

    /**
     * @var int Number of topics to display per page.
     */
    public $topicsPerPage;

    public function componentDetails()
    {
        return [
            'name'        => 'winter.forum::lang.topics.component_name',
            'description' => 'winter.forum::lang.topics.component_description',
        ];
    }

    public function defineProperties()
    {
        return [
            'memberPage' => [
                'title'       => 'winter.forum::lang.member.page_name',
                'description' => 'winter.forum::lang.member.page_help',
                'type'        => 'dropdown'
            ],
            'topicPage' => [
                'title'       => 'winter.forum::lang.topic.page_name',
                'description' => 'winter.forum::lang.topic.page_help',
                'type'        => 'dropdown',
            ],
            'topicsPerPage' =>  [
                'title'             => 'winter.forum::lang.topics.per_page',
                'type'              => 'string',
                'validationPattern' => '^[0-9]+$',
                'validationMessage' => 'winter.forum::lang.topics.per_page_validation',
                'default'           => '20',
            ],
            'includeStyles' => [
                'title'       => 'winter.forum::lang.components.general.properties.includeStyles',
                'description' => 'winter.forum::lang.components.general.properties.includeStyles_desc',
                'type'        => 'checkbox',
                'default'     => true
            ],
        ];
    }

    public function getPropertyOptions($property)
    {
        return Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }

    public function onRun()
    {
        if ($this->property('includeStyles', true)) {
            $this->addCss('assets/css/forum.css');
        }

        $this->prepareVars();

        return $this->prepareTopicList();
    }

    protected function prepareVars()
    {
        /*
         * Page links
         */
        $this->topicPage = $this->page['topicPage'] = $this->property('topicPage');
        $this->memberPage = $this->page['memberPage'] = $this->property('memberPage');
        $this->topicsPerPage = $this->page['topicsPerPage'] = $this->property('topicsPerPage');
    }

    protected function prepareTopicList()
    {
        $currentPage = input('page');
        $searchString = trim(input('search'));
        $topics = TopicModel::with('last_post_member')->listFrontEnd([
            'page'    => $currentPage,
            'perPage' => $this->topicsPerPage,
            'sort'    => 'updated_at',
            'search'  => $searchString,
        ]);

        /*
         * Add a "url" helper attribute for linking to each topic
         */
        $topics->each(function($topic) {
            $topic->setUrl($this->topicPage, $this->controller);

            if ($topic->last_post_member) {
                $topic->last_post_member->setUrl($this->memberPage, $this->controller);
            }

            if ($topic->start_member) {
                $topic->start_member->setUrl($this->memberPage, $this->controller);
            }
        });

        /*
         * Signed in member
         */
        $this->page['member'] = $this->member = MemberModel::getFromUser();

        if ($this->member) {
            $this->member->setUrl($this->memberPage, $this->controller);
            $topics = TopicTracker::instance()->setFlagsOnTopics($topics, $this->member);
        }

        $this->page['topics'] = $this->topics = $topics;

        /*
         * Pagination
         */
        if ($topics) {
            $queryArr = [];
            if ($searchString) {
                $queryArr['search'] = $searchString;
            }
            $queryArr['page'] = '';
            $paginationUrl = Request::url() . '?' . http_build_query($queryArr);

            if ($currentPage > ($lastPage = $topics->lastPage()) && $currentPage > 1) {
                return Redirect::to($paginationUrl . $lastPage);
            }

            $this->page['paginationUrl'] = $paginationUrl;
        }
    }
}
