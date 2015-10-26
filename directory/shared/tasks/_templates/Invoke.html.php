<script>
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
        $('#continue').removeClass('disabled');
    };

    xhr.open("GET", "<?php echo $this->uri('~/tasks/invoke.stream?token='.$token); ?>", true);
    xhr.send("Making request...");
}
</script>

<?php
echo $this->html->flashMessage($this->_(
    'Do not browse away from this page while the task is processing otherwise you will not be able to track progress'
), 'warning');
?>

<code style="display: block; margin-bottom: 2em; border:2px solid #DDD; padding:10px; width:auto; height:400px; overflow:auto; background:#FAFAFA; border-radius: 0.7em;" id="divProgress"></code>

<div style="display: block;">
<?php
echo $this->html->backLink($this['redirect'], true, $this->_('Continue'))
    ->setIcon('back')
    ->setDisposition('informative')
    ->addClass('disabled')
    ->setId('continue');
?>
</div>
