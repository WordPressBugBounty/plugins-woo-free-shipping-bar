<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WFSPB_F_ADMIN_Settings {
	protected $settings;
	protected $updated_sucessfully, $error;
	public function __construct() {
		$this->settings         = WFSPB_F_Data::get_instance();
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_script') );
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_init', array( $this, 'save_settings' ),11 );
	}
	public function admin_enqueue_script(){
		$page = isset( $_REQUEST['page'] ) ? wc_clean( $_REQUEST['page'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $page !== 'woocommerce_free_ship' ){
			return;
		}
		$this->settings::enqueue_style(
			array(
				'semantic-ui-button',
				'semantic-ui-checkbox',
				'semantic-ui-dropdown',
				'semantic-ui-segment',
				'semantic-ui-form',
				'semantic-ui-label',
				'semantic-ui-input',
				'semantic-ui-icon',
				'semantic-ui-table',
				'semantic-ui-message',
				'semantic-ui-menu',
				'semantic-ui-tab',
				'transition',
			),
			array(
				'button',
				'checkbox',
				'dropdown',
				'segment',
				'form',
				'label',
				'input',
				'icon',
				'table',
				'message',
				'menu',
				'tab',
				'transition',
			),
			array( 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1 )
		);
		$this->settings::enqueue_style(
			array(
				'woocommerce-free-shipping-bar-style',
				'woocommerce-free-shipping-bar-fontselect',
				'woocommerce-free-shipping-bar-minicolors',
			),
			array( 'admin-style', 'fontselect', 'minicolors' ),
			array()
		);
		$this->settings::enqueue_script(
			array(
				'woocommerce-free-shipping-bar-fontselect',
				'woocommerce-free-shipping-bar-address',
				'woocommerce-free-shipping-bar-minicolors',
				'semantic-ui-checkbox',
				'semantic-ui-dropdown',
				'semantic-ui-tab',
				'transition'
			),
			array(
				'fontselect',
				'address',
				'minicolors',
				'checkbox',
				'dropdown',
				'tab',
				'transition'
			),
			array( 1, 1,1, 1, 1, 1, 1, 1 )
		);
		$this->settings::enqueue_script(
			array( 'woocommerce-free-shipping-bar-admin' ),
			array( 'admin' ),
			array( 0 ),
		);
		//inline style Style tab
		$bg_color            = $this->settings->get_params( 'bg-color' );
		$text_color          = $this->settings->get_params( 'text-color' );
		$link_color          = $this->settings->get_params( 'link-color' );
		$text_align          =$this->settings->get_params( 'text-align' );
		$font                = $this->settings->get_params( 'font' );
		$font_size           = $this->settings->get_params( 'font-size' );
		$font_family         = str_replace( '+', ' ', $font );
		$bg_progress         = $this->settings->get_params( 'bg-color-progress' );
		$bg_current_progress = $this->settings->get_params( 'bg-current-progress' );
		$progress_text_color = $this->settings->get_params( 'progress-text-color' );
		$fontsize_progress   = $this->settings->get_params( 'font-size-progress' );

		$custom_css = "
					#wfspb-top-bar{
						background-color: {$bg_color};
						color: {$text_color};
						font-family: {$font_family};
					} 
					#wfspb-top-bar #wfspb-main-content{
						font-size: {$font_size}px;
						text-align: {$text_align};
					}
					div#wfspb-close{
						font-size: {$font_size}px;
						line-height: {$font_size}px;
					}
					#wfspb-top-bar #wfspb-main-content > a{
						color: {$link_color};
					}";
		$custom_css .= "
					#wfspb-progress{
						background-color: {$bg_progress};
						display: block !important;
					}
					#wfspb-current-progress{
						background-color: {$bg_current_progress};
					}
					#wfspb-label{
						color: {$progress_text_color};
						font-size: {$fontsize_progress}px;
					}
				";

		wp_add_inline_style( 'woocommerce-free-shipping-bar-style', $custom_css );
	}
	public function add_menu() {
		add_menu_page(
			__( 'WooCommerce Free Shipping Bar', 'woo-free-shipping-bar' ),
			__( 'WC F-Shipping Bar', 'woo-free-shipping-bar' ),
			'manage_options',
			'woocommerce_free_ship',
			array( $this, 'setting_page' ),
			'dashicons-backup',
			2
		);
	}
	public function setting_page(){
		$tabs       = array(
			'message'     => esc_html__( 'Message', 'woo-free-shipping-bar' ),
			'design'     => esc_html__( 'Design', 'woo-free-shipping-bar' ),
			'general'   => esc_html__( 'General', 'woo-free-shipping-bar' ),
			'assign'     => esc_html__( 'Assign', 'woo-free-shipping-bar' ),
			'effect'     => esc_html__( 'Effect', 'woo-free-shipping-bar' ),
		);
		$tab_active = array_key_first( $tabs );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Free Shipping Bar for WooCommerce', 'woo-free-shipping-bar' ); ?></h1>
			<?php
			if ( $this->error  ) {
				printf( '<div id="message" class="error"><p><strong>%s</strong></p></div>', esc_html(  $this->error ) );
			}
			if ( $this->updated_sucessfully  ) {
				printf( '<div id="message" class="updated"><p><strong>%s</strong></p></div>', esc_html__( 'Your settings have been saved!', 'woo-free-shipping-bar' ) );
			}
			$check_woo_shipping_zone =$this->settings->check_woo_shipping_zone();
			if (!$check_woo_shipping_zone){
				?>
				<div class="vi-ui message yellow">
					<p>
						<?php
						esc_html_e('We\'re sorry, but the shipping bar cannot be enabled right now because no free shipping zone has been set up.', 'woo-free-shipping-bar');
						?>
					</p>
					<p>
						<?php
						$link  = admin_url( 'admin.php?page=wc-settings&tab=shipping' );
						$mess0 = esc_html__( 'WooCommerce Shipping settings', 'woo-free-shipping-bar' );
						$mess1 = esc_html__( 'Please go to', 'woo-free-shipping-bar' );
						$mess2 = esc_html__( 'and then Add New a Shipping Zone with Free Shipping method (or Enable Free Shipping method) for your locationn before setting up the free shipping bar.', 'woo-free-shipping-bar' );
						echo wp_kses_post( sprintf( "%s <a href='%s' target='_blank'>%s</a> %s", $mess1, esc_url( $link ), $mess0, $mess2 ) );
						?>
					</p>
				</div>
				<?php
				do_action( 'villatheme_support_woo-free-shipping-bar' );
				echo '</div>';
				return;
			}
			?>
			<div class="vi-ui message positive tiny">
				<p>
                    <?php esc_html_e('You can use the shortcode to display shipping bar everywhere you want', 'woo-free-shipping-bar'); ?>
                    -
                    <a target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                </p>
				<p><strong>[woo_free_shipping_bar]</strong></p>
			</div>
			<form method="post" class="vi-ui small form">
				<?php
				wp_nonce_field( 'woofreeshipbar_action_nonce', '_woofreeshipbar_nonce' ,false);
				?>
				<div class="vi-ui top attached tabular menu">
					<?php
					foreach ( $tabs as $slug => $text ) {
						$active = $tab_active === $slug ? 'active' : '';
						printf( ' <div class="item %s" data-tab="%s">%s</div>', esc_attr( $active ), esc_attr( $slug ), esc_html( $text ) );
					}
					?>
				</div>
				<?php
				foreach ( $tabs as $slug => $text ) {
					$active = $tab_active === $slug ? ' active' : '';
					$method = str_replace( '-', '_', $slug ) . '_options';
					$fields = [];
					printf( '<div class="vi-ui bottom attached%s tab segment" data-tab="%s">', esc_attr( $active ), esc_attr( $slug ) );
					if ( method_exists( $this, $method ) ) {
						$fields = $this->$method();
					}
					$this->settings::villatheme_render_table_field( apply_filters( "wfspb_settings_fields", $fields, $slug ) );
					do_action( 'wfspb_settings_tab', $slug );
					printf( '</div>' );
				}
				?>
				<p class="wfspb-button-save-settings-container">
					<button class="vi-ui primary button labeled icon wfsb-submit"
					        name="wfsb_save_settings">
						<i class="icon save"></i><?php esc_html_e( 'Save', 'woo-free-shipping-bar' ); ?></button>
				</p>
			</form>
			<?php do_action( 'villatheme_support_woo-free-shipping-bar' ); ?>
		</div>
		<?php
	}
	protected function general_options(){
		?>
		<table class="optiontable form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Mobile', 'woo-free-shipping-bar' ) ?></th>
				<td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
					<p class="description"><?php esc_html_e( 'Enable on mobile.', 'woo-free-shipping-bar' ) ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Detect IP', 'woo-free-shipping-bar' ) ?></th>
				<td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                    <p class="description"><?php esc_html_e( 'If you enable to Detect IP then the user is accessing to your site will be automatically apply to Free Shipping zone with their IP. Note: their ip are contained in Free Shipping zone', 'woo-free-shipping-bar' ) ?></p>
                </td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Minimum value to display', 'woo-free-shipping-bar' ) ?></th>
				<td >
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
					<p class="description"><?php esc_html_e( 'The minimum value in the cart to display the shipping bar', 'woo-free-shipping-bar' ) ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Exclude shipping class', 'woo-free-shipping-bar' ) ?></th>
				<td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
					<p class="description"><?php esc_html_e( 'Select shipping class to exclude when calculate subtotal', 'woo-free-shipping-bar' ) ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Always show free shipping bar', 'woo-free-shipping-bar' ) ?></th>
				<td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                    <p class="description"><?php esc_html_e( 'This option will make free shipping bar show Message Full Free Shipping and ignore other condition', 'woo-free-shipping-bar' ) ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Compatible with cache plugin', 'woo-free-shipping-bar' ) ?></th>
				<td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                    <p class="description"><?php esc_html_e( 'Enable this option if your message is cached by cache plugin', 'woo-free-shipping-bar' ) ?></p>
				</td>
			</tr>
		</table>
		<?php
		return '';
	}
	protected function assign_options(){
		?>
		<table class="optiontable form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Assign pages', 'woo-free-shipping-bar' ) ?></th>
				<td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
					<p class="description">
						<?php esc_html_e( 'Checked to', 'woo-free-shipping-bar' );
						echo '<span class="wfspb-note"> ' . esc_html__( 'hide', 'woo-free-shipping-bar' ) . ' </span>';
						esc_html_e( 'bar on this page', 'woo-free-shipping-bar' ); ?>
					</p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Conditional tags', 'woo-free-shipping-bar' ) ?></th>
				<td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
					<p class="description"><?php esc_html_e( 'Let\'s control on which specific pages the shipping bar disappear using', 'woo-free-shipping-bar' ) ?>
						<a href="https://codex.wordpress.org/Conditional_Tags" target="_blank"><?php esc_html_e( 'WP\'s conditional tags,', 'woo-free-shipping-bar' ) ?></a>
						<a href="https://developer.woocommerce.com/docs/conditional-tags-in-woocommerce/" target="_blank"><?php esc_html_e( 'Woo\'s conditional tags.', 'woo-free-shipping-bar' ) ?></a>
					</p>
				</td>
			</tr>
		</table>
		<?php
		return '';
	}
	protected function design_options(){
		?>
        <table class="optiontable form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Small progress bar', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                    <p class="description"><?php esc_html_e( 'Show progress bar at bottom Cart page, Checkout page.', 'woo-free-shipping-bar' ) ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Show on single product page', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                    <p class="description"><?php esc_html_e( 'Show progress bar below add to cart button.', 'woo-free-shipping-bar' ) ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Show in Menu cart', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                    <p class="description"><?php esc_html_e( 'Show progress bar in Menu cart.', 'woo-free-shipping-bar' ) ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Color', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <div class="equal width fields">
                        <div class="field">
                            <input type="text"
                                   class="color-picker"
                                   name="<?php echo esc_attr( self::set_field( 'bg-color' ) ); ?>"
                                   value="<?php echo esc_attr( $this->settings->get_params( 'bg-color', '#4dd2c6' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Background Color', 'woo-free-shipping-bar' ) ?></p>
                        </div>
                        <div class="field">
                            <input type="text"
                                   class="color-picker"
                                   name="<?php echo esc_attr( self::set_field( 'text-color' ) ); ?>"
                                   value="<?php echo esc_attr( $this->settings->get_params( 'text-color', '#FFFFFF' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Text Color', 'woo-free-shipping-bar' ) ?></p>
                        </div>
                        <div class="field">
                            <input type="text"
                                   class="color-picker"
                                   name="<?php echo esc_attr( self::set_field( 'link-color' ) ); ?>"
                                   value="<?php echo esc_attr( $this->settings->get_params( 'link-color', '#ffdf77' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Link Color', 'woo-free-shipping-bar' ) ?></p>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Text', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <div class="equal width fields">
                        <div class="field">
                            <input id="wfspb-font" type="text"
                                   class="wfsb-fontselect"
                                   name="<?php echo esc_attr( self::set_field( 'font' ) ); ?>"
                                   value="<?php echo esc_attr( $this->settings->get_params( 'font', 'Architects Daughter' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Font-Family', 'woo-free-shipping-bar' ) ?></p>
                        </div>
                        <div class="field">
                            <select class="vi-ui fluid dropdown select-fontsize"
                                    name="<?php echo esc_attr( self::set_field( 'font-size' ) ); ?>">
								<?php for ( $i = 10; $i <= 40; $i ++ ) { ?>
                                    <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $this->settings->get_params( 'font-size', 16 ), $i ); ?> > <?php echo esc_html( $i ) . 'px'; ?></option>
								<?php } ?>
                            </select>
                            <p class="description"><?php esc_html_e( 'Font-Size', 'woo-free-shipping-bar' ) ?></p>
                        </div>
                        <div class="field">
                            <select class="vi-ui fluid dropdown select-textalign"
                                    name="<?php echo esc_attr( self::set_field( 'text-align' ) ); ?>">
                                <option value="left" <?php selected( $this->settings->get_params( 'text-align' ), 'left' ) ?>><?php esc_html_e( 'Left', 'woo-free-shipping-bar' ) ?></option>
                                <option value="center" <?php selected( $this->settings->get_params( 'text-align', 'center' ), 'center' ) ?>><?php esc_html_e( 'Center', 'woo-free-shipping-bar' ) ?></option>
                            </select>
                            <p class="description"><?php esc_html_e( 'Text Align', 'woo-free-shipping-bar' ) ?></p>
                        </div>
                    </div>
                </td>
            </tr>
            <tr >
                <th scope="row"><?php esc_html_e( 'Enable Progress', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <div class="vi-ui toggle checkbox wfspb-enable-progress">
                        <input type="checkbox"
                               id="wfspb-progress-percent"
                               name="<?php echo esc_attr( self::set_field( 'enable-progress' ) ); ?>" <?php checked( $this->settings->get_params( 'enable-progress' ), 1 ); ?>
                               value="1">
                        <label></label>
                    </div>
                </td>
            </tr>

            <tr class="wfspb-progress-percent-class">
                <th scope="row"><?php esc_html_e( 'Progress Color', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <div class="equal width fields">
                        <div class="field">
                            <input type="text"
                                   class="color-picker"
                                   name="<?php echo esc_attr( self::set_field( 'bg-color-progress' ) ); ?>"
                                   value="<?php echo esc_attr( $this->settings->get_params( 'bg-color-progress', '#F3F3F3' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Progress Background Color', 'woo-free-shipping-bar' ) ?></p>
                        </div>
                        <div class="field">
                            <input type="text"
                                   class="color-picker"
                                   name="<?php echo esc_attr( self::set_field( 'bg-current-progress' ) ); ?>"
                                   value="<?php echo esc_attr( $this->settings->get_params( 'bg-current-progress', '#B8A16E' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Current Progress Background Color', 'woo-free-shipping-bar' ) ?></p>
                        </div>
                        <div class="field">
                            <input type="text"
                                   class="color-picker"
                                   name="<?php echo esc_attr( self::set_field( 'progress-text-color' ) ); ?>"
                                   value="<?php echo esc_attr( $this->settings->get_params( 'progress-text-color', '#FFFFFF' ) ); ?>">
                            <p class="description"><?php esc_html_e( 'Progress Text Color', 'woo-free-shipping-bar' ) ?></p>
                        </div>
                    </div>
                </td>
            </tr>
            <tr class="wfspb-progress-percent-class">
                <th scope="row"><?php esc_html_e( 'Font-Size Progress Bar', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <select class="vi-ui fluid dropdown select-fontsize-progress"
                            name="<?php echo esc_attr( self::set_field( 'font-size-progress' ) ); ?>">
						<?php for ( $i = 10; $i <= 20; $i ++ ) { ?>
                            <option value="<?php echo esc_attr( $i ); ?>" <?php selected( $this->settings->get_params( 'font-size-progress', 11 ), $i ); ?> > <?php echo esc_html( $i ) . 'px'; ?></option>
						<?php } ?>
                    </select>
                </td>
            </tr>
            <tr class="wfspb-progress-percent-class">
                <th scope="row"><?php esc_html_e( 'Progress Bar Effect', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <select class="vi-ui fluid dropdown select-progress-effect" name="<?php echo esc_attr( self::set_field( 'progress_effect' ) ); ?>">
                        <option value="0" <?php selected( $this->settings->get_params( 'progress_effect', 0 ), 0 ); ?>><?php esc_html_e( 'Plain', 'woo-free-shipping-bar' ) ?></option>
                        <option value="1" <?php selected( $this->settings->get_params( 'progress_effect' ), 1 ); ?>><?php esc_html_e( 'Loading', 'woo-free-shipping-bar' ) ?></option>
                        <option value="2" <?php selected( $this->settings->get_params( 'progress_effect' ), 2 ); ?>><?php esc_html_e( 'Border', 'woo-free-shipping-bar' ) ?></option>
                    </select>
                </td>
            </tr>
            <tr class="wfspb-progress-percent-class">
                <th scope="row"><?php esc_html_e( 'Style', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <div class="equal width fields">
                        <input type="hidden" name="<?php echo esc_attr( self::set_field( 'style' ) ); ?>"
                               id="wfspb-progress-style-value"
                               value="<?php echo esc_attr($style  = $this->settings->get_params( 'style', '',1 ))?>">
						<?php
						for ($i = 1; $i < 4; $i++){
							$tmp_class = ['field','wfspb-progress-style', 'wfspb-progress-style-'.$i];
							if ($i == $style){
								$tmp_class[] = 'wfspb-progress-style-selected';
							}
							?>
                            <div class="<?php echo esc_attr(implode(' ',$tmp_class)) ?>" data-style_id="<?php echo esc_attr($i) ?>">
                                <img src="<?php echo esc_url( WFSPB_F_SHIPPING_IMAGES.'progress-style'.$i.'.png' ) ?>"
                                     class="vi-ui centered medium image middle aligned"/>
                            </div>
							<?php
						}
						?>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Position', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <div class="equal width fields">
                        <input type="hidden" name="<?php echo esc_attr( self::set_field( 'position' ) ); ?>"
                               id="wfspb-progress-position-value"
                               value="<?php echo esc_attr($position  = $this->settings->get_params( 'position', '',0 ))?>">
						<?php
						$tmp_position = ['top','bottom'];
						foreach ($tmp_position as $i => $v){
							$tmp_class = ['field','wfspb-progress-position', 'wfspb-progress-position-'.$i];
							if ($i == $position){
								$tmp_class[] = 'wfspb-progress-position-selected';
							}
							?>
                            <div class="<?php echo esc_attr(implode(' ',$tmp_class)) ?>" data-style_id="<?php echo esc_attr($i) ?>">
                                <img src="<?php echo esc_url( WFSPB_F_SHIPPING_IMAGES.'position-'.$v.'.png' ) ?>"
                                     class="vi-ui centered medium image middle aligned"/>
                            </div>
							<?php
						}
						?>
                    </div>
                </td>
            </tr>
            <tr class="wfspb-progress-position-top">
                <th scope="row"><?php esc_html_e( 'Header selector', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                    <p class="description">
						<?php esc_html_e( 'Add CSS selector to make free shipping bar working with the header bar', 'woo-free-shipping-bar' ) ?>
                    </p>
                </td>
            </tr>
            <tr class="wfspb-gift-box-option-class">
                <th scope="row"><?php esc_html_e( 'Gift Icon', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                    <p class="description"><?php esc_html_e( 'Image dimension should be 147 x 71(px).', 'woo-free-shipping-bar' ) ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Custom CSS', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <textarea name="<?php echo esc_attr( self::set_field( 'custom_css' ) ); ?>"><?php echo wp_kses_post( stripslashes_from_strings_only( $this->settings->get_params( 'custom_css' ) ) ) ?></textarea>
                </td>
            </tr>
        </table>
		<?php
		$class_pos = $this->settings->get_params( 'position' ) ? 'bottom_bar' : 'top_bar';
		$class_progress = $this->settings->get_params( 'enable-progress' ) ? 'enable_progress_bar' : 'disable_progress_bar';
		?>
        <div id="wfspb-top-bar" class="customized <?php echo esc_attr( $class_pos ) ?>">
            <div id="wfspb-main-content"><?php echo esc_html__( 'You have purchased $100 of $120. Continue', 'woo-free-shipping-bar' ) ?>
                <a href="#"><?php echo esc_html__( 'Shopping', 'woo-free-shipping-bar' ) ?></a>
            </div>
            <div class="" id="wfspb-close"></div>
            <div id="wfspb-progress" class="<?php echo esc_attr( $class_progress ) ?>"
                 style="display: none">
                <div id="wfspb-current-progress">
                    <div id="wfspb-label">25%</div>
                </div>
            </div>
        </div>
		<?php
		return '';
	}
	protected function effect_options(){
		?>
        <table class="optiontable form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Show gift box', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <div class="vi-ui toggle checkbox checked">
                        <input type="checkbox"
                               id="wfspb-gift-box-option"
                               name="<?php echo esc_attr( self::set_field( 'show-giftbox' ) ); ?>" <?php checked( $this->settings->get_params( 'show-giftbox' ), 1 ); ?>
                               value="1"/>
                        <label></label>
                        <p class="description"><?php esc_html_e( 'Allow Display gift icon for the free shipping bar', 'woo-free-shipping-bar' ) ?></p>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Initial delay', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Close message', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                    <p class="description"><?php esc_html_e( 'Allow  to show the close icon on the free shipping bar', 'woo-free-shipping-bar' ) ?></p>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Time to disappear', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                    <p class="description"><?php esc_html_e( 'Allow to set time to disappear the free shipping bar', 'woo-free-shipping-bar' ) ?></p>
                </td>
            </tr>
        </table>
		<?php
		return '';
	}
	protected function message_options(){
		?>
		<table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e( 'Enable', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <div class="vi-ui toggle checkbox checked">
                        <input type="checkbox"
                               name="<?php echo esc_attr( self::set_field( 'enable' ) ); ?>" <?php checked( $this->settings->get_params( 'enable' ), 1 ); ?>
                               value="1">
                        <label></label>
                    </div>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php esc_html_e( 'Free Shipping Zone(*)', 'woo-free-shipping-bar' ) ?></th>
                <td>
                    <select class="vi-ui fluid dropdown required"
                            name="<?php echo esc_attr( self::set_field( 'default-zone' ) ); ?>">
                        <option value="none" disabled><?php esc_html_e('None - Premium version only', 'woo-free-shipping-bar'); ?></option>
						<?php
						$default_zone = $this->settings->get_params( 'default-zone' );
						$exist_zones = $this->settings->get_free_shipping_zone();
						if (is_array($exist_zones) && !empty($exist_zones)){
							foreach ($exist_zones as $k => $v){
								$selected = $default_zone == $k ? 'selected' :'';
								?>
                                <option value="<?php echo esc_attr($k) ?>" <?php echo esc_attr($selected)?>><?php echo wp_kses_post($v['name'] ?? $k)?></option>
								<?php
							}
						}
						?>
                    </select>
                    <p class="description"><?php esc_html_e( 'Please select zone default what you set Free Shipping method.', 'woo-free-shipping-bar' ) ?></p>
                </td>
            </tr>
			<tr>
				<th><?php esc_html_e( 'Announce System', 'woo-free-shipping-bar' ) ?></th>
				<td>
					<?php
					ob_start();
					?>
					<textarea rows="2"
					          name="<?php echo esc_attr( self::set_field( 'announce_system' ) ); ?>"
					><?php echo wp_kses_post( trim( $this->settings->get_params( 'announce_system') ) ); ?></textarea>
					<?php
					$tmp_html = ob_get_clean();
					$fields     = [
						'fields'   => [
							'announce_system' =>[
								'not_wrap_html' => 1,
								'html' => $tmp_html,
							]
						],
					];
					$this->settings::villatheme_render_table_field( $fields );
					?>
					<p class="description">
						<span>{min_amount}</span>
						- <?php esc_html_e( 'Minimum order amount Free Shipping', 'woo-free-shipping-bar' ) ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Message Purchased', 'woo-free-shipping-bar' ) ?></th>
				<td>
					<?php
					ob_start();
					?>
					<textarea rows="2"
					          name="<?php echo esc_attr( self::set_field( 'message_purchased' ) ); ?>"
					><?php echo wp_kses_post( trim( $this->settings->get_params( 'message_purchased') ) ); ?></textarea>
					<?php
					$tmp_html = ob_get_clean();
					$fields     = [
						'fields'   => [
							'message_purchased' =>[
								'not_wrap_html' => 1,
								'html' => $tmp_html,
							]
						],
					];
					$this->settings::villatheme_render_table_field( $fields );
					?>
					<p class="description">
						<span>{min_amount}</span>
						- <?php esc_html_e( 'Minimum order amount Free Shipping', 'woo-free-shipping-bar' ) ?>
					</p>
					<p class="description">
						<span>{total_amounts}</span>
						- <?php esc_html_e( 'The total amount of your purchases', 'woo-free-shipping-bar' ) ?>
					</p>
					<p class="description">
						<span>{cart_amount}</span>
						- <?php esc_html_e( 'Total quantity in cart.', 'woo-free-shipping-bar' ) ?>
					</p>
					<p class="description">
						<span>{missing_amount}</span>
						- <?php esc_html_e( 'The outstanding amount of the free shipping program', 'woo-free-shipping-bar' ) ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Message Success', 'woo-free-shipping-bar' ) ?></th>
				<td>
					<?php
					ob_start();
					?>
					<textarea rows="2"
					          name="<?php echo esc_attr( self::set_field( 'message_success' ) ); ?>"
					><?php echo wp_kses_post( trim( $this->settings->get_params( 'message_success') ) ); ?></textarea>
					<?php
					$tmp_html = ob_get_clean();
					$fields     = [
						'fields'   => [
							'message_success' =>[
								'not_wrap_html' => 1,
								'html' => $tmp_html,
							]
						],
					];
					$this->settings::villatheme_render_table_field( $fields );
					?>
					<p class="description">
						<span>{checkout_page}</span>
						- <?php esc_html_e( 'Link to checkout page', 'woo-free-shipping-bar' ) ?>
					</p>
					<p class="description">
                        <a class="vi-ui button" target="_blank"
                           href="https://1.envato.market/N3mPV">{cart_page} - <?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
						- <?php esc_html_e( 'Link to cart page', 'woo-free-shipping-bar' ) ?>
					</p>
					<p class="description">
                        <a class="vi-ui button" target="_blank"
                           href="https://1.envato.market/N3mPV">{shopping} - <?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
						- <?php esc_html_e( 'Link to shop page', 'woo-free-shipping-bar' ) ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Message Error', 'woo-free-shipping-bar' ) ?></th>
				<td>
					<?php
					ob_start();
					?>
					<textarea rows="2"
					          name="<?php echo esc_attr( self::set_field( 'message_error' ) ); ?>"
					><?php echo wp_kses_post( trim( $this->settings->get_params( 'message_error') ) ); ?></textarea>
					<?php
					$tmp_html = ob_get_clean();
					$fields     = [
						'fields'   => [
							'message_error' =>[
								'not_wrap_html' => 1,
								'html' => $tmp_html,
							]
						],
					];
					$this->settings::villatheme_render_table_field( $fields );
					?>
					<p class="description">
						<span>{missing_amount}</span>
						- <?php esc_html_e( 'The outstanding amount of the free shipping program', 'woo-free-shipping-bar' ) ?>
					</p>
					<p class="description">
						<span>{shopping}</span>
						- <?php esc_html_e( 'Link to shop page', 'woo-free-shipping-bar' ) ?>
					</p>
				</td>
			</tr>
			<tr>
				<th><?php esc_html_e( 'Message Full Free Shipping', 'woo-free-shipping-bar' ) ?></th>
				<td>
                    <a class="vi-ui button" target="_blank"
                       href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
					<p class="description">
						<?php esc_html_e( 'This message is used when min amount is zero', 'woo-free-shipping-bar' ) ?>
					</p>
				</td>
			</tr>
            <?php
            if (class_exists('Polylang') || (function_exists('is_plugin_active') && is_plugin_active( 'sitepress-multilingual-cms/sitepress.php' ) )){
                ?>
                <tr>
                    <th><?php esc_html_e( 'Message for multi-languages', 'woo-free-shipping-bar' ) ?></th>
                    <td>
                        <a class="vi-ui button" target="_blank"
                           href="https://1.envato.market/N3mPV"><?php esc_html_e( 'Upgrade This Feature', 'woo-free-shipping-bar' ) ?></a>
                    </td>
                </tr>
                <?php
            }
            ?>
		</table>
		<?php
		return '';
	}
	public function save_settings() {
		if ( ! isset( $_POST['_woofreeshipbar_nonce'] ) || ! wp_verify_nonce( wc_clean($_POST['_woofreeshipbar_nonce']), 'woofreeshipbar_action_nonce' ) ) {
			return;
		}
		if ( ! isset( $_REQUEST['page'] ) || wc_clean($_REQUEST['page']) != 'woocommerce_free_ship' ) {
			return;
		}
		if ( !isset( $_POST['wfsb_save_settings']  ) ) {
			return;
		}
		global $woocommerce_free_shipping_settings;
		$args = $this->settings->get_params();
		foreach ( $args as $key => $arg ) {
			if ( isset( $_POST[ $key ] ) ) {
				if ( is_array( $_POST[ $key ] ) ) {
					$args[ $key ] = array_map( 'sanitize_text_field',wp_unslash( $_POST[ $key ] ) );
				} else {
					$args[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
				}
			} else {
				$args[ $key ] = is_array( $arg ) ? array() : '';
			}
		}
		$args                    = apply_filters( "wfsb_update_settings_args", $args );
		$woocommerce_free_shipping_settings = $args;
		update_option( 'wfspb-param', $args);
		$this->settings = WFSPB_F_Data::get_instance(true);
		$this->updated_sucessfully = 1;
	}

	public static function set_field( $field, $multi = false ) {
		if ( $field ) {
			if ( $multi ) {
				return '' . $field . '[]';
			} else {
				return $field ;
			}
		} else {
			return '';
		}
	}
}