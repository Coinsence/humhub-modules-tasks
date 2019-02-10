<?php

use yii\db\Migration;

/**
 * Class m190210_225806_task_account
 */
class m190210_225806_task_account extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->createTable('task_account', array(
            'id' => 'pk',
            'account_id' => 'int(11) NOT NULL',
            'account_type'=> 'tinyint(4) NOT NULL',
            'task_id' => 'int(11) NOT NULL',
            'created_at' => 'datetime NOT NULL',
            'created_by' => 'int(11) NOT NULL',
            'updated_at' => 'datetime NOT NULL',
            'updated_by' => 'int(11) NOT NULL',
        ), '');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190210_225806_task_account cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190210_225806_task_account cannot be reverted.\n";

        return false;
    }
    */
}
