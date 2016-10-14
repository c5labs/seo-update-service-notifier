<?php
defined('C5_EXECUTE') or die("Access Denied.");
?>
<form method="post" action="<?php echo $view->action('save_notifications'); ?>">
    <?php echo $this->controller->token->output('save_notifications'); ?>
    <fieldset>
        <div class="checkbox">
            <label>
                <?php echo $fh->checkbox('concrete[seo][notify][enabled]', 1, $enabled) ?>
                <?php echo t('Notify update services when a new page version is approved.'); ?>
            </label>
        </div>
        <div class="form-group" style="margin-top: 30px;">
            <label class="control-label"><?php echo t('Update Service List')?></label>
            <textarea name="concrete[seo][notify][hosts]" rows="8" class="form-control"><?php echo $hosts ?></textarea>
            <div class="alert alert-info" style="margin-top: 30px;">Separate multiple service URLs with line breaks.</div>
        </div>
    </fieldset>
    <fieldset>
        <legend>Advanced Settings</legend>
        <div class="checkbox">
            <label>
                <?php echo $fh->checkbox('concrete[seo][notify][log]', 1, $log) ?>
                <?php echo t('Log all notification requests & the responses.'); ?>
            </label>
        </div>

        <div class="form-group" style="margin-top: 30px;">
            <label class="control-label"><?php echo t('Rate limiting')?></label>
            <br>
            <?php echo t('Only allow one request to be sent every')?>
            <select name="concrete[seo][notify][ttl]">
                <option value="900"<?php echo $ttl === 900 ? 'selected="selected"' : '' ?>>15 minutes</option>
                <option value="1800"<?php echo $ttl === 1800 ? 'selected="selected"' : '' ?>>30 minutes</option>
                <option value="3600"<?php echo $ttl === 3600 ? 'selected="selected"' : '' ?>>1 hour</option>
                <option value="14400"<?php echo $ttl === 14400 ? 'selected="selected"' : '' ?>>4 hours</option>
                <option value="28800"<?php echo $ttl === 28800 ? 'selected="selected"' : '' ?>>8 hours</option>
                <option value="43200"<?php echo $ttl === 43200 ? 'selected="selected"' : '' ?>>12 hours</option>
            </select>
            <span style="color: #ccc;">(<?php echo t('Note that additional requests in this time period will be discarded.'); ?>)</span>
        </div>
    </fieldset>

    <div class="ccm-dashboard-form-actions-wrapper">
        <div class="ccm-dashboard-form-actions">
            <?php echo $interface->submit(t('Save'), 'url-form', 'right', 'btn-primary'); ?>
        </div>
    </div>
</form>
