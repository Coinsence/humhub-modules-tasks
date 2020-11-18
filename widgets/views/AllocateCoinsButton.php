<?php
/**
 * @link https://coinsence.org/
 * @copyright Copyright (c) 2020 Coinsence
 * @license https://www.humhub.com/licences
 *
 * @author Daly Ghaith <daly.ghaith@gmail.com>
 */

use humhub\modules\tasks\models\Task;
use humhub\widgets\Button;

/* @var $task Task */
/* @var $actionLabel string */
/* @var $icon string */
/* @var $url string */
/* @var $handler string */

?>

<div class="btn-group pull-right" style="margin-right: 5px">
    <?= Button::primary($actionLabel)->action($handler, $url)->sm()->icon($icon)->loader(true); ?>
</div>
