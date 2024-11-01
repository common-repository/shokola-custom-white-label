<?php
/*
Plugin Name: Shokola Custom and White Label
Plugin URI:  https://wordpress.org/plugins/shokola-custom-white-label/
Description: Set WordPress as "White Label" and personalize the login page. See <code>Settings / Custom Shokola</code> to configure.
Version:     1.2.5
Author:      Shokola
Author URI:  https://www.shokola.com/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Tags:        custom,white,label,login,log in
Requires at least: 3.1.2
Tested up to: 4.9.4
Text Domain: shokola-custom-whitelabel
Domain Path: /languages
*/

// If this file is called directly, just die
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

/**
 * Shokola Custom WhiteLabel
 *
 * @class Shokola_Custom_WhiteLabel
 */
if ( ! class_exists( 'Shokola_Custom_WhiteLabel' ) ) {
	class Shokola_Custom_WhiteLabel {

		private $version = '1.2.5';
		private $plugin_slug = 'shokola-custom-whitelabel';

		/**
		 * Constructor.
		 */
		public function __construct() {

			add_action( 'admin_init', array( $this, 'my_plugin_redirect' ) );

			// Add text domain for translations
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

			// Admin Scripts
			add_action( 'admin_enqueue_scripts', array( $this, 'add_scripts' ) );

			// All - Custom register settings
			add_action( 'admin_init', array( $this, 'register_settings' ) );

			// BO - Add Option Page
			add_action( 'admin_menu', array( $this, 'add_settings_page' ) );

			// BO - Add settings link on plugin page
			add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

			// BO - Remove WP logo
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_remove_items' ), 999 );

			// BO - Remove WP Welcome panel and other dashboard WP meta box
			add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widgets' ), 999 );

			// BO - Footer - Remove version number for non Shokola user
			add_action( 'admin_menu', array( $this, 'remove_version_footer' ) );

			// BO - Footer - Remove credits or set to anything
			add_filter( 'admin_footer_text', array( $this, 'change_credits_footer' ) );

			// Login - Check 'Remember me'
			add_action( 'login_enqueue_scripts', array( $this, 'login_scripts' ) );

			// Login Custom logo url
			add_action( 'login_head', array( $this, 'shokola_custom_login_css' ) );
			add_filter( 'login_headerurl', array( $this, 'shokola_logo_url_login' ), 999 );

			// Login and BO - Favico
			add_action( 'admin_head', array( $this, 'shokola_custom_favicon' ) );
			add_action( 'login_head', array( $this, 'shokola_custom_favicon' ) );

		}

		public function my_plugin_activate() {
			add_option( 'my_plugin_do_activation_redirect', true );
		}

		public function my_plugin_redirect() {
			if ( get_option( 'my_plugin_do_activation_redirect', false ) ) {
				delete_option( 'my_plugin_do_activation_redirect' );
				wp_redirect( 'options-general.php?page=' . $this->plugin_slug . '.php' );
				exit();
			}
		}

		public function load_textdomain() {
			load_plugin_textdomain( 'shokola-custom-whitelabel', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
		}

		public function add_scripts() {
			if ( function_exists( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style( 'shokola_admin_style', plugin_dir_url( __FILE__ ) . 'admin/css/admin_style.css', array(), time() );
			wp_enqueue_script( 'shokola_admin_script', plugin_dir_url( __FILE__ ) . 'admin/js/admin_script.js', array(
				'jquery',
				'wp-color-picker'
			), time(), true );
		}


		/**
		 * All - Custom register settings
		 */
		public function register_settings() {
			$settings = $this->shokola_custom_get_settings();
			// register plugins settings
			foreach ( $settings as $setting ) {
				register_setting( 'wp_plugin_template-group', $setting['key'] );
			}
		}

		/**
		 * Add Option Page
		 */
		public function add_settings_page() {
			add_options_page( 'Shokola Custom', 'Shokola Custom', 'manage_options', $this->plugin_slug . '.php', array(
				$this,
				'shokola_custom_options_page_html'
			) );
		}

		/**
		 * Add settings link on plugin page
		 */
		function plugin_action_links( $links ) {
			$links[] = '<a href="' . esc_url( get_admin_url( null, 'options-general.php?page=' . $this->plugin_slug . '.php' ) ) . '">' . __( 'Settings', 'shokola-custom-whitelabel' ) . '</a>';

			// $links[] = '<a href="https://www.shokola.com/" target="_blank">Shokola</a>';
			return $links;
		}

		/**
		 * BO - Option Page HTML
		 */
		public function shokola_custom_options_page_html() {
			// For futur improvments
			$tabs = false;

			// check user capabilities
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'You do not have sufficient permissions to access this page.', 'shokola-custom-whitelabel' ) );
			}

			$settings = $this->shokola_custom_get_settings();
			?>
            <div class="wrap scwl-wrap">
                <h2><?php echo esc_html( get_admin_page_title() ); ?></h2>
                <p><?php echo __( 'Adjust the custom settings of the login page / recovery password page.', 'shokola-custom-whitelabel' ); ?></p>

				<?php if ( $tabs ) : ?>
                    <h2 class="nav-tab-wrapper">
                        <a href="#login" class="nav-tab nav-tab-active">Login page options</a>
                        <a href="#other" class="nav-tab">White label options</a>
                    </h2>
				<?php endif; ?>

                <form method="post" action="options.php">
					<?php settings_fields( 'wp_plugin_template-group' ); ?>
					<?php do_settings_fields( 'wp_plugin_template-group', 'wp_plugin_template-group' ); ?>

                    <div class="scwl-container-section" id="scwl_container_section_login">


                        <table class="form-table">
							<?php foreach ( $settings as $setting ) :
								$option_value = ( get_option( $setting['key'] ) == '' ) ? $setting['default_value'] : get_option( $setting['key'] );
								?>
                                <tr valign="top">
                                    <th scope="row"><label
                                                for="<?php echo $setting['key']; ?>"><?php echo $setting['name']; ?></label>
                                    </th>
                                    <td>
										<?php if ( $setting['type'] == 'radio' ) : ?>

                                            <fieldset class="fieldset_<?php echo $setting['key']; ?>">
                                                <legend class="screen-reader-text">
                                                    <span><?php echo $setting['name']; ?></span>
                                                </legend>
												<?php foreach ( $setting['values'] as $sub_key => $sub_value ) : ?>
                                                <label
                                                        class="label_<?php echo $setting['key']; ?> label_<?php echo $setting['key']; ?>_<?php echo $sub_key; ?>">
                                                    <input type="radio" name="<?php echo $setting['key']; ?>"
                                                           id="<?php echo $setting['key']; ?>_<?php echo $sub_key; ?>"
                                                           value="<?php echo $sub_key; ?>" <?php echo ( $option_value == $sub_key ) ? 'checked="checked"' : ''; ?>><?php echo $sub_value; ?>
                                                    </label><?php echo ( $setting['key'] != 'shokola_background_position' ) ? '<br>' : ''; ?>
												<?php endforeach; ?>
                                            </fieldset>

										<?php else : ?>

                                            <input type="text" name="<?php echo $setting['key']; ?>"
                                                   id="<?php echo $setting['key']; ?>"
												<?php if ( $setting['type'] == 'file' && function_exists( 'wp_enqueue_media' ) ) : ?>
                                                    onclick="open_media_uploader_image('<?php echo $setting['key']; ?>', 'The Button Text');"
												<?php endif; ?>
                                                   value="<?php echo $option_value; ?>" <?php echo ( isset( $setting['placeholder'] ) && function_exists( 'wp_enqueue_media' ) ) ? 'placeholder="' . $setting['placeholder'] . '"' : ''; ?>
                                                   class="regular-text <?php echo ( $setting['type'] == 'color' ) ? ' shokola-color-field ' : ''; ?>"
												<?php echo ( $setting['type'] == 'color' ) ? ' data-default-color="' . $setting['default_value'] . '" ' : ''; ?>
												<?php echo ( $setting['type'] == 'file' && function_exists( 'wp_enqueue_media' ) ) ? ' readonly' : ''; ?>
                                            >
											<?php if ( $setting['type'] == 'file' && function_exists( 'wp_enqueue_media' ) ) : ?>
                                                <button type="button"
                                                        class="button button-secondary button-remove-image"
                                                        title="<?php echo __( 'Remove image', 'shokola-custom-whitelabel' ); ?>"
                                                        data-input-id="<?php echo $setting['key']; ?>"><?php echo __( 'Remove', 'shokola-custom-whitelabel' ); ?></button>
											<?php endif; ?>

										<?php endif; ?>


                                        <p class="description"
                                           id="tagline-description"><?php echo $setting['description']; ?></p>
										<?php if ( $setting['type'] == 'file' && function_exists( 'wp_enqueue_media' ) ) : ?>
                                            <p class="description"
                                               id="<?php echo $setting['key']; ?>_image_preview_description" <?php echo ( $option_value == '' ) ? 'style="display: none;"' : ''; ?>><?php echo __( 'Image preview', 'shokola-custom-whitelabel' ); ?>
                                                :</p>
                                            <div class="image-preview">
                                                <img src="<?php echo $option_value; ?>" alt="Image preview"
                                                     id="<?php echo $setting['key']; ?>_image_preview" <?php echo ( $option_value == '' ) ? 'style="display: none;"' : ''; ?>>
                                            </div>
										<?php endif; ?>

                                    </td>
                                </tr>
							<?php endforeach; ?>
                        </table>
                    </div>

                    <div class="scwl-container-section" id="scwl_container_section_other">

                    </div>


					<?php submit_button(); ?>
                </form>
            </div>
			<?php
		}

		/**
		 * All - Set custom settings
		 * @return array
		 */
		public function shokola_custom_get_settings() {
			$settings = array();

			$settings[] = array(
				'type'          => 'file',
				'name'          => __( 'Logo - image', 'shokola-custom-whitelabel' ),
				'default_value' => '',
				'placeholder'   => __( 'Click here to choose an image...', 'shokola-custom-whitelabel' ),
				'description'   => __( 'Select or upload an image in the Media. Recommended size :', 'shokola-custom-whitelabel' ) . ' <code>320px x 320px max</code>',
				'key'           => 'shokola_logo_name'
			);
			$settings[] = array(
				'type'          => 'text',
				'name'          => __( 'Logo - link', 'shokola-custom-whitelabel' ),
				'default_value' => '#',
				'description'   => __( 'Logo link', 'shokola-custom-whitelabel' ),
				'key'           => 'shokola_logo_url'
			);

			$settings[] = array(
				'type'          => 'file',
				'name'          => __( 'Background - image', 'shokola-custom-whitelabel' ),
				'default_value' => '',
				'placeholder'   => __( 'Click here to choose an image...', 'shokola-custom-whitelabel' ),
				'description'   => __( 'Select or upload an image in the Media. Recommended size :', 'shokola-custom-whitelabel' ) . ' <code>1920px x 1080px min</code>',
				'key'           => 'shokola_background_name'
			);

			$settings[] = array(
				'type'          => 'radio',
				'name'          => __( 'Background - position', 'shokola-custom-whitelabel' ),
				'values'        => array(
					'left top'      => __( 'left top', 'shokola-custom-whitelabel' ),
					'center top'    => __( 'center top', 'shokola-custom-whitelabel' ),
					'right top'     => __( 'right top', 'shokola-custom-whitelabel' ),
					'left center'   => __( 'left center', 'shokola-custom-whitelabel' ),
					'center center' => __( 'center center', 'shokola-custom-whitelabel' ),
					'right center'  => __( 'right center', 'shokola-custom-whitelabel' ),
					'left bottom'   => __( 'left bottom', 'shokola-custom-whitelabel' ),
					'center bottom' => __( 'center bottom', 'shokola-custom-whitelabel' ),
					'right bottom'  => __( 'right bottom', 'shokola-custom-whitelabel' )
				),
				'default_value' => 'center center',
				'description'   => __( 'Choose background position', 'shokola-custom-whitelabel' ),
				'key'           => 'shokola_background_position'
			);

			$settings[] = array(
				'type'          => 'radio',
				'values'        => array(
					'no-repeat' => __( 'The background-image will not be repeated', 'shokola-custom-whitelabel' ),
					'repeat'    => __( 'The background image will be repeated both vertically and horizontally', 'shokola-custom-whitelabel' ),
					'repeat-x'  => __( 'The background image will be repeated only horizontally', 'shokola-custom-whitelabel' ),
					'repeat-y'  => __( 'The background image will be repeated only vertically', 'shokola-custom-whitelabel' )
				),
				'name'          => __( 'Background - repeat', 'shokola-custom-whitelabel' ),
				'default_value' => 'no-repeat',
				'description'   => __( 'Choose background repetition', 'shokola-custom-whitelabel' ),
				'key'           => 'shokola_background_repeat'
			);

			$settings[] = array(
				'type'          => 'radio',
				'values'        => array(
					0 => __( 'No', 'shokola-custom-whitelabel' ),
					1 => __( 'Yes', 'shokola-custom-whitelabel' )
				),
				'name'          => __( 'Background - cover', 'shokola-custom-whitelabel' ),
				'default_value' => '0',
				'description'   => __( 'Set background dimension as cover type', 'shokola-custom-whitelabel' ),
				'key'           => 'shokola_background_cover'
			);

			$settings[] = array(
				'type'          => 'file',
				'name'          => __( 'Favicon', 'shokola-custom-whitelabel' ),
				'default_value' => '',
				'placeholder'   => __( 'Click here to choose an image...', 'shokola-custom-whitelabel' ),
				'description'   => __( 'Select or upload an image in the Media. Recommended size :', 'shokola-custom-whitelabel' ) . ' <code>16px x 16px</code>',
				'key'           => 'shokola_favico_name'
			);

			$settings[] = array(
				'type'          => 'radio',
				'values'        => array(
					'classic'  => __( 'Classic', 'shokola-custom-whitelabel' ),
					'material' => __( 'Material Design', 'shokola-custom-whitelabel' )
				),
				'name'          => __( 'Theme - style', 'shokola-custom-whitelabel' ),
				'default_value' => 'classic',
				'description'   => __( 'The general look of the form (inputs, labels and buttons)', 'shokola-custom-whitelabel' ),
				'key'           => 'shokola_theme_style'
			);

			$settings[] = array(
				'type'          => 'radio',
				'values'        => array(
					0 => __( 'Light', 'shokola-custom-whitelabel' ),
					1 => __( 'Dark', 'shokola-custom-whitelabel' )
				),
				'name'          => __( 'Theme - ambiance', 'shokola-custom-whitelabel' ),
				'default_value' => '0',
				'description'   => __( 'Light or dark theme', 'shokola-custom-whitelabel' ),
				'key'           => 'shokola_theme_dark'
			);

			$settings[] = array(
				'type'          => 'color',
				'name'          => __( 'Theme - details', 'shokola-custom-whitelabel' ),
				'default_value' => '#333',
				'description'   => __( 'Borders and buttons color', 'shokola-custom-whitelabel' ),
				'key'           => 'shokola_theme_color'
			);

			$settings[] = array(
				'type'          => 'color',
				'name'          => __( 'Theme - logo background', 'shokola-custom-whitelabel' ),
				'default_value' => '#FFF',
				'description'   => __( 'Background color of the logo (should be <code>#FFFFFF</code> to match Light theme and <code>#333333</code> to match Dark theme', 'shokola-custom-whitelabel' ),
				'key'           => 'shokola_h1_color'
			);

			return $settings;
		}


		/**
		 * BO - Remove WP logo
		 *
		 * @param $wp_admin_bar
		 */
		public function admin_bar_remove_items( $wp_admin_bar ) {
			$wp_admin_bar->remove_node( 'wp-logo' );
			$wp_admin_bar->remove_menu( 'wp-logo' );
		}


		/**
		 * BO - DashBoard remove WP Widgets
		 *
		 * Note : use 'dashboard-network' as the second parameter to remove widgets from a network dashboard.
		 * Note : global $wp_meta_boxes; print_r($wp_meta_boxes['dashboard']); to have list of all
		 */
		public function remove_dashboard_widgets() {

			remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );   // Right Now
			remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );   // WordPress blog

			remove_action( 'welcome_panel', 'wp_welcome_panel' );

		}


		/**
		 * BO - Footer - Remove version number for non admin user
		 */
		public function remove_version_footer() {
			if ( is_admin() ) {
				add_filter( 'update_footer', array( $this, 'smarter_update_footer' ), 9999 );
			} else {
				remove_filter( 'update_footer', 'core_update_footer' );
			}
		}

		/**
		 * BO - Footer - Improve Admin footer update notice.
		 *  credits to : https://profiles.wordpress.org/pixeline/
		 */
		public function smarter_update_footer( $msg = '' ) {
			if ( ! current_user_can( 'update_core' ) ) {
				return sprintf( __( 'Version %s' ), get_bloginfo( 'version', 'display' ) );
			}

			$cur = get_preferred_from_update_core();
			if ( ! is_object( $cur ) ) {
				$cur = new stdClass;
			}

			if ( ! isset( $cur->current ) ) {
				$cur->current = '';
			}

			if ( ! isset( $cur->url ) ) {
				$cur->url = '';
			}

			if ( ! isset( $cur->response ) ) {
				$cur->response = '';
			}

			switch ( $cur->response ) {
				case 'development' :
					return sprintf( __( 'You are using a development version (%1$s). Cool! Please <a href="%2$s">stay updated</a>.' ), get_bloginfo( 'version', 'display' ), network_admin_url( 'update-core.php' ) );

				case 'upgrade' :
					return '<strong>' . sprintf( __( 'Version %s' ), get_bloginfo( 'version', 'display' ) ) . ' - <a href="' . network_admin_url( 'update-core.php' ) . '">' . sprintf( __( 'Get Version %s' ), $cur->current ) . '</a></strong>';

				case 'latest' :
				default :
					return sprintf( __( 'Version %s' ), get_bloginfo( 'version', 'display' ) );
			}
		}

		/**
		 * BO - Footer - Remove credits or set to anything
		 * Default message '<span id="footer-thankyou">Merci de faire de <a href="http://www.wordpress-fr.net/">WordPress</a> votre outil de création.</span>'
		 * @return string
		 */
		public function change_credits_footer() {
			return '';
		}

		/**
		 * Login - Check 'Remember me'
		 */
		public function login_scripts() {
			$theme_style = get_option( 'shokola_theme_style' );

			wp_enqueue_script( 'shokola_login_script', plugin_dir_url( __FILE__ ) . 'public/js/script.js', array( 'jquery' ), time(), true );

			$script_vars = array(
				'template_url' => get_bloginfo( 'template_url' ),
				'plugin_url'   => plugins_url() . '/' . $this->plugin_slug,
				'theme_style'  => $theme_style
			);
			wp_localize_script( 'shokola_login_script', 'script_vars', $script_vars );

		}


		/**
		 * Login Custom CSS
		 */
		public function shokola_custom_login_css() {
			$default_width       = 200;
			$default_height      = 200;
			$upload_dir          = wp_upload_dir();
			$upload_dir_base_url = $upload_dir['baseurl'];
			$upload_dir_base_dir = $upload_dir['basedir'];
			$logo_src            = get_option( 'shokola_logo_name' );
			$bg_src              = get_option( 'shokola_background_name' );
			$bg_repeat           = get_option( 'shokola_background_repeat' );
			$bg_position         = get_option( 'shokola_background_position' );
			$border_color        = get_option( 'shokola_theme_color' );
			$theme_h1_color      = get_option( 'shokola_h1_color' );
			$theme_style         = get_option( 'shokola_theme_style' );
			$theme_dark          = get_option( 'shokola_theme_dark' );
			$background_cover    = get_option( 'shokola_background_cover' );

			if ( $border_color == '' ) {
				$border_color = ( $theme_dark == 1 ) ? '#333' : '#FFF';
			}
			if ( $theme_style == '' ) {
				$theme_style = 'classic';
			}

			if ( file_exists( $logo_src ) && $data = getimagesize( $logo_src ) ) {
				$logo_src_width  = isset( $data[0] ) ? $data[0] : $default_width;
				$logo_src_height = isset( $data[1] ) ? $data[1] : $default_height;
				if ( $logo_src_width > 320 ) {
					// New dimensions
					$logo_src_new_width = 320;
					if ( $logo_src_width != 0 ) {
						$logo_src_new_height = ( $logo_src_new_width * $logo_src_height ) / $logo_src_width;
					} else {
						$logo_src_new_height = 320;
					}
					// Set to new dimensions
					$logo_src_width  = $logo_src_new_width;
					$logo_src_height = $logo_src_new_height;
				}
				$logo_src_type = isset( $data[2] ) ? $data[2] : '';
				$logo_src_attr = isset( $data[3] ) ? $data[3] : '';
			} else {
				$logo_src_width  = $default_width;
				$logo_src_height = $default_height;
				$logo_src_type   = '';
				$logo_src_attr   = '';
			}

			?>
            <style type="text/css">
            <?php if ($bg_src != '') : ?>
            @media screen and (min-width: 639px) {
                body {
                    background: url('<?php echo $bg_src; ?>')<?php echo $bg_repeat; ?> <?php echo $bg_position; ?>;
                <?php if ($background_cover == 1) : ?> background-size: cover;
                <?php endif; ?>
                }
            }

            <?php endif; ?>

            #login {
                padding: 4% 0 0;
                border-top: 10px solid<?php echo $border_color ?>;
                border-bottom: 10px solid<?php echo $border_color ?>;
            }

            #loginform, #lostpasswordform {
                -webkit-box-shadow: none;
                box-shadow: none;
                border: 0;
            }

            #login, #loginform, #lostpasswordform, #login #login_error, #login .message {
                background-color: <?php echo ($theme_dark == 1) ? '#333' : '#FFF'; ?>;
            }

            h1 {
                background-color: <?php echo $theme_h1_color; ?>;
                text-align: center;
            }

            <?php if ($logo_src != "") : ?>
            #login h1 a {
                background: url('<?php echo $logo_src; ?>') no-repeat center center;
                width: <?php echo $logo_src_width;?>px;
                height: <?php echo $logo_src_height;?>px;
                background-size: contain;
                display: inline-block;
                margin: 0 auto;
            }

            <?php endif; ?>

            #wp-submit {
                color: <?php echo ($this->helper_color_diff_hexa($border_color, '#FFFFFF') < 450) ? '#333' : '#FFF'; ?>;
                border-color: <?php echo $border_color ?>;
                background-color: <?php echo $border_color ?>;
                background-image: none;
                border-radius: 0;
                -moz-border-radius: 0;
                -webkit-border-radius: 0;
                letter-spacing: 0;
                text-transform: uppercase;
                border-width: 0;
                padding-left: 20px;
                padding-right: 20px;
                -webkit-box-shadow: none;
                box-shadow: none;
                text-shadow: none;
            }

            #login #backtoblog a, #login #nav a, #login label, #login #login_error, #login .message {
                color: <?php echo ($theme_dark == 1) ? '#FFF' : '#333'; ?>;
            }

            #login form .input, #login input[type=text], input[type=radio], input[type=checkbox] {
                border-color: <?php echo $border_color ?>;
                box-shadow: none;
                background: <?php echo ($theme_style == 'classic') ? '#f6f6f6' : 'transparent'; ?>;
            }

            #login form .input, #login input[type=text] {
                border: 0;
            <?php echo ($theme_style == 'classic') ? 'border-left' : 'border-bottom'; ?>: <?php echo ($theme_style == 'classic') ? '2px' : '1px'; ?> solid <?php echo $border_color ?>;
                margin: 0;
                transition: all .4s cubic-bezier(.25, .8, .25, 1);
            }

            #login form p {
                position: relative;
                margin-bottom: 30px
            }

            <?php if ($theme_style == 'material') : ?>

            #login form p:not(.forgetmenot) label {
                position: absolute;
                bottom: 100%;
                left: 0;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                width: 100%;
                pointer-events: none;
                -webkit-font-smoothing: antialiased;
                z-index: 1;
                transform: translate3d(0, 28px, 0) scale(1);
                -webkit-transition: -webkit-transform .4s cubic-bezier(.25, .8, .25, 1);
                transform-origin: left top;
            }

            #login form p:not(.forgetmenot).has-focus label {
                font-size: 16px;
                -webkit-transform: translate3d(0, 6px, 0) scale(.75);
                transform: translate3d(0, 6px, 0) scale(.75);
            }

            #login form p:not(.forgetmenot).has-focus input {
                border-bottom-color: #0085ba;
            }

            #login form p input[type=text] {
                color: <?php echo ($theme_dark == 1) ? '#FFF' : '#333'; ?>;
            }

            <?php endif; ?>



            </style><?php
		}


		/**
		 * Login Custom logo url
		 * @return mixed
		 */
		public function shokola_logo_url_login() {
			return get_option( 'shokola_logo_url' );
		}


		/**
		 * Login and BO - Favico
		 */
		public function shokola_custom_favicon() {
			$upload_dir          = wp_upload_dir();
			$upload_dir_base_url = $upload_dir['baseurl'];
			$favico_name         = get_option( 'shokola_favico_name' );
			if ( $favico_name != '' ) {
				$favico_src = $favico_name;
				echo '<link rel="shortcut icon" type="image/x-icon" href="' . $favico_src . '" />';
				echo '<link rel="apple-touch-icon" href="' . $favico_src . '" />';
			}
		}


		/**
		 * Helpers - Color Difference
		 *
		 * Description : algorithm that works by summing up the differences between the three color components red, green and blue. A value higher than 500 is recommended for good readability.
		 */
		public function helper_color_diff( $R1, $G1, $B1, $R2, $G2, $B2 ) {
			return max( $R1, $R2 ) - min( $R1, $R2 ) + max( $G1, $G2 ) - min( $G1, $G2 ) + max( $B1, $B2 ) - min( $B1, $B2 );
		}

		public function helper_color_diff_hexa( $color1, $color2 ) {
			$color1_array = $this->helper_color_hex_to_dec( $color1 );
			$color2_array = $this->helper_color_hex_to_dec( $color2 );

			$diff = $this->helper_color_diff( $color1_array[0], $color1_array[1], $color1_array[2], $color2_array[0], $color2_array[1], $color2_array[2] );

			return $diff;
		}

		public function helper_color_hex_to_dec( $color ) {
			if ( $color[0] == '#' ) {
				$color = substr( $color, 1 );
			}

			if ( strlen( $color ) == 6 ) {
				list( $r, $g, $b ) = array( $color[0] . $color[1], $color[2] . $color[3], $color[4] . $color[5] );
			} elseif ( strlen( $color ) == 3 ) {
				list( $r, $g, $b ) = array( $color[0] . $color[0], $color[1] . $color[1], $color[2] . $color[2] );
			} else {
				return false;
			}

			$r = hexdec( $r );
			$g = hexdec( $g );
			$b = hexdec( $b );

			return array( $r, $g, $b );
		}
	}
}

register_activation_hook( __FILE__, array( 'Shokola_Custom_WhiteLabel', 'my_plugin_activate' ) );

// Set plugin instance
$shokola_custom_whitelabel = new Shokola_Custom_WhiteLabel();


