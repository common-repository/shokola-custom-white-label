var media_uploader = null;
var input_id;

function open_media_uploader_image(the_id, uploader_button_text) {
    var the_elmt = document.getElementById(the_id);
    var the_img_preview = document.getElementById(the_id + '_image_preview');
    var the_img_preview_description = document.getElementById(the_id + '_image_preview_description');

    media_uploader = wp.media({
        frame: "post",
        library: {type: 'image'},
        state: "insert",
        multiple: false,
        button: {text: 'Insert this image'}
    });

    media_uploader.on("insert", function () {
        var json = media_uploader.state().get("selection").first().toJSON();
        var image_url = json.url;
        var image_caption = json.caption;
        var image_title = json.title;

        the_elmt.value = image_url;
        the_img_preview.src = image_url;
        the_img_preview.style.display = "block";
        the_img_preview_description.style.display = "block";
    });

    media_uploader.open();
}

jQuery(document).ready(function ($) {
    jQuery('.shokola-color-field').wpColorPicker();

    jQuery('.button-remove-image').on("click", function () {
        input_id = jQuery(this).data('input-id');
        jQuery('#' + input_id).val('');
        jQuery('#' + input_id + '_image_preview').hide();
        jQuery('#' + input_id + '_image_preview_description').hide();
    });

    jQuery('.nav-tab-wrapper a.nav-tab').on('click', function (event) {

        jQuery('.nav-tab-wrapper a').removeClass('nav-tab-active');
        jQuery(this).addClass('nav-tab-active');

        var section = jQuery(this).attr('href').replace('#', '');

        jQuery('.scwl-container-section').hide();
        jQuery('#scwl_container_section_' + section).show();

        return false;
    });

});