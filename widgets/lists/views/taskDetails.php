<?php

use humhub\components\View;
use humhub\modules\content\widgets\richtext\RichText;
use humhub\modules\content\widgets\WallEntryAddons;
use humhub\modules\tasks\helpers\TaskUrl;
use humhub\modules\tasks\models\Task;
use humhub\modules\tasks\widgets\AllocateCoinsButton;
use humhub\modules\tasks\widgets\ChangeStatusButton;
use humhub\modules\tasks\widgets\PaymentAccountBox;
use humhub\modules\tasks\widgets\TaskBalanceBox;
use humhub\modules\tasks\widgets\TaskInfoBox;
use humhub\modules\tasks\widgets\checklist\TaskChecklist;
use humhub\modules\tasks\widgets\TaskRoleInfoBox;
use humhub\widgets\Button;

/* @var $this View */
/* @var $task Task */

$scheduleTextClass = '';

if (($task->schedule->isOverdue())) {
    $scheduleTextClass = 'colorDanger';
}
?>

<div class="task-list-task-details">

    <div class="task-list-task-details-body clearfix">


        <div class="task-list-task-infos">
            <?= TaskRoleInfoBox::widget(['task' => $task]) ?>

            <?= TaskInfoBox::widget([
                'title' => Yii::t('TasksModule.base', 'Scheduling'),
                'value' => $task->schedule->getFormattedDateTime(),
                'icon' => 'fa-clock-o',
                'textClass' => $scheduleTextClass])
            ?>

            <?php if ($task->status != Task::STATUS_COMPLETED): ?>
                <?= TaskBalanceBox::widget(['task' => $task]) ?>
            <?php endif; ?>

            <?php if ($task->schedule->canRequestExtension()): ?>
                <div style="display:inline-block;vertical-align:bottom;">
                    <?= Button::primary()->icon('fa-calendar-plus-o')->xs()->cssClass('tt')->link(TaskUrl::requestExtension($task))->options(['title' => Yii::t('TasksModule.base', 'Request extension')]) ?>
                </div>
            <?php endif; ?>

            <?= ChangeStatusButton::widget(['task' => $task]) ?>
            <?= AllocateCoinsButton::widget(['task' => $task]) ?>

        </div>

        <?php if (!empty($task->description)) : ?>
            <div class="task-details-body">
                <?= RichText::output($task->description) ?>
            </div>
        <?php endif; ?>

        <?php if ($task->hasItems()) : ?>
            <div class="task-details-body">
                <?= TaskChecklist::widget(['task' => $task]) ?>
            </div>
        <?php endif; ?>

    </div>

    <?= WallEntryAddons::widget(['object' => $task]); ?>
</div>
