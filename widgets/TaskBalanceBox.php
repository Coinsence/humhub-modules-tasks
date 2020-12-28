<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2020 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\tasks\widgets;

use humhub\modules\tasks\models\Task;
use Yii;

class TaskBalanceBox extends TaskInfoBox
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
        return $this->render('taskBalanceBox', [
            'title' =>  Yii::t('TasksModule.base', 'Balance'),
            'icon' => 'fa-money',
            'account' => $this->task->getAccount(Task::ACCOUNT_SPACE),
        ]);
    }
}