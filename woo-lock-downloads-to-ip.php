<?php
/*
Plugin Name: Woocommerce - Lock Downloads to IP
Plugin URI: http://amansaini.me
Description: An extension for Woocommerce to lock file downloads to the IP address used to purchase the file
Author: Aman Saini
Version: 1.1
Author URI: http://amansaini.me
Text Domain: wooiplock
Domain Path: languages
*/


class Woo_Lock_Downloads_To_IP {

	function __construct() {

		// internationalization
		add_action( 'init', array( $this, 'textdomain' ) );

		//Add admin js
		add_action( 'admin_enqueue_scripts',  array( $this,'add_wooiplock_js'));

		//Add new field type 'ip_range'
		add_action('woocommerce_admin_field_ip_range',  array( $this,'add_range_field'));

		//Save values on update
		add_action('woocommerce_update_option_ip_range',  array( $this,'save_range_field'));

		//Add Options to genaral settings in admin
		add_filter('woocommerce_general_settings', array( $this, 'add_lock_download_options' ),10,1);

		// Check the IP during file download
		add_action( 'woocommerce_download_product', array( $this, 'check_ip' ),10,6 );

		add_action( 'show_user_profile', array( $this, 'show_ip_lock_bypass' ));
		add_action( 'edit_user_profile', array( $this, 'show_ip_lock_bypass' ));

		add_action( 'personal_options_update', array( $this, 'save_user_bypass_setting' ));
		add_action( 'edit_user_profile_update', array( $this, 'save_user_bypass_setting' ));

	}


	/**
	 * Load the plugin text domain for internationalization
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */

	public function textdomain() {

		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';

		// Load the translations
		load_plugin_textdomain( 'wooiplock', false, $lang_dir );

	}

	/**
	 * Add js for the plugin admin
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */

	public function add_wooiplock_js(){
		wp_enqueue_script('wooiplock_admin_js', plugins_url('/js/admin.js', __FILE__));

	}

	/**
	 * Add the options for user's to manage lock downloads on general setting tab
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function add_lock_download_options(  $fields ){

		$fields[] = array(  'name' => __( 'Download lock'), 'type' => 'title', '', 'id' => 'woo_lock_downloads_by_ip' );

		$fields[] = array(
		                  'name' => __( 'Lock downloads by IP ', 'wooiplock' ),
		                  'desc'      => __( "Check it to enable lock on downloads.", 'wooiplock' ),
		                  'id'        => 'wooiplock_enable',
		                  'type'      => 'checkbox',
		                  );

		$fields[] = array(
		                  'name' => __( 'Download Permission', 'wooms'),
		                  'desc'      => __( 'Select download permission type.', 'wooiplock'),
		                  'id'        => 'wooiplock_lock_type',
		                  'css'       => 'min-width:300px;',
		                  'type'      => 'select',
		                  'desc_tip'  => true,
		                  'options'   =>  array(
		                                        'user_ip'  => __( 'User IP Only', 'wooiplock' ),
		                                        'ip_range' => __( 'IP Range', 'wooiplock' ),
		                                        /* 'countries' => __( 'Countries', 'wooiplock' ),*/
		                                        )
		                  );

		$fields[] = array(
		                  'name' => __( 'IP Range', 'wooms'),
		                  'desc'      => __( 'Add the start and end IP range', 'wooiplock'),
		                  'id'        => 'ip_range',
		                  'css'       => 'max-width:150px;',
		                  'type'      => 'ip_range',
		                  'desc_tip' => true,
		                  );

		$fields[] = array(
		                  'name' => __( 'Error message', 'wooiplock'),
		                  'desc'      => __( 'The error message that will show to customer when they try to download from out of range IP ', 'wooiplock' ),
		                  'id'        => 'wooiplock_error',
		                  'type'      => 'textarea',
		                  'css'       => 'min-width:300px;',
		                  'default'	  =>  'You do not have permission to download this file because your IP address doesn\'t match our records. Please contact the administartor',
		                  'desc_tip' => true,
		                  );



/*		$fields[] = array(
		                  'name' => __( 'Countries', 'wooiplock'),
		                  'desc'      => __( 'Allow downloads to the user\'s of selected countires', 'wooiplock' ),
		                  'id'        => 'wooiplock_countries',
		                  'type'      => 'multi_select_countries',
		                  'css'       => 'min-width:300px;',
		                  'desc_tip' => true,
		                  );
*/


$fields[] = array( 'type' => 'sectionend', 'id' => 'woo_lock_downloads_by_ip_section' );

