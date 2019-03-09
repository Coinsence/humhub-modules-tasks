<?php


namespace humhub\modules\tasks\widgets;


use humhub\components\Widget;
use humhub\modules\tasks\models\Task;
use humhub\modules\xcoin\models\Account;
use humhub\widgets\Button;

class ChooseWorkerAccountButton extends Widget
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
        $workerAccounts = Account::findAll([
            'user_id' => $this->task->taskAssignedUsers[0],
            'space_id' => null
        ]);

        return $this->render('chooseWorkerAccountButton', [
            'task' => $this->task,
            'accounts' => $workerAccounts
        ]);
    }

}
