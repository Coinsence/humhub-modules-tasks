<?php
/* @var $this \humhub\components\View */

use humhub\modules\tasks\helpers\TaskUrl;
use humhub\widgets\Button;

/* @var $task \humhub\modules\tasks\models\Task */
/* @var $accounts array */

?>

<div class="btn-group pull-right task-change-state-button">
    <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="caret"></span>
        <span class="sr-only">Toggle Dropdown</span>
        Choose Payment Account
    </button>
    <ul class="dropdown-menu">
        <?php foreach($accounts as $account) : ?>
            <li>
                <?= Button::asLink($account->title)->action('task.chooseWorkerAccount', TaskUrl::chooseWorkerAccount($task, $account->id));?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
