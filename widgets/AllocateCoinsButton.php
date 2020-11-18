<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2020 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\tasks\widgets;

use humhub\components\Widget;
use humhub\modules\tasks\helpers\TaskUrl;
use humhub\modules\tasks\models\state\CompletedState;
use humhub\modules\tasks\models\Task;
use Yii;

class AllocateCoinsButton extends Widget
{
    /**
     * @var Task
     */
    public $task;

    /**
     * @inheritdoc
     */
    public function run()
    {
        $state = $this->task->state;

        // disable allocate coins action if task is completed
        if ($state instanceof CompletedState) {
            return '';
        }

        if ($this->task->has_account) {
            $actionLabel = 'Allocate Coins';
            $icon = 'fa-money';
            $url = TaskUrl::createTaskAccount($this->task);
            $handler = 'task.allocateCoins';
        } else {
            $actionLabel = 'Create Account';
            $icon = 'fa-plus';
            $url = TaskUrl::createTaskAccount($this->task);
            $handler = 'task.createTaskAccount';
        }

        return $this->render('AllocateCoinsButton', [
            'task' => $this->task,
            'actionLabel' => Yii::t('TasksModule.base', $actionLabel),
            'icon' => $icon,
            'url' => $url,
            'handler' => $handler,
        ]);
    }
}
