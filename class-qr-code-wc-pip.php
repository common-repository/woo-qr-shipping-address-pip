<?php
/**
 * Main and only class for this plugin
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * QR_Code_WC_PIP class.
 */
class QR_Code_WC_PIP {

	/**
	 * The QR Code size in pixels.
	 *
	 * @var string
	 */
	public $qr_size = 100;


	/**
	 * Constructor.
	 */
	public function __construct() {
		// Hook up to the init action
		add_action( 'init', array( $this, 'init' ) );
	}


	/**
	 * Initialize.
	 */
	public function init() {
		// Set QR Code size - You can define QR_WCDN_SIZE to any value you want on wp-config.php - Deprecated - filter used below now
		$this->qr_size = intval( defined( 'QR_WCDN_SIZE' ) ? QR_WCDN_SIZE : $this->qr_size );
		// WooCommerce Print Invoices & Packing lists (below shipping address) - https://woocommerce.com/products/print-invoices-packing-lists/
		add_filter( 'wc_pip_shipping_address', array( $this, 'wc_pip_shipping_address' ), 10, 3 );
		// WooCommerce Print Invoice & Delivery Note (below shipping address) - https://wordpress.org/plugins/woocommerce-delivery-notes/
		add_filter( 'wcdn_address_shipping', array( $this, 'wcdn_address_shipping' ), 10, 2 );
		// WooCommerce PDF Invoices, Packing Slips, Delivery Notes & Shipping Labels - https://www.webtoffee.com/product/woocommerce-pdf-invoices-packing-slips/
		add_filter( 'wf_change_shipping_address_format', array( $this, 'wf_change_shipping_address_format' ), 10, 2 );
		// PDF Invoices & Packing Slips for WooCommerce - https://wordpress.org/plugins/woocommerce-pdf-invoices-packing-slips/
		add_action( 'wpo_wcpdf_after_shipping_address', array( $this, 'wpo_wcpdf_after_shipping_address' ), 10, 2 );
		// WooCommerce order edit screen
		add_action( 'woocommerce_admin_order_data_after_shipping_address', array( $this, 'admin_order_data_after_shipping_address' ), 10 );
	}

	/**
	 * Get Google Maps URL
	 *
	 * @param object $order The WooCommerce order.
	 */
	private function get_google_maps_url( $order ) {
		add_filter( 'woocommerce_formatted_address_replacements', array( $this, 'shipping_address_remove_names' ), 10, 2 );
		$qr_address = trim( str_replace( '<br>', ' ', str_replace( '<br/>', ' ', $order->get_formatted_shipping_address() ) ) );
		remove_filter( 'woocommerce_formatted_address_replacements', array( $this, 'shipping_address_remove_names' ), 10, 2 );
		return 'https://maps.google.com/maps?daddr=' . rawurlencode( $qr_address );
	}

	/**
	 * QR Code Image Link from qrserver.com
	 *
	 * @param object $order The WooCommerce order.
	 * @param array  $args  Optional arguments to pass to this function.
	 */
	private function qr_code_image_link( $order, $args = array() ) {
		$google_maps_url = $this->get_google_maps_url( $order );
		$qr_size         = intval( apply_filters( 'woo_qr_pip_qr_size', $this->qr_size ) );
		$qr_image_url    = 'https://api.qrserver.com/v1/create-qr-code/?size=' . intval( $qr_size ) . 'x' . intval( $qr_size ) . '&data=' . rawurlencode( $google_maps_url );
		if ( isset( $args['format'] ) ) {
			$qr_image_url .= '&format=' . trim( $args['format'] );
		}
		return $qr_image_url;
	}

