<?php

echo $view->header()->setAttribute('template', 'Date and time configuration');

echo $view->selector('timezone', $view::SELECTOR_DROPDOWN);

echo $view->dateInput('date');
echo $view->textInput('time');

echo $view->fieldsetSwitch('status', 'disabled');
echo $view->fieldsetSwitch('status', 'enabled')
    ->insert($view->textInput('server'));

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);
