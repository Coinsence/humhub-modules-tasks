<?php

use humhub\modules\tasks\models\account\TaskAccount;
use humhub\modules\tasks\models\Task;
use humhub\modules\user\models\User;
use humhub\modules\xcoin\models\Account;
use yii\db\Migration;

/**
 * Class m190222_231557_tasks_accounts_bulk_insert
 *
 * This migration file is introduced in order to create accounts
 * for already existing tasks.
 *
 */
class m190222_231557_tasks_accounts_bulk_insert extends Migration
{
    /**
     * {@inheritdoc}
     * @throws \yii\db\Exception
     */
    public function safeUp()
    {
        $tasks = Task::find()->all();

        if (count($tasks)) {
            $accountModel = ['title', 'space_id', 'account_type', 'user_id'];
            $taskAccountModel = ['task_id', 'account_id', 'account_type', 'created_at', 'updated_at'];

            $processedTasks = [];
            $accountRows = [];
            $taskAccountRows = [];

            foreach ($tasks as $task) {
                if (!$task->hasAccount()) {
                    $accountRows [] = [
                        'title' => "Task#$task->id ( $task->title )",
                        'space_id' => $task->content->container->id,
                        'account_type' => Account::TYPE_TASK,
                        'user_id' => empty($this->responsibleUsers) ? null : User::findOne([
                            'guid' => $this->responsibleUsers[0]
                        ])->id,
                    ];

                    array_push($processedTasks, $task);
                }
            }

            // bulk insert for accounts
            Yii::$app->db->createCommand()
                ->batchInsert(Account::tableName(), $accountModel, $accountRows)
                ->execute();

            $batchFirstInsertedId = Yii::$app->db->getLastInsertID();

            foreach ($processedTasks as $task) {
                $now = date('Y-m-d H:i:s');
                $taskAccountRows [] = [
                    'task_id' => $task->id,
                    'account_id' => $batchFirstInsertedId++,
                    'account_type' => Task::ACCOUNT_SPACE,
                    'created_at' => $now,
                    'updated_at' => $now
                ];
            }

            // bulk insert for task accounts
            Yii::$app->db->createCommand()
                ->batchInsert(TaskAccount::tableName(), $taskAccountModel, $taskAccountRows)
                ->execute();
        }
    }
}
