<?php namespace Winter\Forum\Controllers;

use Flash;
use BackendMenu;
use Backend\Classes\Controller;
use Winter\Forum\Models\Channel;
use System\Classes\SettingsManager;

/**
 * Channels Back-end Controller
 */
class Channels extends Controller
{
    public $implement = [
        'Backend.Behaviors.FormController',
        'Backend.Behaviors.ListController',
        'Backend.Behaviors.ReorderController'
    ];

    public $formConfig = 'config_form.yaml';
    public $listConfig = 'config_list.yaml';
    public $reorderConfig = 'config_reorder.yaml';
    
    public $requiredPermissions = ['winter.forum::lang.settings.channels'];

    public function __construct()
    {
        parent::__construct();

        BackendMenu::setContext('Winter.System', 'system', 'settings');
        SettingsManager::setContext('Winter.Forum', 'settings');
    }

    public function index_onDelete()
    {
        if (($checkedIds = post('checked')) && is_array($checkedIds) && count($checkedIds)) {
            foreach ($checkedIds as $channelId) {
                if (!$channel = Channel::find($channelId)) {
                    continue;
                }

                $channel->delete();
            }

            Flash::success('Successfully deleted those channels.');
        }

        return $this->listRefresh();
    }
}
