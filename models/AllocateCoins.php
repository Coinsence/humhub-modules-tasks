<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2020 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

namespace humhub\modules\tasks\models;

use humhub\modules\xcoin\models\Account;
use humhub\modules\xcoin\models\Asset;
use humhub\modules\xcoin\models\Transaction;
use yii\base\Model;
use Yii;
use yii\web\HttpException;

/**
 * Class AllocateCoins
 */
class AllocateCoins extends Model
{

    /**
     * @var Account
     */
    public $fromAccount;

    /**
     * @var Account
     */
    public $toAccount;

    /**
     * @var Task
     */
    public $task;

    /**
     * @var int
     */
    public $amount;

    /**
     * @var int
     */
    public $asset_id;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['amount', 'asset_id'], 'required'],
            ['amount', 'number', 'min' => 0.1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'amount' => Yii::t('TasksModule.models_allocate_coins', 'Amount'),
            'asset_id' => Yii::t('TasksModule.models_allocate_coins', 'Coin'),
        ];
    }

    public function getMaxAmount()
    {
        return $this->fromAccount->getAssetBalance(Asset::findOne(['id' => $this->asset_id]));
    }

    public function checkBalance()
    {
        if ($this->amount > $this->getMaxAmount()) {
            $this->addError('amount', Yii::t('TasksModule.models_allocate_coins', 'Insufficient balance for select coin'));

            return false;
        }

        return true;
    }

    public function allocateCoins()
    {
        if (!($this->validate() && $this->checkBalance())) {
            return false;
        }

        $transaction = new Transaction();
        $transaction->transaction_type = Transaction::TRANSACTION_TYPE_TRANSFER;
        $transaction->asset_id = $this->asset_id;
        $transaction->to_account_id = $this->toAccount->id;
        $transaction->from_account_id = $this->fromAccount->id;
        $transaction->amount = $this->amount;
        $transaction->comment = Yii::t('TasksModule.models_allocate_coins', 'Task Coins Allocations');
        if (!$transaction->save()) {
            throw new HttpException(Yii::t('TasksModule.models_allocate_coins', 'Transaction failed!'));
        }

        return true;
    }

}
