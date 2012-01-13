<?php
/**
 * This file is part of the Decode Framework
 * @license http://opensource.org/licenses/MIT
 */
namespace df\core\debug\renderer;

use df;
use df\core;

?>
<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>DF Debug</title>
        <link rel="icon" href="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAABHNCSVQICAgIfAhkiAAAArVJREFUOMt9k11IU2EYx/9nZ0vNZH3MIhRam6VZGFja1taW0YcRVHhRVHRjFER4UVgR2FVBdSVYXURRSEUFlQSSRB+SZYmKQlkpbLmMKGx+nE3P2c457/N01bCa/eC5eHif93fxf94XzIzpKtiyloPN3gpmxrqHa6r898u1v2csmMKGZ3731J4NAhl09Ej9DhcZNI90MvAXfwjY5ND6x2trf/dkcJIM9plK4kxu2NYAjWftOea/MK2ATFLI5LMAEHjgVcigDDJo7nvX6N4Svyfz/OY6yZlXcGL7oVXHf9+RmDklCDR5KljwCwtZlBw5x15dtAfZ8gx8jH7Bje6bKC0qxLY5lTjXfhlsMshkSIFHHoWJS9t2doQBwHenjGxskw4v243Qz3fQkioMZKLjWz/GYgrK8kowdL839LRpYAkAWMnkLBYc8t0tv2kluTI/M1/asWgd+r/3IJ5QMKzGYWbYcWR1NaITE7jy+ipysrgvlcGrqo4ZpFMH6bRfTxq5u1wbMfCjFzFtHMMTMejyLAQXrEZn+DmsnAAbEsac0qbik0trUiG+2dflhYHk4uxFiIwMIKYqGI5PQJOyEVxYhg9D3RifHAcgQyRNkE4zSaeGwho3p7bASRZ262zYLDMxomrQkAXP3BXo+9qFyaQKl6MYH4e+QOgEJNnRXx+ShE6nUwJZyA2dg11QNECjLHgdJfg88gFqIgGXoxjN3e1o6XkC1vngp4uhUQAIXRk8mxK013SekgxLW2PrPWx1bYFqxiEEUDC/GKpqRXQ0CtIJQheX3AectWnfAQCsrFv+EoRARVEAVknG095WqJoGiXBQmBRmwU0s2M7EBZHbX8P/CABgRW1ho6mb+4TJMgsGC34TvhbxIQ1pBVNxVzvfsmAPCy4YvDUU/u9nSkf4esRLBhlkck+681+eRJHAgdTwpAAAAABJRU5ErkJggg==" />
        <style type="text/css">
            <?php require __DIR__.'/style.css'; ?>
        </style>
        <script type="text/javascript">
            <?php require __DIR__.'/jquery-1.7.1.min.js'; ?>
            <?php require __DIR__.'/functions.js'; ?>
        </script>
    </head>
    <body>
        <div id="page-header">
            <h1 id="page-logo">DF <?php echo df\Launchpad::REV.' '.ucfirst(df\Launchpad::CODENAME); ?></h1>
            
            <ul id="page-stats">
              <?php foreach($this->_stats as $key => $stat) { ?>
                <li><?php echo $this->esc($key); ?>: <strong><?php echo $this->esc($stat); ?></strong></li>
              <?php } ?>
            </ul>
        </div>
        
        <div id="page-toggleButtons">
            <?php
            $counts = $this->_context->getNodeCounts();
            $includes = $this->_getNormalizedIncludeList();
            
            foreach($counts as $key => $value) { 
            ?>
            <a class="node-<?php echo $this->esc($key); ?> <?php echo $value ? 'on' : 'empty'; ?>" 
                data-node="<?php echo $this->esc($key); ?>">
                <?php echo $this->esc(ucfirst($key)); ?> <sup><?php echo $value; ?></sup>
            </a>
            <?php } ?>
            <a class="node-includes" data-node="includes">
                Includes <sup><?php echo count($includes); ?></sup>
            </a>
        </div>
        
        <?php
        $nodeRender = function($stack, $nodeRender) {
            foreach($stack as $key => $node) {
                $hasChildren = $node instanceof core\debug\IGroupNode && $node->hasChildren();
                $hasBody = $node instanceof core\debug\IExceptionNode
                        || $node instanceof core\debug\IDumpNode
                        || $node instanceof core\debug\IStackTrace;
        ?>
        <div class="nodeBlock node-<?php echo $this->esc($node->getNodeType()); ?><?php if($hasBody || $hasChildren) { echo ' collapsible'; } ?>">
            <header class="nodeHeader">
                <h3 class="nodeTitle"><?php echo $this->esc($node->getNodeTitle()); ?></h3>
                
                <div class="description">
                    <?php echo $this->_getNodeDescription($node); ?>
                    
                    <?php if($location = $this->_getNodeLocation($node)) { ?>
                    <div class="location">
                        <?php echo $location; ?>
                    </div>
                    <?php } ?>
                </div>
            </header>
            
            <?php if($hasBody || $hasChildren) { ?>
            <div class="nodeBody">
                <?php if($hasBody) { ?>
                <div class="nodeContent<?php if($hasChildren) { echo ' mixed'; } ?>">
                    <?php echo $this->_getNodeBody($node); ?>
                </div>
                <?php } ?>
                <?php if($hasChildren) { $nodeRender($node->getChildren(), $nodeRender); } ?>
            </div>
            <?php } ?>
        </div>
        <?php }
        };
        
        $nodeRender($this->_context->getChildren(), $nodeRender);
        ?>
        
        <div class="nodeBlock node-stackTrace">
            <header class="nodeHeader">
                <h3 class="nodeTitle">Stack Trace</h3>
            </header>
            
            <div class="nodeBody">
                <div class="nodeContent">
                    <?php echo $this->_renderStackTrace($this->_context->getStackTrace()); ?>
                </div>
            </div>
        </div>
        
        <div class="nodeBlock node-includes">
            <header class="nodeHeader">
                <h3 class="nodeTitle">Includes</h3>
                
                <div class="description">
                    Included <?php echo count($includes); ?> files
                </div>
            </header>
            
            <div class="nodeBody">
                <div class="nodeContent"><?php echo implode("\n", $includes); ?></div>
            </div>
        </div>
    </body>
</html>