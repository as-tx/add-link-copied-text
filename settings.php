<?php if ( ! defined( 'WPINC' ) ) {
	die;
}

/*
Plugin Name: Add Link to Copied Text
Plugin URI: https://www.astech.solutions/wordpress-javascript-jquery-plugins/add-link-copied-text/
Description: Add a link to your website/page whenever someone copies text from your website. Alternatively, protect visitors from stealing your content.
Version: 2.0
Author: AS Tech Solutions
Author URI: https://www.astech.solutions/
Text Domain: add-link-copied-text
Domain Path: /languages
License: GPLv2 or later
*/

if ( ! class_exists( 'ftAddlink' ) ) {
	class ftAddlink {

		private $defaults = array(
			'readmore'          => '', /* Label to prepend */
			'addlinktosite'     => false, /* Link to site instead of page/post */
			'addsitename'       => false, /* Add site title to link text */
			'breaks'            => 2, /* Number of <br /> tags to insert before the link */
			'cleartext'         => false, /* Don't let user copy my content */
			'reloption'         => 'na', /* Add a rel attribute to link */
			'replaced_text'     => '', /* Replace copied text with */
			'target'            => false, /* Open link in new window/tab */
			'usesitenameaslink' => true, /* Add site title as a separate link */
			'usetitle'          => false, /* Use page/post title as link text */
		);

		private $option_name = 'ftAddlink_options';
		private $rel_attr = array(
			'na'        => 'Don\'t add (Recommended)',
			'canonical' => 'Canonical',
			'nofollow'  => 'No follow (Not recommended)',
		);

		// constructor
		function __construct() {

			/* if condition below is not required since it's an option page under WP Settings menu*/
			//if ( is_admin() ){
			add_action( 'admin_menu', array( &$this, 'ftAddlink_menu' ) );
			add_action( 'admin_init', array( &$this, 'ftAddlink_admininit' ) );
			//}

			add_action( 'init', array( &$this, 'ftAddlink_init' ) );
			add_action( 'wp_head', array( &$this, 'add_script' ) );

		}

		// function for init action hook
		function ftAddlink_init() {
			load_plugin_textdomain( 'add-link-copied-text', false, basename( dirname( __FILE__ ) ) . '/languages/' );
			$this->defaults['readmore'] = __( 'Continue reading at', 'add-link-copied-text' );
		}

		// adding menu item in admin menu
		function ftAddlink_menu() {

			add_options_page( ucwords( esc_html__( 'Add link Settings', 'add-link-copied-text' ) ), esc_html__( 'Add Link', 'add-link-copied-text' ), 'manage_options', $this->option_name, array(
				$this,
				'ftAddlink_display_settings'
			) );

		}

		//register_setting( $option_group, $option_name, $sanitize_callback );
		function ftAddlink_admininit() {
			register_setting( $this->option_name, $this->option_name, array( $this, 'options_validate' ) );
		}

		function options_validate( $input ) {

			$valid = array();

			$valid['readmore']      = isset( $input['readmore'] ) ? sanitize_text_field( $input['readmore'] ) : $this->defaults['readmore'];
			$valid['breaks']        = isset( $input['breaks'] ) ? sanitize_text_field( $input['breaks'] ) : $this->defaults['breaks'];
			$valid['replaced_text'] = isset( $input['replaced_text'] ) ? $input['replaced_text'] : $this->defaults['replaced_text'];
			$valid['reloption']     = isset( $this->rel_attr[ $input['reloption'] ] ) ? $input['reloption'] : $this->defaults['reloption'];
			$fields                 = array( 'addlinktosite', 'usetitle', 'cleartext', 'target' );
			foreach ( $fields as $field ) {
				$valid[ $field ] = isset( $input[ $field ] ) ? (bool) $input[ $field ] : false;
			}
			$valid['usesitenameaslink'] = isset( $input['usesitenameaslink'] ) && ! $valid['addlinktosite'] ? (bool) $input['usesitenameaslink'] : false;
			$valid['addsitename']       = isset( $input['addsitename'] ) && ! $valid['usesitenameaslink'] ? (bool) $input['addsitename'] : false;

			if ( ! ( ctype_digit( $valid['breaks'] ) || is_int( $valid['breaks'] ) ) || (int) $valid['breaks'] < 0 ) {
				// if ( strlen( $valid['breaks'] ) == 0 || $valid['breaks'] < 0 ) {
				add_settings_error(
					'breaks',                     // Setting title
					'breaks_texterror',            // Error ID
					esc_html__( 'Please enter a valid integer number', 'add-link-copied-text' ),     // Error message
					'error'                         // Type of message
				);

				$valid['breaks'] = $this->defaults['breaks'];
			}

			return $valid;
		}

		function ftAddlink_display_settings() {
			$options = wp_parse_args( get_option( $this->option_name, $this->defaults ), $this->defaults );
			?>
            <style>
                .form-table th {
                    width: 40%
                }
            </style>
            <div class="wrap">
                <h2><?php esc_html_e( 'Add Link On Copy Options', 'add-link-copied-text' ); ?></h2>
                <form method="post" action="options.php">
					<?php settings_fields( $this->option_name ); ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e( 'Label to prepend', 'add-link-copied-text' ); ?>:</th>
                            <td>
                                <input type="text" name="<?php echo $this->option_name ?>[readmore]" value="<?php esc_attr_e( $options['readmore'], 'add-link-copied-text' ); ?>"/>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e( 'Number of &lt;br /&gt; tags to insert before the link', 'add-link-copied-text' ); ?>:</th>
                            <td><input type="text" name="<?php echo $this->option_name ?>[breaks]" value="<?php echo $options['breaks']; ?>"/></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e( 'Open link in new window/tab', 'add-link-copied-text' ); ?>:</th>
                            <td><input type="checkbox" name="<?php echo $this->option_name ?>[target]" <?php checked( $options['target'] ); ?>/>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e( 'Add a rel attribute to link', 'add-link-copied-text' ); ?>:</th>
                            <td>
                                <select name="<?php echo $this->option_name ?>[reloption]">
									<?php
									foreach ( $this->rel_attr as $opt => $v ) {
										printf( '<option value="%s" %s>%s</option>', $opt, selected( $options['reloption'], $opt, false ), esc_html__( $v, 'add-link-copied-text' ) );
									}
									?>
                                </select>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e( 'Link to site instead of page/post', 'add-link-copied-text' ); ?>:</th>
                            <td><input type="checkbox" class="setchk setchklink" name="<?php echo $this->option_name ?>[addlinktosite]" <?php checked( $options['addlinktosite'] ); ?>/>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e( 'Use page/post title as link text', 'add-link-copied-text' ); ?>:</th>
                            <td><input type="checkbox" name="<?php echo $this->option_name ?>[usetitle]" <?php checked( $options['usetitle'] ); ?>/>
                            </td>
                        </tr>
                        <tr valign="top"<?php if ( $options['addlinktosite'] ) echo ' style="opacity:0.5;"'; ?>>
                            <th scope="row"><?php esc_html_e( 'Add site title as a separate link', 'add-link-copied-text' ); ?>:</th>
                            <td><input type="checkbox" class="setchk" name="<?php echo $this->option_name ?>[usesitenameaslink]" <?php checked( $options['usesitenameaslink'] ); disabled( $options['addlinktosite'] ); ?>/></td>
                        </tr>
                        <tr valign="top"<?php if ( $options['usesitenameaslink'] ) echo ' style="opacity:0.5;"'; ?>>
                            <th scope="row"><?php esc_html_e( 'Add site title to link text', 'add-link-copied-text' ); ?>:</th>
                            <td><input type="checkbox" name="<?php echo $this->option_name ?>[addsitename]" <?php checked( $options['addsitename'] ); disabled( $options['usesitenameaslink'] ); ?> /></td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e( 'Replace copied text with', 'add-link-copied-text' ); ?>:</th>
                            <td><textarea name="<?php echo $this->option_name ?>[replaced_text]" rows="5" cols="50"><?php echo esc_textarea( $options['replaced_text'] ); ?></textarea>
                            </td>
                        </tr>
                        <tr valign="top">
                            <th scope="row"><?php esc_html_e( 'OR', 'add-link-copied-text' ); ?><br/><br/><span style="color: red;"><?php esc_html_e( 'Don\'t let user copy my content', 'add-link-copied-text' ); ?>:</span></th>
                            <td><br/><br/><input type="checkbox" name="<?php echo $this->option_name ?>[cleartext]" <?php checked( $options['cleartext'] ); ?>/>
                            </td>
                        </tr>
                    </table>
					<?php submit_button(); ?>
                </form>
                <script>
                    jQuery(document).ready(function () {
                        jQuery('.setchk').change(function () {
                            var obj = jQuery(this),
                                elm = obj.closest('tr').next();
                            if (obj.hasClass('setchklink'))
                                elm = elm.next();
                            if (this.checked) {
                                elm.css({opacity: '0.5'}).find('input[type=checkbox]').attr({
                                    'checked': false,
                                    'disabled': true
                                });
                                if (obj.hasClass('setchklink'))
                                    elm.next().removeAttr('style').find('input[type=checkbox]').removeAttr('disabled');
                            }
                            else
                                elm.removeAttr('style').find('input[type=checkbox]').removeAttr('disabled');
                        });
                    });
                </script>
            </div>
			<?php
		}

		// enqueue front-end script
		function add_script() {
			wp_register_script( 'add_linkoncopy', plugins_url( basename( dirname( __FILE__ ) ) ) . '/assets/add_link.js' );
			wp_enqueue_script( 'add_linkoncopy' );

			$options = wp_parse_args( get_option( $this->option_name, $this->defaults ), $this->defaults );

			/*
			Not escaping data since wp_localize_script() decodes HTML entities and wp_json_encode also does kind of sanitization.
			Entity encoding moved to JS.
			*/
			$params = wp_parse_args( array(
				'sitename'  => get_bloginfo( 'name' ),
				'siteurl'   => get_bloginfo( 'url' ),
				'frontpage' => is_home() || is_front_page()
			), $options );
			if ( $options['usetitle'] === true ) {
				if ( is_home() || is_front_page() ) {
					$params['pagetitle']   = $params['sitename'];
					$params['addsitename'] = false;
				}
				if ( is_singular() ) {
					global $post;
					$params['pagetitle'] = get_the_title( $post->ID );
				}
			}

			$params['replaced_text'] = nl2br( $params['replaced_text'] );

			wp_localize_script( 'add_linkoncopy', 'astx_add_link_copied_text', $params );
		}
	} // class ends here

	$ftAddlink = new ftAddlink();

}// top most if condition ends here