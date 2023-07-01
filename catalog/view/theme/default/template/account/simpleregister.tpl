<?php if (!$ajax && !$popup && !$as_module) { ?>
<?php $simple_page = 'simpleregister'; include $simple_header; ?>
<div class="simple-content">
<?php } ?>
    <?php if (!$ajax || ($ajax && $popup)) { ?>
    <script type="text/javascript">
        <?php if ($popup) { ?> 
            var simpleScriptsInterval = window.setInterval(function(){
                if (typeof jQuery !== 'undefined' && jQuery.isReady) {
                    window.clearInterval(simpleScriptsInterval);

                    if (typeof Simplepage !== "function") {
                        <?php foreach ($simple_scripts as $script) { ?> 
                            jQuery("head").append('<script src="' + '<?php echo $script ?>' + '"></' + 'script>');
                        <?php } ?>

                        <?php foreach ($simple_styles as $style) { ?> 
                            jQuery("head").append('<link href="' + '<?php echo $style ?>' + '" rel="stylesheet"/>');
                        <?php } ?>                         
                    }
                }
            },0);
        <?php } ?>

        var startSimpleInterval = window.setInterval(function(){
            if (typeof jQuery !== 'undefined' && typeof Simplepage === "function" && jQuery.isReady) {
                window.clearInterval(startSimpleInterval);

                var simplepage = new Simplepage({
                    additionalParams: "<?php echo $additional_params ?>",
                    additionalPath: "<?php echo $additional_path ?>",
                    mainUrl: "<?php echo $action; ?>",
                    mainContainer: "#simplepage_form",
                    useAutocomplete: <?php echo $use_autocomplete ? 1 : 0 ?>,
                    loginLink: "<?php echo $login_link ?>",
                    scrollToError: <?php echo $scroll_to_error ? 1 : 0 ?>,
                    notificationDefault: <?php echo $notification_default ? 1 : 0 ?>,
                    notificationToasts: <?php echo $notification_toasts ? 1 : 0 ?>,
                    notificationCheckForm: <?php echo $notification_check_form ? 1 : 0 ?>,
                    notificationCheckFormText: "<?php echo $notification_check_form_text ?>",
                    languageCode: "<?php echo $language_code ?>",
                    popup: <?php echo ($popup || $as_module) ? 1 : 0 ?>,
                    javascriptCallback: function() {<?php echo $javascript_callback ?>}
                });

                if (typeof toastr !== 'undefined') {
                    toastr.options.positionClass = "<?php echo $notification_position ? $notification_position : 'toast-top-right' ?>";
                    toastr.options.timeOut = "<?php echo $notification_timeout ? $notification_timeout : '5000' ?>";
                    toastr.options.progressBar = true;            
                    toastr.options.preventDuplicates = true;        
                }

                simplepage.init();
            }
        },0);
    </script>
    <?php } ?>
    <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="simplepage_form">
        <div class="simpleregister" id="simpleregister">
            <p class="simpleregister-have-account"><?php echo $text_account_already; ?></p>
            <?php if ($error_warning) { ?>
            <div class="warning alert alert-danger"><?php echo $error_warning; ?></div>
            <?php } ?>
            <div class="simpleregister-block-content">
                <?php foreach ($rows as $row) { ?>
                  <?php echo $row ?>
                <?php } ?>
                <?php foreach ($hidden_rows as $row) { ?>
                  <?php echo $row ?>
                <?php } ?>
            </div>

            <?php if ($display_agreement_checkbox) { ?>
                <div class="alert alert-danger simpleregister-warning-block" id="agreement_warning" <?php if ($error_agreement) { ?>data-error="true"<?php } else { ?>style="display:none;"<?php } ?>>
                    <div class="agreement_all">
                        <?php foreach ($error_warning_agreement as $agreement_id => $warning_agreement) { ?>
                            <div class="agreement_<?php echo $agreement_id ?>"><?php echo $warning_agreement ?></div>
                        <?php } ?>
                    </div>                    
                </div>
            <?php } ?>

            <div class="simpleregister-button-block buttons">
                <div class="simpleregister-button-right">
                    <?php if ($display_agreement_checkbox) { ?>
                        <span id="agreement_checkbox">
                            <?php foreach ($text_agreements as $agreement_id => $text_agreement) { ?>
                                <div class="checkbox"><label><input type="checkbox" name="agreements[]" value="<?php echo $agreement_id ?>" <?php echo in_array($agreement_id, $agreements) ? 'checked="checked"' : '' ?> /><?php echo $text_agreement; ?></label></div>
                            <?php } ?>
                        </span>
                    <?php } ?>
                    <a class="button btn-primary button_oc btn" data-onclick="submit" id="simpleregister_button_confirm"><span><?php echo $button_continue; ?></span></a>
                </div>
            </div>
        </div>
        <?php if ($redirect) { ?>
            <input type="hidden" id="simple_redirect_url" value="<?php echo $redirect ?>">
        <?php } ?>
    </form>
<?php if (!$ajax && !$popup && !$as_module) { ?>
</div>
<?php include $simple_footer ?>
<?php } ?>