	/**
	 * QR Code Image tag
	 *
	 * @param object $order The WooCommerce order.
	 * @param array  $args Optional arguments to pass to this function.
	 * @param bool   $with_link Should include a link to Google maps?.
	 */
	private function qr_code_image_tag( $order, $args = array(), $with_link = false ) {
		$qr_size = intval( apply_filters( 'woo_qr_pip_qr_size', $this->qr_size ) );
		$qr_link = $this->qr_code_image_link( $order, $args );
		$tag     = '<img src="' . esc_url( $qr_link ) . '" width="' . intval( $qr_size ) . '" height="' . intval( $qr_size ) . '"/>';
		if ( $with_link ) {
			$tag = '<a href="' . esc_url( $this->get_google_maps_url( $order ) ) . '" target="_blank">' . $tag;
			// Avoid translations on this simple plugin
			// $tag .= '<br/><small>' . __( 'Open in Google Maps', '' ) . '</small>'; // phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			$tag .= '</a>';
		}
		return $tag;
	}

	/**
	 * Remove names and company from the shipping address
	 *
	 * @param array $array Array of replacements.
	 * @param array $args Array of arguments.
	 */
	public function shipping_address_remove_names( $array, $args ) {
		$array['{first_name}']       = '';
		$array['{last_name}']        = '';
		$array['{name}']             = '';
		$array['{company}']          = '';
		$array['{first_name_upper}'] = '';
		$array['{last_name_upper}']  = '';
		$array['{name_upper}']       = '';
		$array['{company_upper}']    = '';
		return $array;
	}

	/**
	 * WooCommerce Print Invoices & Packing lists (below shipping address)
	 *
	 * @param string $address The address string.
	 * @param string $type    Maybe the type of document.
	 * @param object $order The WooCommerce order.
	 */
	public function wc_pip_shipping_address( $address, $type, $order ) {
		return $address . '<br/><br/>' . $this->qr_code_image_tag( $order );
	}

	/**
	 * WooCommerce Print Invoice & Delivery Note (below shipping address)
	 *
	 * @param string $address The address string.
	 * @param object $order The WooCommerce order.
	 */
	public function wcdn_address_shipping( $address, $order ) {
		return $address . '<br/><br/>' . $this->qr_code_image_tag( $order );
	}

	/**
	 * WooCommerce PDF Invoices, Packing Slips, Delivery Notes & Shipping Labels
	 *
	 * @param string $shipping_address_data The address string.
	 * @param string $shipping_address ??.
	 */
	public function wf_change_shipping_address_format( $shipping_address_data, $shipping_address ) {
		if ( isset( $_GET['post'] ) && intval( $_GET['post'] ) > 0 && isset( $_GET['type'] ) && trim( $_GET['type'] ) !== '' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.MissingUnslash,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( $order = wc_get_order( intval( $_GET['post'] ) ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.Found,Squiz.PHP.DisallowMultipleAssignments.FoundInControlStructure,WordPress.Security.NonceVerification.Recommended
				if ( in_array(
					trim( $_GET['type'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					apply_filters(
						'woo_qr_pip_wf_document_types',
						array(
							'print_invoice',
							'download_invoice',
							'print_packing_list',
							'print_delivery_note',
							'print_dispatch_label',
						)
					),
					true
				) ) {
					$shipping_address_data .= '<br/><br/>' . $this->qr_code_image_tag( $order );
				}
			}
		}
		return $shipping_address_data;
	}

	/**
	 * PDF Invoices & Packing Slips for WooCommerce
	 *
	 * @param string $type  The document type.
	 * @param object $order The WooCommerce order.
	 */
	public function wpo_wcpdf_after_shipping_address( $type, $order ) {
		if ( $type === 'packing-slip' ) {
			// JPG because we got an error with PNG
			echo '<br/><br/>' . $this->qr_code_image_tag( $order, array( 'format' => 'jpg' ) ) . '<br/><br/>'; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}

	/**
	 * WooCommerce order edit screen
	 *
	 * @param object $order The WooCommerce order.
	 */
	public function admin_order_data_after_shipping_address( $order ) {
		if ( apply_filters( 'woo_qr_pip_wf_order_edit_screen', false ) ) {
			?>
			<p class="qr-code-wc-pip">
				<?php echo $this->qr_code_image_tag( $order, array(), true ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</p>
			<?php
		}
	}

}

/* If you're reading this you must know what you're doing ;-) Greetings from sunny Portugal! */

