<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WFSPB_F_FRONTEND_Frontend {
	protected $settings;
	public static $cache = [];

	public function __construct() {
		$this->settings = WFSPB_F_Data::get_instance();
		if ( ! $this->settings->get_params( 'enable' ) ) {
			return;
		}
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ) );
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'update_bar_message' ) );
		add_filter( 'woocommerce_update_order_review_fragments', array( $this, 'update_bar_message' ) );
		//wc block
		add_filter( 'woocommerce_after_calculate_totals', array( $this, 'block_update_cart' ) );
	}
	public function block_update_cart() {
        if (!function_exists('woocommerce_store_api_register_endpoint_data') || !class_exists('\Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema')){
            return;
        }
		woocommerce_store_api_register_endpoint_data(
			array(
				'endpoint'        => \Automattic\WooCommerce\StoreApi\Schemas\V1\CartSchema::IDENTIFIER,
				'namespace'       => 'wfspb_message',
				'data_callback'   => function(){
					$messages = $this->update_bar_message([]);
					return $messages;
				},
				'schema_callback' => function() {
					return array(
						'properties' => [
							'#wfspb-main-content.wfspb-message-in-shop'=>['type' => 'string'],
							'#wfspb-main-content.wfspb-message-in-cart-checkout'=>['type' => 'string'],
							'#wfspb-progress'=>['type' => 'string'],
						]
					);
				},
				'schema_type'     => ARRAY_A,
			)
		);
	}
	public function update_bar_message( $fragment ) {
		if ( ! is_array( $fragment ) ) {
			return $fragment;
		}
		$shipping_bar_info = $this->settings->get_shipping_bar_info();
		$message           = $this->settings->get_shipping_bar_message( $shipping_bar_info );
		if ( strpos( $message, 'wfspb-message-always-show' )  ) {
			$fragment['#wfspb-main-content'] = $message;
		} elseif ( strpos( $message, 'wfspb-message-in-shop' ) ) {
			$fragment['#wfspb-main-content.wfspb-message-in-shop']          = $message;
			$fragment['#wfspb-main-content.wfspb-message-in-cart-checkout'] = $this->settings->get_shipping_bar_message( $shipping_bar_info, [ 'get_message_error' => 1 ] );;
		}else{
			$fragment['#wfspb-main-content.wfspb-message-in-cart-checkout'] = $message;
		}
		$current_percent     = floatval( $shipping_bar_info['current_percent'] ?? 0 );
		$progress_class      = [ 'wfsb-style-' . $this->settings->get_params( 'style' ) ];
		if ( ! $current_percent || $current_percent > 100 ) {
			$progress_class[]      = 'wfsb-hidden';
		}
		ob_start();
		$this->render_progress_bar( $current_percent, $progress_class );
		$fragment['#wfspb-progress'] = ob_get_clean();

		return $fragment;
	}

	public function enqueue_script() {
		if ( ! $this->settings->check_woo_shipping_zone() ) {
			return;
		}
		$style = $this->settings->get_params( 'style' );
		$this->settings::enqueue_style(
			[
				'woocommerce-free-shipping-bar',
			],
			[
				'frontend-style',
			],
			[],
			[],
			'register'
		);
		$this->settings::enqueue_script(
			[
				'woocommerce-free-shipping-bar',
			],
			[
				'frontend',
			],
			[],
			[],
			'register'
		);
		$bg_color        = $this->settings->get_params( 'bg-color' );
		$text_color      = $this->settings->get_params( 'text-color' );
		$link_color      = $this->settings->get_params( 'link-color' );
		$text_align      = $this->settings->get_params( 'text-align' );
		$font            = $this->settings->get_params( 'font' );
		$font_size       = $this->settings->get_params( 'font-size' );
		if ( $font == 'Default' ) {
			$font = '';
		}
		if ( ! empty( $font ) ) {
			$font = str_replace( '+', ' ', $font );
			wp_register_style( 'woocommerce-free-shipping-bar-google-font', '//fonts.googleapis.com/css?family=' . $font . ':400,500,600,700', array(), WFSPB_F_VERSION );
		}
		$custom_css = stripslashes_from_strings_only( $this->settings->get_params( 'custom_css' ) );
		$custom_css .= "
				#wfspb-top-bar .wfspb-lining-layer{
					background-color: {$bg_color} !important;
				}
				#wfspb-progress.wfsb-style-3{
					background-color: {$bg_color} !important;
				}
				#wfspb-top-bar{
					color: {$text_color} !important;
					text-align: {$text_align} !important;
				}
				#wfspb-top-bar #wfspb-main-content{
					padding: 0 " . ( $font_size * 2 ) . "px;
					font-size: {$font_size}px !important;
					text-align: {$text_align} !important;
					color: {$text_color} !important;
				}
				#wfspb-top-bar #wfspb-main-content b span{
					color: {$text_color} ;
				}
				#wfspb-top-bar #wfspb-main-content a{
					color: {$link_color};
				}
				div#wfspb-close{
				font-size: {$font_size}px !important;
				line-height: {$font_size}px !important;
				}
				";
		if ( $font ) {
			$custom_css .= "
				#wfspb-top-bar{
					font-family: {$font} !important;
				}";
		}
		if ( $this->settings->get_params( 'enable-progress' ) ) {
			$bg_progress         = $this->settings->get_params( 'bg-color-progress' );
			$bg_current_progress = $this->settings->get_params( 'bg-current-progress' );
			$progress_text_color = $this->settings->get_params( 'progress-text-color' );
			$fontsize_progress   = $this->settings->get_params( 'font-size-progress' );
			$custom_css          .= "
					#wfspb-progress .wfspb-progress-background,.woocommerce-free-shipping-bar-order .woocommerce-free-shipping-bar-order-bar{
						background-color: {$bg_progress} !important;
					}
					#wfspb-current-progress,.woocommerce-free-shipping-bar-order .woocommerce-free-shipping-bar-order-bar .woocommerce-free-shipping-bar-order-bar-inner{
						background-color: {$bg_current_progress} !important;
					}
					#wfspb-top-bar > #wfspb-progress.wfsb-effect-2{
					outline-color:{$bg_current_progress} !important;
					}
					#wfspb-label{
						color: {$progress_text_color} !important;
						font-size: {$fontsize_progress}px !important;
					}
				";

			$custom_css .= $style == 2 ? "#wfspb-top-bar #wfspb-progress::before, #wfspb-top-bar #wfspb-progress::after{border-bottom-color: {$bg_color} !important;}" : '';
		}
		wp_add_inline_style( 'woocommerce-free-shipping-bar', $custom_css );
		$params = array(
			'mobile'          => $this->settings->get_params('detect-mobile') ? 1:'',
			'ajax_url'          => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'vifsb-nonce' ),
			'html_refresh'      => [
				'#wfspb-main-content.wfspb-message-in-shop',
				'#wfspb-main-content.wfspb-message-in-cart-checkout',
				'#wfspb-progress',
			],
		);
		wp_localize_script( 'woocommerce-free-shipping-bar', '_wfsb_params', $params );
		self::enqueue_scripts();
		add_action( 'wp_footer', array( $this, 'frontend_html' ) );
	}

	public function frontend_html() {
		$class_wrap        = [ 'displaying customized' ];
		$class_wrap[]      = $this->settings->get_params( 'position' ) ? 'bottom_bar' : 'top_bar';
		$shipping_bar_info = $this->settings->get_shipping_bar_info();
		$message           = $this->settings->get_shipping_bar_message(  $shipping_bar_info );
		if ( empty( $message ) ) {
			$class_wrap[] = 'wfspb-hidden';
		}
		?>
		<div id="wfspb-top-bar" class="<?php echo esc_attr( implode( ' ', $class_wrap ) ) ?>">
			<div class="wfspb-lining-layer">
				<?php echo wp_kses( $message, $this->settings::filter_allowed_html() ); ?>
			</div>
			<?php
			if ( $this->settings->get_params( 'enable-progress' ) ) {
				$current_percent = floatval( $shipping_bar_info['current_percent'] ?? 0 );
				$progress_class  = [ 'wfsb-style-' . $this->settings->get_params( 'style' ) ];
				if ( ! $current_percent || $current_percent > 100 ) {
					$progress_class[] = 'wfsb-hidden';
				}
				$this->render_progress_bar( $current_percent, $progress_class );
			}
			?>
		</div>
		<?php
		if ($this->settings->get_params('show-giftbox')){
			$giftbox_class =['wfspb-gift-box'];
			$gift_icon = 'free-delivery.png';
			$gift_custom_icon_url = WFSPB_F_SHIPPING_IMAGES.$gift_icon;
			?>
			<div class="<?php echo esc_attr(implode(' ', $giftbox_class))?>">
                <img src="<?php echo esc_url($gift_custom_icon_url)?>" alt="free-delivery">
			</div>
			<?php
		}
	}

	public function render_progress_bar( $current_percent, $progress_class ) {
		?>
		<div id="wfspb-progress" class="<?php echo esc_attr( implode( ' ', $progress_class ) ) ?>">
			<div class="wfspb-progress-background wfsb-effect-<?php echo esc_attr( $this->settings->get_params( 'progress_effect' ) ) ?>">
				<div id="wfspb-current-progress"
				     data-current_percent="<?php echo esc_attr( $current_percent ) ?>">
					<div id="wfspb-label"><?php echo esc_html( round( $current_percent, wc_get_price_decimals() ) ); ?>%</div>
				</div>
			</div>
		</div>
		<?php
	}

	public static function enqueue_scripts() {
		wp_enqueue_style( 'woocommerce-free-shipping-bar-google-font' );
		wp_enqueue_style( 'woocommerce-free-shipping-bar' );
		wp_enqueue_script( 'woocommerce-free-shipping-bar' );
	}
}