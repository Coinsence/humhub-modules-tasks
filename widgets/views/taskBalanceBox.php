<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2020 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

use humhub\modules\space\widgets\Image;
use humhub\modules\xcoin\models\Account;

/* @var $title string */
/* @var $icon string */
/* @var $account Account */
?>
<div class="task-info">
    <strong><i class="fa <?= $icon ?>"></i> <?= $title ?></strong>
    <br>
    <?php if ($account and count($account->getAssets())) : ?>
        <div>
            <?php foreach ($account->getAssets() as $asset) : ?>
                <strong> <?= $account->getAssetBalance($asset) ?> </strong>
                <?= Image::widget(['space' => $asset->space, 'width' => 20, 'showTooltip' => true, 'link' => true]) ?>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <span class="task-info-text">
            <small>
           <?= Yii::t('TasksModule.views_balance', 'No coins allocated') ?>
            </small>
        </span>
    <?php endif; ?>
</div>
