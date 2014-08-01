<script>
if(window.XMLHttpRequest) {
    var xhr = new XMLHttpRequest();
    xhr.lastText = '';
     
    xhr.onerror = function() { console.log("[XHR] Fatal Error."); };
    xhr.onreadystatechange = function() {
        if(xhr.readyState > 2) {
            var message = xhr.responseText.replace(/^\s+/,"").substring(xhr.lastText.replace(/^\s+/,"").length);
            xhr.lastText = xhr.responseText;
            var objDiv = document.getElementById("divProgress");
            $(objDiv).append(message);
            objDiv.scrollTop = objDiv.scrollHeight;
        }
    };
    xhr.onload = function() {
        $('#continue').removeClass('state-disabled');
    };

    xhr.open("GET", "<?php echo $this->uri->request('~/tasks/invoke.stream?token='.$this['token']); ?>", true);
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
    ->addClass('state-disabled')
    ->setId('continue');
?>
</div>
