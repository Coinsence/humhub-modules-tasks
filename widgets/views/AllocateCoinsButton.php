<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2020 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

use humhub\modules\tasks\models\Task;
use humhub\modules\tasks\widgets\AllocateCoinsButton;
use humhub\widgets\Button;

/* @var $task Task */
/* @var $actionLabel string */
/* @var $icon string */
/* @var $url string */
/* @var $handler string */
/* @var $action int */

?>

<div class="btn-group pull-right" style="margin-right: 5px">
    <?php if ($action == AllocateCoinsButton::ACTION_CREATE_ACCOUNT) : ?>
        <?= Button::primary($actionLabel)->action($handler, $url)->sm()->icon($icon)->loader(true); ?>
    <?php else : ?>
        <?= Button::primary($actionLabel)->link($url)->options(['data-target' => '#globalModal'])->sm()->icon($icon)->loader(false); ?>
    <?php endif; ?>
</div>