return $fields;

}


	/**
	 * Add a field type ip_range used above to produce IP start and end range fields in admin
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */

	public function add_range_field($value){

		global $woocommerce;

		$type 			= 'text';
		$option_value_start 	= woocommerce_settings_get_option( $value['id'].'_start', $value['default'] );
		$option_value_end 	= woocommerce_settings_get_option( $value['id'].'_end', $value['default'] );
		$class			= 'small-text';
		$lock_type = get_option('wooiplock_lock_type');
		$tip = '<img class="help_tip" data-tip="' . esc_attr( $value['desc'] ) . '" src="' . $woocommerce->plugin_url() . '/assets/images/help.png" height="16" width="16" />';

		?>

		<tr valign="top" id="ip_range_row" style="<?php echo $lock_type=='ip_range'?'':'display:none'  ?>">
			<th scope="row" class="titledesc">
				<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?></label>
				<?php echo $tip; ?>
			</th>
			<td class="forminp forminp-<?php echo sanitize_title( $value['type'] ) ?>">
				<input
				name="<?php echo esc_attr( $value['id'] ); ?>_start"
				id="<?php echo esc_attr( $value['id'] ); ?>_start"
				type="<?php echo esc_attr( $type ); ?>"
				style="<?php echo esc_attr( $value['css'] ); ?>"
				value="<?php echo esc_attr( $option_value_start ); ?>"
				class="<?php echo esc_attr( $value['class'] ); ?>"
				/>-
				<input
				name="<?php echo esc_attr( $value['id'] ); ?>_end"
				id="<?php echo esc_attr( $value['id'] ); ?>_end"
				type="<?php echo esc_attr( $type ); ?>"
				style="<?php echo esc_attr( $value['css'] ); ?>"
				value="<?php echo esc_attr( $option_value_end ); ?>"
				class="<?php echo esc_attr( $value['class'] ); ?>"
				/>
				<?php echo $description; ?>
			</td>
		</tr>

		<?php
	}


	public function save_range_field($value){


		update_option( 'ip_range_start', $_POST['ip_range_start'] );
		update_option( 'ip_range_end', $_POST['ip_range_end'] );
	}

/**
 *
 * Show checkbox on user profile to bypass IP lock
 *
 */

function show_ip_lock_bypass( $user ) { ?>

<h3>Download Lock Setting</h3>

<table class="form-table">

	<tr>
		<th><label for="iplock">Bypass Ip Lock</label></th>

		<td>
			<input type="checkbox" name="bypass_ip_lock" id="bypass_ip_lock" value="1" <?php  echo get_the_author_meta( 'bypass_ip_lock', $user->ID )==1?'checked="checked"':''; ?> class="checkbox" />
			<span class="description">Check it to let user bypass IP lock on downloads.</span>
		</td>
	</tr>

</table>
<?php }

/**
 * Save the Profile checkbox for bypass ip lock
 */

public function save_user_bypass_setting( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;

	update_usermeta( $user_id, 'bypass_ip_lock', $_POST['bypass_ip_lock'] );
}


	/**
	 * Check the IP address during file download and display an error if it doesn't match the purchase records
	 *
	 * @access      public
	 * @since       1.0
	 * @return      void
	 */
	public function check_ip( $email, $order_key, $product_id, $user_id, $download_id, $order_id ) {

		$lock_enabled = get_option( 'wooiplock_enable' );

		  $user_ID = get_current_user_id();

		 $ip_lock_bypass_user = get_user_meta( $user_ID, 'bypass_ip_lock', true );

		if( !empty( $lock_enabled ) && !$ip_lock_bypass_user ){

			$lock_error = get_option( 'wooiplock_error' );

			$ip_address = isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'] ;

			$order = new WC_Order( $order_id );

			$order_ip_arr =get_post_meta($order_id,'_customer_ip_address',false);

			$order_ip= $order_ip_arr[0];

			$lock_type = get_option( 'wooiplock_lock_type' );

			if( ( $lock_type == 'user_ip' ) && ( $order_ip != $ip_address ) ){

				wp_die( $lock_error, __( 'Error', 'wooiplock' ) );

			}else if( $lock_type == 'ip_range' ) {

				include_once('check_ip_range.php');

				$start_range = get_option( 'ip_range_start' );

				$end_range = get_option( 'ip_range_end' );

				$range = $start_range.'-'.$end_range;

				$in_range = ip_in_range($ip_address , $range);

				if( !$in_range ){

					wp_die( $lock_error, __( 'Error', 'wooiplock' ) );

				}

			}
		}

	}




}
new Woo_Lock_Downloads_To_IP();






