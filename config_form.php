<div class="field">
    <div class="two columns alpha">
        <label for="rights_statements_preference">Image Preference</label>
    </div>
    <div class="inputs five columns omega">
        <label>
            <input type="radio" name="rights_statements_preference"
                value=""<?php if (!get_option('rights_statements_preference')) echo ' checked'; ?>>
            Display All
        </label>
        <?php foreach ($this->domains as $domain => $data): ?>
            <label>
                <input type="radio" name="rights_statements_preference"
                    value="<?php echo $domain; ?>"<?php if (get_option('rights_statements_preference') === $domain) echo ' checked'; ?>>
                <?php echo $domain; ?>
            </label>
        <?php endforeach; ?>
    </div>
</div>

<div class="field">
    <div class="two columns alpha">
        <label for="rights_statements_target">Open in New Window</label>
    </div>
    <div class="inputs five columns omega">
        <input type="hidden" name="rights_statements_target" value="">
        <input type="checkbox" name="rights_statements_target" id="rights_statements_target" value="1"<?php if (get_option('rights_statements_target')) echo ' checked'; ?>>
    </div>
</div>

<?php foreach ($this->domains as $domain => $data): ?>
    <?php
    $prefix = 'rights_statements_' . str_replace('.', '_', $domain);
    $example = key($data['licenses']);
    ?>
    <h2><?php echo $domain; ?></h2>
    <div class="field">
        <div class="two columns alpha">
            <label for="<?php echo $prefix; ?>_format">Image Format</label>
        </div>
        <div class="inputs five columns omega">
            <?php foreach ($data['formats'] as $format => $height): ?>
                <label>
                    <input type="radio" name="<?php echo $prefix; ?>_format"
                        id="<?php echo $prefix; ?>_format_<?php echo $format; ?>"
                        value="<?php echo $format; ?>"<?php if (get_option($prefix . '_format') === $format) echo ' checked'; ?>>
                    <?php echo $format; ?><br>
                    <img src="<?php echo web_path_to($domain . '/' . $format . '/' .
                        $example . '.svg'); ?>"
                        alt="<?php echo $data['licenses'][$example]; ?>"
                        height="<?php echo $height; ?>">
                </label>
            <?php endforeach; ?>
            <label>
                <input type="radio"
                    name="<?php echo $prefix; ?>_format"
                    id="<?php echo $prefix; ?>_format_disabled"
                    value="disabled"<?php if (get_option($prefix . '_format') === 'disabled') echo ' checked'; ?>>
                Disabled
            </label>
        </div>
    </div>

    <div class="field">
        <div class="two columns alpha">
            <label for="<?php echo $prefix; ?>_height">Image Height</label>
        </div>
        <div class="inputs five columns omega">
            <input type="text"
                name="<?php echo $prefix; ?>_height"
                id="<?php echo $prefix; ?>_height"
                value="<?php echo get_option($prefix . '_height'); ?>">
        </div>
    </div>
<?php endforeach; ?>
