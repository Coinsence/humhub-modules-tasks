<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2018 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\tasks\models\account;

use humhub\modules\tasks\models\Task;
use humhub\modules\xcoin\models\Account;
use humhub\components\ActiveRecord;

/**
 * This is the model class for table "task_account".
 *
 * The followings are the available columns in table 'task_account':
 * @property integer $id
 * @property integer $task_id
 * @property integer $account_id
 * @property integer $account_type
 */

class TaskAccount extends ActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public static function tableName()
    {
        return 'task_account';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return [
            [['task_id', 'account_type'], 'required'],
            [['task_id', 'account_id', 'account_type'], 'integer'],
        ];
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task_id' => 'Task',
            'account_id' => 'Account',
            'account_type' => 'Account Type'
        ];
    }

    public function getAccount()
    {
        if ($this->account_id) {
            return Account::findOne(['id' => $this->account_id]);
        }
        return null;
    }

    public function getTask()
    {
        return $this->hasOne(Task::class, ['id' => 'task_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
    }
}
