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

echo $view->buttonList($view::BUTTON_SUBMIT | $view::BUTTON_HELP);

$moduleUrl = json_encode($view->getModuleUrl("/DateTime?tsonly"));

$view->includeJavascript("
(function ( $ ) {
 
    $(document).ready(function() {
        // reload page after 60 seconds
        window.setInterval(function () {
            $.Nethgui.Server.ajaxMessage({
                isMutation: false,
                url: $moduleUrl
            });
        }, 60000);
    });

})( jQuery );
");