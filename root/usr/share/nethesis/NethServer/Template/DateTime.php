<?php

echo $view->header('current_datetime')->setAttribute('template', $T('DateTime_header'));

echo $view->selector('timezone', $view::SELECTOR_DROPDOWN);

echo $view->fieldsetSwitch('status', 'disabled')
    ->setAttribute('label', $T('Status_disabled_label'))
    ->insert($view->dateInput('date'))
    ->insert($view->textInput('time'));

echo $view->fieldsetSwitch('status', 'enabled')
    ->setAttribute('label', $T('Status_enabled_label'))
    ->insert($view->textInput('server'));

include 'WizFooter.php';

