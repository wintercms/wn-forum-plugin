<div data-control="toolbar">
    <a
        href="<?= Backend::url('winter/forum/channels/create') ?>"
        class="btn btn-primary wn-icon-plus">
        <?= e(trans('winter.forum::lang.controllers.channels.new')) ?>
    </a>
    <button
        class="btn btn-default wn-icon-trash-o"
        disabled="disabled"
        onclick="$(this).data('request-data', {
            checked: $('.control-list').listWidget('getChecked')
        })"
        data-request="onDelete"
        data-request-confirm="<?= e(trans('winter.forum::lang.controllers.channels.delete_selected_confirm')) ?>"
        data-trigger-action="enable"
        data-trigger=".control-list input[type=checkbox]"
        data-trigger-condition="checked"
        data-stripe-load-indicator>
        <?= e(trans('winter.forum::lang.controllers.channels.delete')) ?>
    </button>
    <a
        href="<?= Backend::url('winter/forum/channels/reorder') ?>"
        class="btn btn-default wn-icon-bars">
        <?= e(trans('winter.forum::lang.controllers.channels.reorder')) ?>
    </a>
</div>
