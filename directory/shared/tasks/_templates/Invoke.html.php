<?php

use DecodeLabs\Tagged as Html;

$nonce = null;

if ($csp = $this->context->app->getCsp('text/html')) {
    $nonce = $csp->getNonce();
}

$attr = [];

if ($nonce !== null) {
    $attr['nonce'] = $nonce;
}

echo Html::script(Html::raw('
if(window.XMLHttpRequest) {
    var xhr = new XMLHttpRequest();

    xhr.onerror = function() { console.log("[XHR] Fatal Error."); };
    xhr.onreadystatechange = function() {
        if(xhr.readyState > 2) {
            var objDiv = document.getElementById("divProgress");
            $(objDiv).text(xhr.responseText.replace(/^\s+/,""));
            objDiv.scrollTop = objDiv.scrollHeight;
        }
    };
    xhr.onload = function() {
        $("#continue").removeClass("disabled");
    };

    xhr.open("GET", "' . ($this['url'] ?? $this->uri('~/tasks/invoke.stream?token=' . $token)) . '", true);
    xhr.send("Making request...");
}
'), $attr);

echo $this->html->flashMessage($this->_(
    'Do not browse away from this page while the task is processing otherwise you will not be able to track progress'
), 'warning');

echo Html::{'samp.terminal-output#divProgress'}();

echo Html::div(function () {
    echo $this->html->backLink($this['redirect'], true, $this->_('Continue'))
        ->setIcon('back')
        ->setDisposition('informative')
        ->addClass('disabled')
        ->setId('continue');
}, [
    'style' => ['display' => 'block']
]);
