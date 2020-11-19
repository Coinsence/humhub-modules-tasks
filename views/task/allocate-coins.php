<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2020 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

use humhub\modules\tasks\models\AllocateCoins;
use humhub\modules\tasks\models\Task;
use humhub\modules\xcoin\models\Account;
use humhub\modules\xcoin\models\Asset;
use humhub\widgets\ActiveForm;
use humhub\widgets\ModalButton;
use humhub\widgets\ModalDialog;
use humhub\assets\Select2BootstrapAsset;
use humhub\modules\xcoin\widgets\SenderAccountField;
use kartik\widgets\Select2;
use yii\web\JsExpression;

Select2BootstrapAsset::register($this);

/** @var Task $task */
/** @var AllocateCoins $model */
/** @var Account $fromAccount */
/** @var Asset[] $assets */
?>
<?php ModalDialog::begin(['header' => Yii::t('TasksModule.views_index_allocate_coins', '<strong>Allocate Coins</strong>'), 'closable' => false]) ?>
<?php $form = ActiveForm::begin(['id' => 'asset-form']); ?>
<div class="modal-body">
    <?= SenderAccountField::widget(['backRoute' => ['/tasks/task/allocate-coins', 'id' => $task->id, 'container' => $this->context->contentContainer], 'senderAccount' => $fromAccount]); ?>
    <br />

    <div class="row">
        <div class="col-md-6">
            <?= $form->field($model, 'amount')->input('number', ['min' => 1]) ?>
        </div>
        <div class="col-md-6">
            <?=
            $form->field($model, 'asset_id')->widget(Select2::class, [
                'data' => $assets,
                'options' => ['placeholder' => Yii::t('TasksModule.views_index_allocate_coins', 'Select Coin')],
                'theme' => Select2::THEME_BOOTSTRAP,
                'hideSearch' => true,
                'pluginOptions' => [
                    'allowClear' => false,
                    'escapeMarkup' => new JsExpression("function(m) { return m; }"),
                ],
            ]);
            ?>
        </div>
    </div>
</div>

<div class="modal-footer">
    <?= ModalButton::submitModal(null, Yii::t('TasksModule.views_index_allocate_coins', 'Allocate')); ?>
    <?= ModalButton::cancel(); ?>
</div>

<?php ActiveForm::end(); ?>
<?php ModalDialog::end() ?>