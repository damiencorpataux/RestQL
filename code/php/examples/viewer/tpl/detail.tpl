<style>
textarea {
    width:100%;
    height:200px;
    font-family: mono;
}
</style>

<?php if (json_decode($d['json']) === null): ?>
    <div class="alert">
        <strong>Invalid JSON</strong>
    </div>
<?php endif ?>

<form class="row">
    <div class="span6">
        <h3>RestQL JSON input</h3>
        <textarea id="restql" name="json"><?php echo $d['json'] ?></textarea>
        <input type="hidden" name="run" value="<?php echo $d['request']['case'] ?>"/>
        <button id="btn-do" class="btn btn-primary pull-right">Parse to SQL &gt;</button>
    </div>
    <div class="span6">
        <h3>SQL output</h3>
        <textarea id="sql" readonly="readonly"><?php echo $d['sql'] ?></textarea>
    </div>
</form>