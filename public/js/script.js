jQuery(document).ready(function ($) {
    jQuery('#rememberme').attr('checked', true);
    jQuery('#login h1 a').removeAttr('title');

    if (script_vars.theme_style == 'material') {
        var $user_login_input = jQuery('#user_login');
        var $user_login_label = $user_login_input.parent();
        $user_login_label.attr('id', 'user_login_label');
        var user_login_label_text = $user_login_label.text();
        $user_login_input.attr('placeholder', '');
        $user_login_input.insertAfter("#user_login_label");

        var $user_pass_input = jQuery('#user_pass');
        var $user_pass_label = $user_pass_input.parent();
        $user_pass_label.attr('id', 'user_pass_label');
        var user_pass_label_text = $user_pass_label.text();
        $user_pass_input.attr('placeholder', '');
        $user_pass_input.insertAfter("#user_pass_label");

        jQuery('#loginform').on('focus blur', 'input', function () {
            var $that = jQuery(this);
            $that.parent().toggleClass('has-focus');
            if ($that.val() != '') {
                $that.parent().addClass('has-focus');
            }
        });
    }

});