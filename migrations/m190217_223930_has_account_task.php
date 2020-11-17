<?php

use yii\db\Migration;

/**
 * Class m190217_223930_has_account_task
 */
class m190217_223930_has_account_task extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $this->addColumn('task', 'has_account', 'tinyint(4) NOT NULL');
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190217_223930_has_account_task cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190217_223930_has_account_task cannot be reverted.\n";

        return false;
    }
    */
}
