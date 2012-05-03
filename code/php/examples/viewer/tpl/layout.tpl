<html>
    <head>
        <link href="css/bootstrap.css" rel="stylesheet">
        <script src="https://ajax.googleapis.com/ajax/libs/mootools/1.4.5/mootools-yui-compressed.js" language="javascript" type="text/javascript"></script>
        <style>
          body { overflow-y: scroll }
        </style>
    </head>
    <body class="container" onload="document.getElementById('restql').focus()">
        <!-- Header -->
        <header class="jumbotron subhead" id="overview">
            <h1>RestQL</h1>
            <p class="lead">
                a Rest oriented query language
            </p>
        </header>
        <hr/>

        <!-- Tabs -->
        <?php
            $cases = $d['data']['cases'];
            $case = $d['request']['case'];
        ?>

        <ul class="nav nav-tabs">
        <?php foreach($cases as $type): ?>
            <?php $class = ($case == $type) ? 'active' : null ?>
            <li data-case="<?php echo $type ?>" class="<?php echo $class ?>">
                <a href="javascript:void()"><?php echo ucfirst($type) ?></a>
            </li>
        <?php endforeach ?>
        </ul>
        <script>
            $$('.nav-tabs li').addEvent('click', function(event){
                var type = event.target.parentElement.getAttribute('data-case');
                window.location.search = "?run="+type;
            });
        </script>

        <!-- Container -->
        <div class="container-fluid">
            <div class="row-fluid">
                <div class="span2">
                    <?php echo $d['html']['side'] ?>
                </div>
                <div class="span10">
                    <?php echo $d['html']['body'] ?>
                </div>
            </div>
        </div>
        <hr/>
        <footer class="footer">
            Please comment.
        </footer>
    </body>
</html>