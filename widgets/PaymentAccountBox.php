<?php

namespace humhub\modules\tasks\widgets;

use humhub\modules\tasks\models\Task;

class PaymentAccountBox extends TaskInfoBox
{
    /**
     * @var Task
     */
    public $task;

    public $icon = 'fa-money';

    public function getTitle()
    {
        return 'Payment Account';
    }

    public function getValue()
    {
        return $this->task->getAccount(Task::WORKER_ACCOUNT)->title;
    }


}
