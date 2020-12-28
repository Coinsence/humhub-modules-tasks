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
    const ACTION_CREATE_ACCOUNT = 1;
    const ACTION_ALLOCATE_COINS = 2;

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
            $url = TaskUrl::allocateCoins($this->task);
            $handler = '';
            $action = self::ACTION_ALLOCATE_COINS;
        } else {
            $actionLabel = 'Create Account';
            $icon = 'fa-plus';
            $url = TaskUrl::createTaskAccount($this->task);
            $handler = 'task.createTaskAccount';
            $action = self::ACTION_CREATE_ACCOUNT;
        }

        return $this->render('AllocateCoinsButton', [
            'task' => $this->task,
            'actionLabel' => Yii::t('TasksModule.base', $actionLabel),
            'icon' => $icon,
            'url' => $url,
            'handler' => $handler,
            'action' => $action
        ]);
    }
}
