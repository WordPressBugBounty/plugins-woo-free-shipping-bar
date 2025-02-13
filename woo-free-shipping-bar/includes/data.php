<?php
if ( ! class_exists( 'WFSPB_F_Data' ) ) {
	class WFSPB_F_Data {
		public $params, $default, $cache =[];
        public static $instance = null,$allow_html = null;
        
        public static function get_instance( $new = false ) {
            if ( $new || null == self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }

		public function __construct() {
			global $woocommerce_free_shipping_settings;

			if ( ! $woocommerce_free_shipping_settings ) {
				$woocommerce_free_shipping_settings = get_option( 'wfspb-param', array() );
			}

			$this->params = $woocommerce_free_shipping_settings;

			$this->default        = array(
				/*General*/
				'enable'              => 0,
				'default-zone'        => '',

				/*Design*/
				'bg-color'               => '#4dd2c6',
				'text-color'          => '#FFFFFF',
				'link-color'             => '#ffdf77',
				'font'                   => 'Architects Daughter',
				'font-size'           => 16,
				'text-align'          => 'center',
				'enable-progress'     => 0,
				'bg-color-progress'      => '#F3F3F3',
				'bg-current-progress'    => '#B8A16E',
				'progress-text-color'    => '#fff',
				'font-size-progress'  => '11',
				'progress_effect'     => 0,
				'position'            => 0,
				'style'               => 1,
				'custom_css'          => '',

				/*Message*/
				'announce_system'        => 'Free shipping for billing over {min_amount}',
				'message_purchased'      => 'You have purchased {total_amounts} of {min_amount}',
				'message_success'        => 'Congratulation! You have got free shipping. Go to {checkout_page}',
				'message_error'          => 'You are missing {missing_amount} to get Free Shipping. Continue {shopping}',
				/*Effect*/
				'show-giftbox'        => 0,
			);
			if (isset($woocommerce_free_shipping_settings['announce-system']['default'])){
				$woocommerce_free_shipping_settings['announce_system'] = $woocommerce_free_shipping_settings['announce-system']['default'];
				$woocommerce_free_shipping_settings['message_purchased'] = isset($woocommerce_free_shipping_settings['message-purchased']['default'])? $woocommerce_free_shipping_settings['message-purchased']['default'] : $this->default['message_purchased'];
				$woocommerce_free_shipping_settings['message_success'] = isset($woocommerce_free_shipping_settings['message-success']['default'])? $woocommerce_free_shipping_settings['message-success']['default']: $this->default['message_success'];
				$woocommerce_free_shipping_settings['message_error'] = isset($woocommerce_free_shipping_settings['message-error']['default'])? $woocommerce_free_shipping_settings['message-error']['default']: $this->default['message_error'];
				unset($woocommerce_free_shipping_settings['announce-system']);
				unset($woocommerce_free_shipping_settings['message-purchased']);
				unset($woocommerce_free_shipping_settings['message-success']);
				unset($woocommerce_free_shipping_settings['message-error']);
				update_option( 'wfspb-param',$woocommerce_free_shipping_settings);
			}
			$this->params = wp_parse_args( $woocommerce_free_shipping_settings, $this->default ) ;

		}
		public function get_params( $name = '',  $default = false) {
			if ( ! $name ) {
				return apply_filters( 'woocommerce_free_shipping_bar_settings_args',$this->params);
			}
			$name_filter = 'woocommerce_free_shipping_bar_get_option_' . $name;
			$result = $this->params[ $name ] ?? $default;
			return apply_filters( $name_filter, $result) ;
		}
		public function get_shipping_bar_message( $shipping_bar_info,$messages = []){
			$settings = self::get_instance();
			$message_class = 'wfspb-message-in-shop';
			if (empty($shipping_bar_info)){
				if (is_checkout() ||  is_cart() || !empty($messages['get_message_error'])){
					$message_class = 'wfspb-message-in-cart-checkout';
				}
				return apply_filters('wfspb_get_shipping_bar_message','<div id="wfspb-main-content" class="'.$message_class.'"></div>','', $shipping_bar_info,'', $messages);
			}
			$min_amount = max(0,floatval($shipping_bar_info['min_amount'] ?? 0));
			if (!$min_amount){
				$message_class = 'wfspb-message-success';
				$result = $settings->get_params('message_success');
				$checkout_link_text = apply_filters( 'wfspb_filter_checkout_link_text', esc_html__('Checkout' , 'woo-free-shipping-bar' ));
				$checkout = '<a class="vi-wcaio-sidebar-cart-bt-nav-checkout" href="' . wc_get_checkout_url() . '" title="' . esc_html__( 'Checkout', 'woo-free-shipping-bar' ) . '">' . esc_html( $checkout_link_text ) . '</a>';
				$result = str_replace( ['{checkout_page}'], [$checkout], $result );
				$result = do_shortcode($result);
				return apply_filters('wfspb_get_shipping_bar_message','<div id="wfspb-main-content" class="'.$message_class.'">'.$result.'</div>','', $shipping_bar_info,'', $messages);
			}
			$current_total = max(0,floatval($shipping_bar_info['current_total'] ?? 0));
			$min_amount_html = '<b id="wfspb_min_order_amount">' . wc_price( $min_amount ) . '</b>';
			if (!$current_total || !empty($messages['get_announce_system']) ){
				$result = $settings->get_params('announce_system');
				$result = str_replace( '{min_amount}', $min_amount_html, $result );
			}elseif ($current_total < $min_amount){
				$missing_amount = $min_amount - $current_total;
				if (is_checkout() ||  is_cart() || !empty($messages['get_message_error'])){
					$message_class = 'wfspb-message-in-cart-checkout';
					$result = $settings->get_params('message_error');
					$shopping_link_text = apply_filters( 'wfspb_filter_shopping_link_text', esc_html__('Shopping', 'woo-free-shipping-bar' ) );
					$shopping = '<a class="" href="' . get_permalink( get_option( 'woocommerce_shop_page_id' ) ) . '">' . esc_html( $shopping_link_text ) . '</a>';
					$missing_amount_html = '<b id="wfspb_missing_amount">' . wc_price( $missing_amount ) . '</b>';
					$result = str_replace( ['{missing_amount}','{shopping}'], [$missing_amount_html,$shopping], $result );
				}else{
					$result = $settings->get_params('message_purchased');
					$current_total_html = '<b id="wfspb-current-amout">' . wc_price( $current_total ) . '</b>';
					$min_amount_html = '<b id="wfspb_min_order_amount">' . wc_price( $min_amount ) . '</b>';
					if ('incl' === get_option( 'woocommerce_tax_display_shop' ) && isset(wc()->cart) && !wc()->cart->display_prices_including_tax() ){
						$missing_amount = $this->get_price_including_tax( $missing_amount );
						$missing_amount_html  = '<b id="wfspb_missing_amount">' . wc_price( $missing_amount ) . esc_html__( '(incl. tax)', 'woo-free-shipping-bar' ) . '</b>';
					}else{
						$missing_amount_html = '<b id="wfspb_missing_amount">' . wc_price( $missing_amount ) . '</b>';
					}
					$cart_amount_html = '<b id="current-quantity">' .($shipping_bar_info['cart_contents_count']??'') . '</b>';//WC()->cart->cart_contents_count
					$result = str_replace( ['{total_amounts}', '{cart_amount}', '{min_amount}', '{missing_amount}'],
						[$current_total_html,$cart_amount_html,$min_amount_html,$missing_amount_html], $result );
				}
			}else{
				if (is_checkout() ||  is_cart() || !empty($messages['get_message_error'])){
					$message_class = 'wfspb-message-in-cart-checkout';
				}
				$message_class .= ' wfspb-message-success';
				$result = $settings->get_params('message_success');
				$checkout_link_text = apply_filters( 'wfspb_filter_checkout_link_text', esc_html__('Checkout' , 'woo-free-shipping-bar' ));
				$checkout = '<a class="vi-wcaio-sidebar-cart-bt-nav-checkout" href="' . wc_get_checkout_url() . '" title="' . esc_html__( 'Checkout', 'woo-free-shipping-bar' ) . '">' . esc_html( $checkout_link_text ) . '</a>';
				$result = str_replace( ['{checkout_page}'], [$checkout], $result );
			}
			if ($result){
				$result = do_shortcode($result);
			}
			return apply_filters('wfspb_get_shipping_bar_message','<div id="wfspb-main-content" class="'.$message_class.'">'.$result.'</div>','', $shipping_bar_info,'', $messages);
		}
		public function get_price_including_tax( $line_price, $round_mode = PHP_ROUND_HALF_UP ) {
			$return_price = $line_price;
			if ( ! wc_prices_include_tax() ) {
				$tax_rates = WC_Tax::get_rates( '' );
				$taxes     = WC_Tax::calc_tax( $line_price, $tax_rates, false );

				if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
					$taxes_total = array_sum( $taxes );
				} else {
					$taxes_total = array_sum( array_map( 'wc_round_tax_total', $taxes ) );
				}

				$return_price = round( $line_price + $taxes_total, wc_get_price_decimals(), $round_mode );
			} else {
				$tax_rates      = WC_Tax::get_rates( '' );
				$base_tax_rates = WC_Tax::get_base_tax_rates( '' );

				/**
				 * If the customer is excempt from VAT, remove the taxes here.
				 * Either remove the base or the user taxes depending on woocommerce_adjust_non_base_location_prices setting.
				 */
				if ( ! empty( WC()->customer ) && WC()->customer->get_is_vat_exempt() ) { // @codingStandardsIgnoreLine.
					$remove_taxes = apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ? WC_Tax::calc_tax( $line_price, $base_tax_rates, true ) : WC_Tax::calc_tax( $line_price, $tax_rates, true );

					if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
						$remove_taxes_total = array_sum( $remove_taxes );
					} else {
						$remove_taxes_total = array_sum( array_map( 'wc_round_tax_total', $remove_taxes ) );
					}

					$return_price = round( $line_price - $remove_taxes_total, wc_get_price_decimals(), $round_mode );

					/**
					 * The woocommerce_adjust_non_base_location_prices filter can stop base taxes being taken off when dealing with out of base locations.
					 * e.g. If a product costs 10 including tax, all users will pay 10 regardless of location and taxes.
					 * This feature is experimental @since 2.4.7 and may change in the future. Use at your risk.
					 */
				} elseif ( $tax_rates !== $base_tax_rates && apply_filters( 'woocommerce_adjust_non_base_location_prices', true ) ) {
					$base_taxes   = WC_Tax::calc_tax( $line_price, $base_tax_rates, true );
					$modded_taxes = WC_Tax::calc_tax( $line_price - array_sum( $base_taxes ), $tax_rates, false );

					if ( 'yes' === get_option( 'woocommerce_tax_round_at_subtotal' ) ) {
						$base_taxes_total   = array_sum( $base_taxes );
						$modded_taxes_total = array_sum( $modded_taxes );
					} else {
						$base_taxes_total   = array_sum( array_map( 'wc_round_tax_total', $base_taxes ) );
						$modded_taxes_total = array_sum( array_map( 'wc_round_tax_total', $modded_taxes ) );
					}

					$return_price = round( $line_price - $base_taxes_total + $modded_taxes_total, wc_get_price_decimals(), $round_mode );
				}
			}

			return $return_price;
		}
		public function get_shipping_bar_info(){
			$settings = self::get_instance();
			$result = [
				'min_amount' => 0,
				'current_percent' => 0,
				'current_total' => 0,
			];
			if (!isset(WC()->cart) || ( !WC()->cart->is_empty() && !WC()->cart->needs_shipping())){
				return apply_filters('wfspb_get_shipping_bar_info',[]);
			}
			$free_shipping_exist = $settings->get_free_shipping_zone();
			if (!is_array($free_shipping_exist) || empty($free_shipping_exist)){
				return apply_filters('wfspb_get_shipping_bar_info',[]);
			}
			if (isset(WC()->cart) && !empty(WC()->cart->get_applied_coupons())){
				$applied_coupons = WC()->cart->get_applied_coupons();
				$coupon_free_shipping = false;
				foreach ( $applied_coupons as $code ) {
					$coupon = new WC_Coupon( $code );
					if ($coupon->get_free_shipping()){
						$coupon_free_shipping = true;
						break;
					}
				}
				if ($coupon_free_shipping){
					return apply_filters('wfspb_get_shipping_bar_info',$result);
				}
			}
			$shipping_packages = WC()->cart->get_shipping_packages()[0]??[];
			$shipping_destination = $shipping_packages['destination']??[];
			$default_shipping_zone = $settings->get_params('default-zone');
			if (empty($shipping_destination['country']) && ( !is_numeric($default_shipping_zone) || !isset($free_shipping_exist[$default_shipping_zone]) )){
				return apply_filters('wfspb_get_shipping_bar_info',[]);
			}
			$country  = $shipping_destination['country'];
			if (!$country){
				$free_shipping_exist = [$default_shipping_zone => $free_shipping_exist[$default_shipping_zone]];
			}
			$state    = $shipping_destination['state'] ??'';
			$check_zone_default = false;
			$method_id   = apply_filters( 'wfspb_method_id', 'free_shipping' );
			if (isset($free_shipping_exist[0])){
				$check_zone_default = 0;
				unset($free_shipping_exist[0]);
			}
			$free_shipping_zone = '';
			foreach ($free_shipping_exist as $k => $v){
				if ($free_shipping_zone){
					break;
				}
				$zone = new WC_Shipping_Zone( $k );
				if (!$zone){
					continue;
				}
				if ($country){
					$check = false;
					$zone_locations = $zone->get_zone_locations();
					if (!is_array($zone_locations) || empty($zone_locations)){
						if (!$check_zone_default){
							$check_zone_default = $k;
						}
						continue;
					}
					foreach ($zone_locations as $item){
						$location_type = $item->type ??'';
						$location_code = $item->code ??'';
						if (!$location_code || !$location_type){
							continue;
						}
						switch ($location_type){
							case 'state':
								$state_info = explode(':',$location_code);
								if (!isset($state_info[1])){
									break;
								}
								if ($state_info[0] !== $country && !in_array($country, $settings->get_shipping_countries($state_info[0]))){
									break;
								}
								$check = $state && $state === $state_info[1];
								break;
							default:
								$check = $country === $location_code || in_array($country, $settings->get_shipping_countries($location_code));
						}
						if ($check){
							break;
						}
					}
					if (!$check){
						continue;
					}
				}
				$shipping_methods = $zone->get_shipping_methods();
				if ( is_array( $shipping_methods ) && !empty( $shipping_methods ) ) {
					foreach ( $shipping_methods as $shipping_method ) {
						if (!isset($shipping_method->id) || !isset($shipping_method->enabled)){
							continue;
						}
						if ( $shipping_method->id === $method_id  && $shipping_method->enabled === 'yes') {
							$free_shipping_zone = $k;
							$result['min_amount'] = floatval($shipping_method->min_amount ?? 0);
							$result['ignore_discounts'] = $shipping_method->ignore_discounts ?? '';
							break;
						}
					}
				}
			}
			if (!$free_shipping_zone && is_numeric($check_zone_default)){
				$zone = new WC_Shipping_Zone( $check_zone_default );
				if ($zone) {
					$shipping_methods = $zone->get_shipping_methods();
					if ( is_array( $shipping_methods ) && ! empty( $shipping_methods ) ) {
						foreach ( $shipping_methods as $shipping_method ) {
							if ( ! isset( $shipping_method->id ) || ! isset( $shipping_method->enabled ) ) {
								continue;
							}
							if ( $shipping_method->id === $method_id && $shipping_method->enabled === 'yes' ) {
								$free_shipping_zone         = 0;
								$result['min_amount']       = floatval($shipping_method->min_amount ?? 0);
								$result['ignore_discounts'] = $shipping_method->ignore_discounts ?? '';
								break;
							}
						}
					}
				}
			}
			if (!is_numeric($free_shipping_zone)){
				return apply_filters('wfspb_get_shipping_bar_info',[]);
			}
			$min_amount = floatval($result['min_amount']);
			if ($min_amount > 0 && !WC()->cart->is_empty()){
				$ignore_discounts = $result['ignore_discounts']??'';
				$current_total = (float) WC()->cart->get_displayed_subtotal();
				/*Compatible with YITH Together*/
				if ( class_exists( 'YITH_WFBT_Frontend' ) ) {
					$totals_var = WC()->cart->get_totals();
					$current_total      = $totals_var['total'] - $totals_var['shipping_total'] - $totals_var['shipping_tax'];
				}
				if ( WC()->cart->display_prices_including_tax() && 'no' === $ignore_discounts ) {
					$current_total -= WC()->cart->get_discount_tax();
				}
				if ( 'no' === $ignore_discounts ) {
					$current_total -= WC()->cart->get_discount_total();
				}
				$exclude_shipping = $settings->get_params( 'exclude-shipping-class' );
				if ( ! empty( $exclude_shipping ) ) {
					$cart_items =  WC()->cart->get_cart();
					$total_exclude = 0;
					if (is_array($cart_items) || !empty($cart_items)){
						foreach ($cart_items as $item){
							if ( !empty($item['data']) && in_array( $item['data']->get_shipping_class_id(), $exclude_shipping ) ) {
								$total_exclude += $item['data']->get_price() * $item['quantity'];
							}
						}
					}
					$current_total -= $total_exclude;
				}
				$result['current_total'] =  round($current_total, wc_get_price_decimals() );
				$result['current_percent'] = ( $current_total * 100 ) / $min_amount ;
			}
			return apply_filters('wfspb_get_shipping_bar_info',$result);
		}
		public function get_shipping_countries($region){
			if (isset($this->cache['get_shipping_countries'][$region])){
				return $this->cache['get_shipping_countries'][$region];
			}
			if (!isset($this->cache['get_shipping_countries'])){
				$this->cache['get_shipping_countries'] = [];
			}
			$this->cache['get_shipping_countries'][$region] = [];
			$shipping_continents = [];
			if (isset(WC()->countries)){
				$shipping_continents = WC()->countries->get_shipping_continents();
			}
			if (!empty($shipping_continents[$region]['countries'])){
				$this->cache['get_shipping_countries'][$region] = $shipping_continents[$region]['countries'];
			}
			if (!is_array($this->cache['get_shipping_countries'][$region])){
				$this->cache['get_shipping_countries'][$region] = [];
			}
			return apply_filters('wfspb_get_shipping_countries',$this->cache['get_shipping_countries'][$region], $region);
		}

		// Get woocommerce free shipping zone
		public function check_woo_shipping_zone() {
			if (isset($this->cache['check_woo_shipping_zone'])){
				return $this->cache['check_woo_shipping_zone'];
			}
			if (self::get_instance()->get_params( 'always-show-free' ) ) {
				return $this->cache['check_woo_shipping_zone'] = true;
			}
			$this->cache['check_woo_shipping_zone'] = !empty($this->get_free_shipping_zone());
			return $this->cache['check_woo_shipping_zone'];
		}
		public function get_free_shipping_zone(){
			if (isset($this->cache['woo_shipping_zone'])){
				return $this->cache['woo_shipping_zone'];
			}
			$method_id   = apply_filters( 'wfspb_method_id', 'free_shipping' );
			$zones = array();
			$zone                                                = new WC_Shipping_Zone( 0 );
			if ($zone) {
				$zones[ $zone->get_id() ]                            = $zone->get_data();
				$zones[ $zone->get_id() ]['formatted_zone_location'] = $zone->get_formatted_location();
				$zones[ $zone->get_id() ]['shipping_methods']        = $zone->get_shipping_methods();
			}
			$zones         = array_merge( $zones, WC_Shipping_Zones::get_zones() );
			$zone_exist = [];
			if (!empty($zones)) {
				foreach ( $zones as $k => $zone ) {
					$shipping_methods = $zone['shipping_methods'] ?? '';
					$zone_id          = $zone['zone_id'] ?? $zone['id'] ?? '';
					$zone_name        = $zone['zone_name'] ?? $zone_id;
					if ($zone_id === ''){
						continue;
					}
					if ( is_array( $shipping_methods ) && !empty( $shipping_methods ) ) {
						foreach ( $shipping_methods as $free_shipping ) {
							if (!isset($free_shipping->id) || !isset($free_shipping->enabled)){
								continue;
							}
							if ( $free_shipping->id === $method_id  && $free_shipping->enabled === 'yes'  && !isset($zone_exist[$zone_id]) ) {
								$zone_exist[$zone_id] = [
									'zone_order' => $k,
									'name' => $zone_name,
								];
							}
						}
					}
				}
			}
			$this->cache['woo_shipping_zone'] = apply_filters( 'wfspb_get_woo_shipping_zone',$zone_exist);
			return $this->cache['woo_shipping_zone'];
		}
		/**
		 * @param $tags
		 *
		 * @return array
		 */
		public static function filter_allowed_html( $tags = [] ) {
			if ( self::$allow_html && empty( $tags ) ) {
				return self::$allow_html;
			}
			$tags = array_merge_recursive( $tags, wp_kses_allowed_html( 'post' ), array(
				'input'  => array(
					'type'         => 1,
					'id'           => 1,
					'name'         => 1,
					'class'        => 1,
					'placeholder'  => 1,
					'autocomplete' => 1,
					'style'        => 1,
					'value'        => 1,
					'size'         => 1,
					'checked'      => 1,
					'disabled'     => 1,
					'readonly'     => 1,
					'data-*'       => 1,
				),
				'form'   => array(
					'method' => 1,
					'action' => 1,
				),
				'select' => array(
					'name'     => 1,
					'multiple' => 1,
				),
				'option' => array(
					'value'    => 1,
					'selected' => 1,
					'disabled'     => 1,
				),
				'style'  => array(
					'id'    => 1,
					'class' => 1,
					'type'  => 1,
				),
				'source' => array(
					'type' => 1,
					'src'  => 1
				),
				'video'  => array(
					'width'  => 1,
					'height' => 1,
					'src'    => 1
				),
				'iframe' => array(
					'width'           => 1,
					'height'          => 1,
					'allowfullscreen' => 1,
					'allow'           => 1,
					'src'             => 1
				),
			) );
			$tmp = $tags;
			foreach ( $tmp as $key => $value ) {
				if ( in_array( $key, array( 'div', 'span', 'a', 'form', 'select', 'option', 'table', 'tr', 'th', 'td' ) ) ) {
					$tags[ $key ] = wp_parse_args( [
						'width'  => 1,
						'height' => 1,
						'class'  => 1,
						'id'     => 1,
						'type'   => 1,
						'style'  => 1,
						'data-*' => 1,
					],$value);
				}
			}
			self::$allow_html = $tags;
			return self::$allow_html;
		}
		public static function implode_html_attributes( $raw_attributes ) {
			$attributes = array();
			foreach ( $raw_attributes as $name => $value ) {
				$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
			}
			return implode( ' ', $attributes );
		}

		public static function villatheme_render_field( $name, $field ) {
			if ( ! $name ) {
				return;
			}
			if ( ! empty( $field['html'] ) ) {
				echo $field['html'];

//			echo wp_kses($field['html'], self::filter_allowed_html());
				return;
			}
			$type  = $field['type'] ?? '';
			$value = $field['value'] ?? '';
			if ( ! empty( $field['prefix'] ) ) {
				$id = "wfsb-{$field['prefix']}-{$name}";
			} else {
				$id = "wfsb-{$name}";
			}
			$class             = $field['class'] ?? $id;
			$custom_attributes = array_merge( [
				'type'  => $type,
				'name'  => $name,
				'id'    => $id,
				'value' => $value,
				'class' => $class,
			], (array) ( $field['custom_attributes'] ?? [] ) );
			if ( ! empty( $field['input_label'] ) ) {
				$input_label_type = $field['input_label']['type'] ?? 'left';
				echo wp_kses( sprintf( '<div class="vi-ui %s labeled input">', ( ! empty( $field['input_label']['fluid'] ) ? 'fluid ' : '' ) . $input_label_type ), self::filter_allowed_html() );
				if ( $input_label_type === 'left' ) {
					echo wp_kses( sprintf( '<div class="%s">%s</div>', $field['input_label']['label_class'] ?? 'vi-ui label', $field['input_label']['label'] ?? '' ), self::filter_allowed_html() );
				}
			}
			switch ( $type ) {
				case 'checkbox':
					unset( $custom_attributes['type'] );
					echo wp_kses( sprintf( '
					<div class="vi-ui toggle checkbox">
						<input type="hidden" %s>
						<input type="checkbox" id="%s-checkbox" %s ><label></label>
					</div>', self::implode_html_attributes( $custom_attributes ), $id, $value ? 'checked' : ''
					), self::filter_allowed_html() );
					break;
				case 'select':
					$select_options = $field['options'] ?? '';
					$multiple       = $field['multiple'] ?? '';
					unset( $custom_attributes['type'] );
					unset( $custom_attributes['value'] );
					$custom_attributes['class'] = "vi-ui fluid dropdown {$class}";
					if ( $multiple ) {
						$value                         = (array) $value;
						$custom_attributes['name']     = $name . '[]';
						$custom_attributes['multiple'] = "multiple";
					}
					echo wp_kses( sprintf( '<select %s>', self::implode_html_attributes( $custom_attributes ) ), self::filter_allowed_html() );
					if ( is_array( $select_options ) && count( $select_options ) ) {
						foreach ( $select_options as $k => $v ) {
							$selected = $multiple ? in_array( $k, $value ) : ( $k == $value );
							echo wp_kses( sprintf( '<option value="%s" %s>%s</option>',
								$k, $selected ? 'selected' : '', $v ), self::filter_allowed_html() );
						}
					}
					printf( '</select>' );
					break;
				case 'textarea':
					unset( $custom_attributes['type'] );
					unset( $custom_attributes['value'] );
					echo wp_kses( sprintf( '<textarea %s>%s</textarea>', self::implode_html_attributes( $custom_attributes ), $value ), self::filter_allowed_html() );
					break;
				default:
					if ( $type ) {
						echo wp_kses( sprintf( '<input %s>', self::implode_html_attributes( $custom_attributes ) ), self::filter_allowed_html() );
					}
			}
			if ( ! empty( $field['input_label'] ) ) {
				if ( ! empty( $input_label_type ) && $input_label_type === 'right' ) {
					printf( '<div class="%s">%s</div>', esc_attr( $field['input_label']['label_class'] ?? 'vi-ui label' ), wp_kses_post( $field['input_label']['label'] ?? '' ) );
				}
				printf( '</div>' );
			}
		}

		public static function villatheme_render_table_field( $options ) {
			if ( ! is_array( $options ) || empty( $options ) ) {
				return;
			}
			if ( ! empty( $options['html'] ) ) {
				echo wp_kses( $options['html'], self::filter_allowed_html() );

				return;
			}
			if ( isset( $options['section_start'] ) ) {
				if ( ! empty( $options['section_start']['accordion'] ) ) {
					echo wp_kses( sprintf( '<div class="vi-ui styled fluid accordion%s">
                                            <div class="title%s">
                                                <i class="dropdown icon"> </i>
                                                %s
                                            </div>
                                        <div class="content%s">',
						! empty( $options['section_start']['class'] ) ? " {$options['section_start']['class']}" : '',
						! empty( $options['section_start']['active'] ) ? " active" : '',
						$options['section_start']['title'] ?? '',
						! empty( $options['section_start']['active'] ) ? " active" : ''
					),
						self::filter_allowed_html() );
				}
				if ( empty( $options['fields_html'] ) ) {
					echo wp_kses_post( '<table class="form-table">' );
				}
			}
			if ( ! empty( $options['fields_html'] ) ) {
				echo wp_kses( $options['fields_html'], self::filter_allowed_html() );
			} else {
				$fields = $options['fields'] ?? '';
				if ( is_array( $fields ) && count( $fields ) ) {
					foreach ( $fields as $key => $param ) {
						$type = $param['type'] ?? '';
						$name = $param['name'] ?? $key;
						if ( ! $name ) {
							continue;
						}
						if ( ! empty( $param['prefix'] ) ) {
							$id = "wfsb-{$param['prefix']}-{$name}";
						} else {
							$id = "wfsb-{$name}";
						}
						if ( empty( $param['not_wrap_html'] ) ) {
							if ( ! empty( $param['wrap_class'] ) ) {
								printf( '<tr class="%s"><th><label for="%s">%s</label></th><td>',
									esc_attr( $param['wrap_class'] ), esc_attr( $type === 'checkbox' ? $id . '-' . $type : $id ), wp_kses_post( $param['title'] ?? '' ) );
							} else {
								printf( '<tr><th><label for="%s">%s</label></th><td>', esc_attr( $type === 'checkbox' ? $id . '-' . $type : $id ), wp_kses_post( $param['title'] ?? '' ) );
							}
						}
						do_action( 'wfsb_before_option_field', $name, $param );
						self::villatheme_render_field( $name, $param );
						if ( ! empty( $param['custom_desc'] ) ) {
							echo wp_kses_post( $param['custom_desc'] );
						}
						if ( ! empty( $param['desc'] ) ) {
							printf( '<p class="description">%s</p>', wp_kses_post( $param['desc'] ) );
						}
						do_action( 'wfsb_after_option_field', $name, $param );
						if ( empty( $param['not_wrap_html'] ) ) {
							echo wp_kses_post( '</td></tr>' );
						}
					}
				}
			}
			if ( isset( $options['section_end'] ) ) {
				if ( empty( $options['fields_html'] ) ) {
					echo wp_kses_post( '</table>' );
				}
				if ( ! empty( $options['section_end']['accordion'] ) ) {
					echo wp_kses_post( '</div></div>' );
				}
			}
		}

		public static function enqueue_style( $handles = array(), $srcs = array(), $is_suffix = array(), $des = array(), $type = 'enqueue' ) {
			if ( empty( $handles ) || empty( $srcs ) ) {
				return;
			}
			$action = $type === 'enqueue' ? 'wp_enqueue_style' : 'wp_register_style';
			$suffix = WP_DEBUG ? '' : '.min';
			foreach ( $handles as $i => $handle ) {
				if ( ! $handle || empty( $srcs[ $i ] ) ) {
					continue;
				}
				$suffix_t = ! empty( $is_suffix[ $i ] ) ? '.min' : $suffix;
				$action( $handle, WFSPB_F_SHIPPING_CSS . $srcs[ $i ] . $suffix_t . '.css', ! empty( $des[ $i ] ) ? $des[ $i ] : array(), WFSPB_F_VERSION );
			}
		}

		public static function enqueue_script( $handles = array(), $srcs = array(), $is_suffix = array(), $des = array(), $type = 'enqueue', $in_footer = false ) {
			if ( empty( $handles ) || empty( $srcs ) ) {
				return;
			}
			$action = $type === 'register' ? 'wp_register_script' : 'wp_enqueue_script';
			$suffix = WP_DEBUG ? '' : '.min';
			foreach ( $handles as $i => $handle ) {
				if ( ! $handle || empty( $srcs[ $i ] ) ) {
					continue;
				}
				$suffix_t = ! empty( $is_suffix[ $i ] ) ? '.min' : $suffix;
				$action( $handle, WFSPB_F_SHIPPING_JS . $srcs[ $i ] . $suffix_t . '.js', ! empty( $des[ $i ] ) ? $des[ $i ] : array( 'jquery' ),
					WFSPB_F_VERSION, $in_footer );
			}
		}

	}
}
