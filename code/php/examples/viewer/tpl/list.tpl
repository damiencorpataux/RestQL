<dl>
<?php foreach ($d as $case => $tests): ?>
    <dt>
        <?php echo ucfirst($case) ?>
    </dt>
<?php foreach ($tests as $test): ?>
    <dd>
        <a href="<?php echo "?run=$case:$test" ?>">
            <?php echo ucfirst($test) ?>
        </a>
    </dd>
<?php endforeach ?>
<?php endforeach ?>
</dl>