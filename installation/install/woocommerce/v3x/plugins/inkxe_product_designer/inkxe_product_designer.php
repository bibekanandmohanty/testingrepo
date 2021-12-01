<?php
/*
Plugin Name: Imprintnext Product Designer
Plugin URI: https://imprintnext.com
Description: Online product designer tool for woocommerce.
Version: 10.0.1
Author: Imprintnext
Author Email: support@imprintnext.com
 */
/*namespace inkxe;*/

class InkXEProductDesigner {

	/*  =============================
		    // !Constants
	*/

	public $name = 'Imprintnext Product Designer';
	public $slug = 'ink-pd';
	public $plugin_path;
	public $plugin_url;
	public $ajax_nonce_string;
	public $version = "10.0.1";

	/* =============================
		    // !Constructor
	*/

	function __construct() {

		$this->plugin_path = plugin_dir_path(__FILE__);
		$this->plugin_url = plugin_dir_url(__FILE__);
		$this->ajax_nonce_string = $this->slug . '_ajax';
		$this->api_path = $this->ink_pd_get_api_url();

		// Hook up to the init action
		add_action('plugins_loaded', array($this, 'init_woocommerce_action_filters'));

		add_action('rest_api_init', array($this, 'inkxe_register_custom_routes'));
		//add_action( 'rest_api_init', array($this, 'ink_resgister_order_api'));

		// To create multiple shipping address table for quotation module
		register_activation_hook(__FILE__, array(&$this, 'create_table_shipping_address'));

	}

	public function ink_pd_get_api_url() {
		$xepath = get_site_url();
		$inkXEDir = get_option('inkxe_dir');
		if (!$inkXEDir) {
			$inkXEDir = "designer";
		}
		$url = $xepath . "/" . $inkXEDir . "/";
		return $url;
	}
	/*=============================
		     !Runs when the plugin is initialized
	*/

	function init_woocommerce_action_filters() {
		/*  =============================
			            // !Actions and Filters
		*/
		if (is_admin()) {
			add_action('admin_enqueue_scripts', array($this, 'ink_pd_admin_scripts'));
			add_action('wp_ajax_admin_load_thumbnails', array($this, 'ink_pd_admin_load_thumbnails'));
			add_action('woocommerce_process_product_meta', array($this, 'ink_pd_save_images'));
			add_action('admin_init', array($this, 'ink_pd_media_columns'));
			add_action('woocommerce_product_options_general_product_data', array($this, 'ink_pd_add_product_fields'));
			add_action('woocommerce_process_product_meta', array($this, 'ink_pd_save_product_fields'));
			add_action('woocommerce_order_item_add_action_buttons', array($this, 'ink_pd_action_woocommerce_order_item_add_action_buttons'));
		} else {
			add_action('wp_enqueue_scripts', array($this, 'ink_pd_register_scripts_and_styles'));
			add_action('woocommerce_add_order_item_meta', array($this, 'ink_pd_wdm_add_print_status_values_to_order_item_meta'), 1, 2);
			add_action('woocommerce_before_calculate_totals', array($this, 'ink_pd_add_custom_total_price'), 10, 1);
			add_action('woocommerce_after_add_to_cart_button', array($this, 'ink_pd_customize_button'), 10, 0);
			add_action('woocommerce_before_add_to_cart_quantity', array($this, 'ink_pd_display_dropdown_variation_add_cart'), 10, 0);
			add_action('woocommerce_after_add_to_cart_quantity', array($this, 'ink_pd_after_add_to_cart_quantity'), 10, 0);
			//add_action('user_register', array($this, 'registration_save'), 10, 1);

			add_filter('woocommerce_available_variation', array($this, 'ink_pd_alter_variation_json'), 10, 3);
			add_filter('woocommerce_thankyou', array($this, 'create_order_files'));
			add_filter('woocommerce_add_cart_item_data', array($this, 'ink_pd_add_cart_item_custom_data_vase'), 10, 2);
			add_filter('woocommerce_get_item_data', array($this, 'ink_pd_filter_woocommerce_get_item_data'), 10, 2);
			add_filter('woocommerce_cart_item_thumbnail', array($this, 'ink_pd_inkxe_customize_product_image'), 10, 3);
			add_filter('woocommerce_cart_item_name', array($this, 'ink_pd_add_edit_info_button'), 10, 3);
			add_filter('woocommerce_cart_item_quantity', array($this, 'ink_pd_disable_customize_product_cart_item_quantity'), 10, 3);
		}
	}

	/** =============================
	 *
	 * Alter Variation JSON
	 *
	 * This hooks into the data attribute on the variations form for each variation
	 * we can get the additional image data here!
	 *
	 * @param mixed $anything Description of the parameter
	 * @return bool
	 *
	============================= */

	public function ink_pd_alter_variation_json($variation_data, $wc_product_variable, $variation_obj) {
		$img_ids = $this->get_all_image_ids($variation_data['variation_id']);
		$images = $this->get_all_image_sizes($img_ids);

		$variation_data['additional_images'] = $images;

		return $variation_data;
	}

	/** =============================
	 *
	 * Is Enabled
	 *
	 * Check whether inkxe is enabled for this product
	 *
	============================= */

	public function is_enabled() {

		global $post;

		$pid = $post->ID;

		if ($pid) {

			$disable_inkxe = get_post_meta($pid, 'disable_inkxe', true);

			return ($disable_inkxe && $disable_inkxe == "yes") ? false : true;

		}

		return false;

	}

	/** =============================
	 *
	 * Get Product ID from Slug
	 *
	============================= */

	public function get_post_id_from_slug() {

		global $wpdb;

		$slug = str_replace(array("/product/", "/"), "", $_SERVER['REQUEST_URI']);

		$sql = "
            SELECT
                ID
            FROM
                $wpdb->posts
            WHERE
                post_type = \"product\"
            AND
                post_name = \"%s\"
        ";

		return $wpdb->get_var($wpdb->prepare($sql, $slug));

	}

/** =============================
 *
 * Add custom product fields
 *
============================= */

	public function ink_pd_add_product_fields() {

		global $woocommerce, $post;

		echo '<div class="options_group">';

		// Disable inkxe
		woocommerce_wp_checkbox(
			array(
				'id' => 'disable_inkxe',
				'label' => __('Disable InkXE?', $this->slug),
			)
		);

		echo '</div>';

	}

	/** =============================
	 *
	 * Save custom product fields
	 *
	============================= */

	public function ink_pd_save_product_fields($post_id) {
		// Disable inkxe
		$disable_inkxe = isset($_POST['disable_inkxe']) ? 'yes' : 'no';
		update_post_meta($post_id, 'disable_inkxe', $disable_inkxe);
	}

	/*  =============================
		    // !Add new column to media screen for Image IDs
	*/

	function ink_pd_media_columns() {
		add_filter('manage_media_columns', array($this, 'ink_pd_media_id_col'));
		add_action('manage_media_custom_column', array($this, 'ink_pd_media_id_col_val'), 10, 2);
	}

	function ink_pd_media_id_col($cols) {
		$cols["mediaid"] = "Image ID";
		return $cols;
	}

	function ink_pd_media_id_col_val($column_name, $id) {
		if ($column_name == 'mediaid') {
			echo $id;
		}

	}

	/*  =============================
		    // !Action and Filter Functions
	*/

	// Edit Screen Functions

	public function ink_pd_admin_scripts() {
		global $post, $pagenow;

		if (($post && (get_post_type($post->ID) == "product" && ($pagenow == "post.php" || $pagenow == "post-new.php"))) || ($pagenow == 'admin.php')) {
			wp_enqueue_script($this->slug, plugins_url('assets/admin/js/admin-scripts.js', __FILE__), array('jquery'), '2.0.1', true);
			wp_enqueue_style('jck_wt_admin_css', plugins_url('assets/admin/css/admin-styles.css', __FILE__), false, '2.0.1');

			$vars = array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce($this->ajax_nonce_string),
				'slug' => $this->slug,
			);
			wp_localize_script($this->slug, 'ink_pd_vars', $vars);
		} else {
			wp_enqueue_script($this->slug, plugins_url('assets/admin/js/downloadsvg.js', __FILE__), array('jquery'), '2.0.1', true);
			$vars = array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce($this->ajax_nonce_string),
				'siteurl' => $this->api_path,
				'slug' => $this->slug,
			);
			wp_localize_script($this->slug, 'ink_pd_vars', $vars);
		}
	}

	public function ink_pd_save_images($post_id) {
		// New changes
		if (isset($_POST['_product_image_gallery'])) {
			foreach ($_POST['_product_image_gallery'] as $varID => $variation_image_gallery) {
				update_post_meta($varID, '_product_image_gallery', $variation_image_gallery);
			}
		}
	}

	public function ink_pd_admin_load_thumbnails() {

		if (!isset($_REQUEST['nonce']) || !wp_verify_nonce($_REQUEST['nonce'], $this->ajax_nonce_string)) {die('Invalid Nonce');}
		// New changes
		$attachments = get_post_meta($_GET['varID'], '_product_image_gallery', true);
		$attachmentsExp = array_filter(explode(',', $attachments));
		$imgIDs = array();?>

            <ul class="wooThumbs">

                <?php if (!empty($attachmentsExp)) {?>

                    <?php foreach ($attachmentsExp as $id) {$imgIDs[] = $id;?>
                        <li class="image" data-attachment_id="<?php echo $id; ?>">
                            <a href="#" class="delete" title="Delete image"><?php echo wp_get_attachment_image($id, 'thumbnail'); ?></a>
                        </li>
                    <?php }?>

                <?php }?>

            </ul>
            <input type="hidden" class="variation_image_gallery" name="_product_image_gallery[<?php echo $_GET['varID']; ?>]" value="<?php echo $attachments; ?>">
        <?php
wp_die();
	}

	/*  =============================
		    // !Frontend Scripts and Styles
	*/

	public function ink_pd_register_scripts_and_styles() {
		global $jck_wt;

		if ((function_exists('is_product') && is_product()) && $this->is_enabled()) {

			// CSS

			// $this->load_file( $this->slug . '-css', '/assets/frontend/css/main.min.css' );

			// Scripts

			// $this->load_file( $this->slug . '-script', '/assets/frontend/js/main.min.js', true );

			$vars = array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce($this->ajax_nonce_string),
				'loading_icon' => plugins_url('assets/frontend/img/loading.gif', __FILE__),
				'slug' => $this->slug,
				'siteurl' => get_option('siteurl'),
				'options' => $jck_wt,
			);

			wp_localize_script($this->slug . '-script', 'ink_pd_vars', $vars);

			add_action('wp_head', array($this, 'dynamic_css'));

		}
		//Cart Image

		if (is_cart()) {
			$this->load_file($this->slug . '-css', '/assets/frontend/css/cart-style.css');
			$this->load_file($this->slug . '-script', '/assets/frontend/js/cart-image.js', true);
			wp_localize_script($this->slug . '-script', 'ink_pd_vars', array('siteurl' => get_option('siteurl')));
		}

		// Checkout page
		if (is_checkout()) {
			// Create order files after order placed.
			$orderURLData = explode('/', $_SERVER['REQUEST_URI']);
			if ($orderURLData['2'] == 'order-received') {
				$order_id = $orderURLData['3'];
				$this->create_order_files($order_id);
			}
		}
	} // end register_scripts_and_styles

	/** =============================
	 *
	 * Dynamic CSS
	 *
	============================= */

	public function dynamic_css() {
		include $this->plugin_path . '/assets/frontend/css/dynamic-styles.css.php';
	}

	/*  =============================
		    // !Helpers
	*/

	/*  =============================
		        Helper function for registering and enqueueing scripts and styles.
		        @name:          The ID to register with WordPress
		        @file_path:     The path to the actual file
		        @is_script:     Optional argument for if the incoming file_path is a JavaScript source file.
	*/

	private function load_file($name, $file_path, $is_script = false) {
		$url = plugins_url($file_path, __FILE__);
		$file = plugin_dir_path(__FILE__) . $file_path;

		if (file_exists($file)) {
			if ($is_script) {
				wp_register_script($name, $url, array('jquery'), false, true); //depends on jquery
				wp_enqueue_script($name);
			} else {
				wp_register_style($name, $url);
				wp_enqueue_style($name);
			} // end if
		} // end if
	}

	/*  =============================
		                Get all attached Image IDs
		                @id = the product or variation ID
	*/

	public function get_all_image_ids($id) {

		$allImages = array();
		$show_gallery = false;

		// Main Image
		if (has_post_thumbnail($id)) {

			$allImages[] = get_post_thumbnail_id($id);

		} else {

			$prod = get_post($id);
			$prodParentId = $prod->post_parent;
			if ($prodParentId && has_post_thumbnail($prodParentId)) {
				$allImages[] = get_post_thumbnail_id($prodParentId);
			} else {
				$allImages[] = 'placeholder';
			}

			$show_gallery = true;
		}

		// WooThumb Attachments
		if (get_post_type($id) == 'product_variation') {
			// New changes
			$wtAttachments = array_filter(explode(',', get_post_meta($id, '_product_image_gallery', true)));
			$allImages = array_merge($allImages, $wtAttachments);
		}

		// Gallery Attachments

		if (get_post_type($id) == 'product' || $show_gallery) {
			$product = get_product($id);
			$attachIds = $product->get_gallery_attachment_ids();

			if (!empty($attachIds)) {
				$allImages = array_merge($allImages, $attachIds);
			}
		}

		return $allImages;
	}

	/*  =============================
		        Get required image sizes based
		        on array of image IDs
	*/

	public function get_all_image_sizes($imgIds) {
		$images = array();
		if (!empty($imgIds)) {
			foreach ($imgIds as $imgId):
				if ($imgId == 'placeholder') {
					$images[] = array(
						'large' => array(wc_placeholder_img_src('large')),
						'single' => array(wc_placeholder_img_src('shop_single')),
						'thumb' => array(wc_placeholder_img_src('thumbnail')),
						'alt' => '',
						'title' => '',
					);
				} else {
					if (!array_key_exists($imgId, $images)) {
						$attachment = $this->wp_get_attachment($imgId);
						$images[] = array(
							'large' => wp_get_attachment_image_src($imgId, 'large'),
							'single' => wp_get_attachment_image_src($imgId, apply_filters('single_product_large_thumbnail_size', 'shop_single')),
							'thumb' => wp_get_attachment_image_src($imgId, 'thumbnail'),
							'alt' => $attachment['alt'],
							'title' => $attachment['title'],
						);
					}
				}
			endforeach;
		}
		return $images;
	}

	public function wp_get_attachment($attachment_id) {
		$attachment = get_post($attachment_id);
		return array(
			'alt' => get_post_meta($attachment->ID, '_wp_attachment_image_alt', true),
			'caption' => $attachment->post_excerpt,
			'description' => $attachment->post_content,
			'href' => get_permalink($attachment->ID),
			'src' => $attachment->guid,
			'title' => $attachment->post_title,
		);
	}

	// Update Print Status
	public function ink_pd_wdm_add_print_status_values_to_order_item_meta($item_id, $values) {
		wc_add_order_item_meta($item_id, 'custom_design_id', $values['custom_design_id']);
	}

	// Method to create order files on suucessful checkout.
	public function create_order_files($order_id) {
		$storeId = get_current_blog_id() ? get_current_blog_id() : 1;
		$url = $this->api_path . "api/v1/orders/create-order-files/" . $order_id . '?store_id=' . $storeId;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$output = curl_exec($ch);
		curl_close($ch);
	}

	// Method to add download-button on order page
	public function ink_pd_action_woocommerce_order_item_add_action_buttons($order_id) {
		global $wpdb;
		$id = $order_id->id;
		$order_items = $wpdb->prefix . "woocommerce_order_items";
		$order_item_meta = $wpdb->prefix . "woocommerce_order_itemmeta";
		$sql = "SELECT order_item_id FROM $order_items WHERE order_id = '" . $id . "'";
		$items = $wpdb->get_results($sql);
		$is_customize = 0;
		foreach ($items as $item) {
			$item_id = $item->order_item_id;
			$meta_id = $wpdb->get_var("SELECT meta_id FROM $order_item_meta WHERE meta_key='custom_design_id' AND meta_value!='' AND order_item_id='" . $item_id . "'");
			if ($meta_id) {
				$is_customize = 1;
			}
		}
		if ($is_customize == 1) {
			echo '<button type="button" onclick="downloadSVG()" class="button generate-items">' . __('Download Designs') . '</button>';
		} else {
			echo '';
		}

	}

	//Method to add custom cart data
	public function ink_pd_add_cart_item_custom_data_vase($cart_item_meta, $product_id) {
		global $woocommerce;
		$refid = get_post_meta($product_id, 'custom_design_id', true);
		if (!isset($cart_item_meta['custom_design_id'])) {
			$cart_item_meta['custom_design_id'] = $refid;
		}
		return $cart_item_meta;
	}

	public function ink_pd_filter_woocommerce_get_item_data($item_data, $cart_item) {
		$item_data = array();

		// Both Simple Product and configure product variation data
		if (is_array($cart_item['variation'])) {

			foreach ($cart_item['variation'] as $name => $value) {

				if ('' === $value) {
					continue;
				}

				$taxonomy = wc_attribute_taxonomy_name(str_replace('attribute_pa_', '', urldecode($name)));

				// If this is a term slug, get the term's nice name
				if (taxonomy_exists($taxonomy)) {
					$term = get_term_by('slug', $value, $taxonomy);
					if (!is_wp_error($term) && $term && $term->name) {
						$value = $term->name;
					}
					$label = wc_attribute_label($taxonomy);

					// If this is a custom option slug, get the options name
				} else {
					$value = apply_filters('woocommerce_variation_option_name', $value);
					$product_attributes = $cart_item['data']->get_attributes();
					if (isset($product_attributes[str_replace('attribute_', '', $name)])) {
						$label = wc_attribute_label(str_replace('attribute_', '', $name));
					} else {
						$label = $name;
					}
				}
				$item_data[] = array(
					'key' => $label,
					'value' => $value,
				);
			}
		}

		// Format item data ready to display
		foreach ($item_data as $key => $data) {
			// Set hidden to true to not display meta on cart.
			if (!empty($data['hidden'])) {
				unset($item_data[$key]);
				continue;
			}
			$item_data[$key]['key'] = !empty($data['key']) ? $data['key'] : $data['name'];
			$item_data[$key]['display'] = !empty($data['display']) ? $data['display'] : $data['value'];
		}

		return $item_data;
	}

	public function ink_pd_add_custom_total_price($cart_object) {
		foreach ($cart_object->get_cart() as $key => $value) {
			$product_id = $value['product_id'];
			$variant_id = $value['variation_id'];
			$quantity = $value['quantity'];
			// For Imprintnext tier pricing
			if (!$value['custom_design_id']) {
				$metaDataContent = get_post_meta($product_id, 'imprintnext_tier_content');
				$tierPriceData = array();
				$commonTierPrice = array();
				$variantTierPrice = array();
				$price = 0;
				$variantPrice = 0;
				$sameforAllVariants = $isTier = false;
				if (!empty($metaDataContent)) {
					$tierPriceData = $metaDataContent[0];
					$isTier = true;
					if ($tierPriceData['pricing_per_variants'] == 'true') {
						$sameforAllVariants = true;
						foreach ($tierPriceData['price_rules'][0]['discounts'] as $discount) {
							$commonTierPrice[] = array("upper_limit" => $discount['upper_limit'],
								"lower_limit" => $discount['lower_limit'],
								"discount" => $discount['discount'],
								"discountType" => $tierPriceData['discount_type'],
							);
						}
					} else {
						foreach ($tierPriceData['price_rules'] as $variant) {
							foreach ($variant['discounts'] as $discount) {
								$variantTierPrice[$variant['id']][] = array("upper_limit" => $discount['upper_limit'], "lower_limit" => $discount['lower_limit'],
									"discount" => $discount['discount'],
									"discountType" => $tierPriceData['discount_type'],
								);
							}
						}
					}
				}
				if ($isTier) {
					$price = $value['data']->get_price();
					$variantPrice = ($sameforAllVariants === true ? $this->getPriceAfterTierDiscount($commonTierPrice, $price, $quantity) : $this->getPriceAfterTierDiscount($variantTierPrice[$variant_id], $price, $quantity));
					$value['data']->set_price($variantPrice);
				}
			}
			if ($value['_other_options']['product-price'] != "") {
				$value['data']->set_price($value['_other_options']['product-price']);
			}
		}
	}

	public function getPriceAfterTierDiscount($tierPriceRule, $price, $quantity) {
		$returnPrice = $price;
		foreach ($tierPriceRule as $tier) {
			if ($quantity >= $tier['lower_limit'] && $quantity <= $tier['upper_limit']) {
				$returnPrice = ($tier['discountType'] == "flat" ? ($price - $tier['discount']) : ($price - (($tier['discount'] / 100) * $price)));
				break;
			}
		}
		return $returnPrice;
	}

	public function get_customize_preview_images_details($custom_design_id, $product_id) {
		$result = array();
		$result['httpCode'] = 0;
		$ch = curl_init();
		$xepath = $this->api_path;
		$url = $xepath . 'api/v1/preview-images?custom_design_id=' . $custom_design_id . '&product_id=' . $product_id;
		curl_setopt($ch, CURLOPT_URL, $url);
		//curl_setopt($ch, CURLOPT_POST, 1);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array('custom_design_id' => $custom_design_id,'product_id' => $product_id)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$server_output = curl_exec($ch);
		$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		curl_close($ch);
		$result['httpCode'] = $httpCode;
		$result['customPreviewImagesData'] = $server_output;
		return $result;
	}

	public function ink_pd_inkxe_customize_product_image($product_customize_image, $cart_item, $cart_item_key) {
		$ref_id = $cart_item['custom_design_id'];
		$product_id = $cart_item['variation_id'];
		if ($product_id == 0) {
			$product_id = $cart_item['product_id'];
		}
		if ($ref_id) {
			$result = $this->get_customize_preview_images_details($ref_id, $product_id);
			if ($result['httpCode'] == 200) {
				$customize_data = json_decode($result['customPreviewImagesData'], true);
				if (!array_key_exists("status", $customize_data)) {
					$product_customize_image = "";
					$i = 0;
					$isPrint = 1;
					foreach ($customize_data[$ref_id] as $key => $value) {
						// For custom size.
						if ($isPrint) {
							if ($value['variableDecorationSize']) {
								echo "<b>Custom Size: " . $value['variableDecorationSize'] . " " . $value['variableDecorationUnit'] . "</b>";
								$isPrint = 0;
							}
						}
						// if ($value['design_status']) {
						$class = 'attachment-shop_thumbnail wp-post-image';
						// Default cart thumbnail class.
						$src = $value['customImageUrl'][$i];
						$product_customize_image .= '<img';
						$product_customize_image .= ' src="' . $src . '"';
						$product_customize_image .= ' class="' . $class . '"';
						$product_customize_image .= ' width="75" height="75" />';
						// }
						$i++;
					}
				}
			}
		}
		return $product_customize_image;
	}

	public function ink_pd_add_edit_info_button($product_get_name, $cart_item, $cart_item_key) {
		if (is_cart()) {
			global $wpdb;
			$xepath = get_site_url();
			$ch = curl_init();
			$ref_id = $cart_item['custom_design_id'];
			$simpleProductId = $cart_item['variation_id'];
			$productId = $cart_item['product_id'];
			if ($simpleProductId == 0) {
				$simpleProductId = $productId;
			}
			$quantity = $cart_item['quantity'];
			$result = $this->get_customize_preview_images_details($ref_id, $simpleProductId);
			if ($result['httpCode'] == 200) {
				$customize_data = json_decode($result['customPreviewImagesData'], true);
				$a = "";
				$value = $customize_data[$ref_id][0];
				$is_name_and_number = $value['nameAndNumber'];
				$display_edit = $value['display_edit'];
				$wc_attributes = $wpdb->prefix . "woocommerce_attribute_taxonomies";
				$sql = "SELECT attribute_name FROM $wc_attributes WHERE attribute_label = '" . $value['sizeAttr'] . "'";
				$items = $wpdb->get_results($sql);
				$sizeAttrVal = $cart_item['variation']['attribute_pa_' . $items[0]->attribute_name];
				$vid = $value['simpleProductId'];
				echo "<br/><br/>";
				$api_url = $this->ink_pd_get_api_url();
				$settingUrl = $api_url . 'api/v1/settings/carts';
				$settingArray = $this->getGeneralSetting($settingUrl);
				$cart_edit_enabled = $settingArray['is_enabled'];
				$action = $settingArray['cart_item_edit_setting'] ? $settingArray['cart_item_edit_setting'] : 'add';
				if ($display_edit) {
					if ($cart_edit_enabled) {
						_e("<div id='editButton" . $cart_item_key . "'><a type='button' data-toggle='tooltip'  title='Edit' class='btn button btn-primary'  href='" . $xepath . "/product-designer/?id=" . $productId . "&vid=" . $vid . "&dpid=" . $ref_id . "&qty=1&cart_item_id=" . $cart_item_key . "&action=" . $action . "'>Edit</a><i class='icon-edit'></i></div>");
					}

				}
			}
		}
		return $product_get_name;
	}

	// multiple shipping address management
	public function create_table_shipping_address() {
		global $wpdb;
		$table = $wpdb->prefix . 'multipleshippingaddress';
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$sql = "CREATE TABLE IF NOT EXISTS $table (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `first_name` varchar(50) NOT NULL,
            `last_name` varchar(50) NOT NULL,
            `address_line_one` varchar(200) NOT NULL,
            `address_line_two` varchar(200) NOT NULL,
            `company` varchar(200) NOT NULL,
            `city` varchar(50) NOT NULL,
            `postcode` varchar(10) NOT NULL,
            `country` varchar(7) NOT NULL,
            `state` varchar(7) NOT NULL,
            `mobile_no` varchar(13) NOT NULL,
            `user_id` int(11) NOT NULL,
            `is_default` int(11) NOT NULL DEFAULT '0',
             PRIMARY KEY  (id)
            )ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8";
		dbDelta($sql);
	}

	function inkxe_get_orders($request) {
        global $wpdb;
        $page = $request['per_page'] * ($request['page'] - 1);
        $post_per_page = $request['per_page'];
        $searchStr = explode(" ", trim($request['search']));
        $search = addslashes($searchStr[0]);
        $filter = addslashes($request['sku']);
        $print_type = $request['print_type'];
        $is_customize = $request['is_customize'];
        $last_order_id = $request['last_id'];
        $order_by = ($request['order_by'] != '') ? $request['order_by'] : 'xe_id';
        $order = $request['order'];
        $from = $request['from'];
        $to = $request['to'];
        $order_status = $request['order_status'];
		$customer_id = $request['customer_id'];
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
        $order_query = ($order_by == 'xe_id') ? 'ORDER BY p.post_date' : 'ORDER BY pm.meta_value';
        //$meta_query = ($order_by=='customer')?'_billing_first_name':'_order_total';
        $date_range_query = ($from != '' && $to != '') ? ' AND p.post_date >= "' . $from . '" AND p.post_date <= "' . $to . '"' : '';
        $query_by_id = ($last_order_id != 0) ? ' AND p.ID > ' . $last_order_id : '';
        $status_query = ($order_status == '' || $order_status == 'kiosk') ? " AND p.post_status != 'trash'" : " AND p.post_status = 'trash'";

        $tableOrder = $wpdb->prefix . "posts";
        $tableOrderMeta = $wpdb->prefix . "postmeta";
        $tableOrderItem = $wpdb->prefix . "woocommerce_order_items";
        $tableOrderItemMeta = $wpdb->prefix . "woocommerce_order_itemmeta";
        
        $sql = "SELECT DISTINCT p.ID, p.post_date, p.post_status FROM $tableOrder as p INNER JOIN $tableOrderMeta as pm ON (p.ID = pm.post_id AND (pm.meta_key = '_billing_first_name' OR pm.meta_key = '_billing_last_name' OR pm.meta_key = '_order_total' OR pm.meta_key='_customer_user') AND pm.meta_key != 'kiosk_order') INNER JOIN $tableOrderItem as woi ON pm.post_id = woi.order_id INNER JOIN $tableOrderItemMeta as woim ON woi.order_item_id = woim.order_item_id WHERE p.post_type = 'shop_order'".$status_query;

        $count = "SELECT COUNT(DISTINCT p.ID) as total FROM $tableOrder as p INNER JOIN $tableOrderMeta as pm ON (p.ID = pm.post_id AND (pm.meta_key = '_billing_first_name' OR pm.meta_key = '_billing_last_name' OR pm.meta_key = '_order_total' OR pm.meta_key='_customer_user')) INNER JOIN $tableOrderItem as woi ON pm.post_id = woi.order_id INNER JOIN $tableOrderItemMeta as woim ON woi.order_item_id = woim.order_item_id WHERE p.post_type = 'shop_order'".$status_query;

        if ($order_status == 'kiosk') {
            $count = "SELECT COUNT(DISTINCT *) FROM $tableOrder as p INNER JOIN $tableOrderMeta as pm ON (p.ID = pm.post_id AND pm.meta_key = 'kiosk_order') INNER JOIN $tableOrderItem as woi ON pm.post_id = woi.order_id INNER JOIN $tableOrderItemMeta as woim ON woi.order_item_id = woim.order_item_id WHERE p.post_type = 'shop_order' AND woim.meta_key = 'custom_design_id' AND (woim.meta_value != '' AND woim.meta_value != '0') ";
            $sql = $customOrders = "SELECT DISTINCT * FROM $tableOrder as p INNER JOIN $tableOrderMeta as pm ON (p.ID = pm.post_id AND pm.meta_key = 'kiosk_order') INNER JOIN $tableOrderItem as woi ON pm.post_id = woi.order_id INNER JOIN $tableOrderItemMeta as woim ON woi.order_item_id = woim.order_item_id WHERE p.post_type = 'shop_order' AND woim.meta_key = 'custom_design_id' AND (woim.meta_value != '' AND woim.meta_value != '0') ";
        }else{
            $customOrders = "SELECT DISTINCT p.ID FROM $tableOrder as p INNER JOIN $tableOrderMeta as pm ON (p.ID = pm.post_id AND (pm.meta_key = '_billing_first_name' OR pm.meta_key = '_billing_last_name' OR pm.meta_key = '_order_total') AND pm.meta_key != 'kiosk_order') INNER JOIN $tableOrderItem as woi ON pm.post_id = woi.order_id INNER JOIN $tableOrderItemMeta as woim ON woi.order_item_id = woim.order_item_id WHERE p.post_type = 'shop_order' AND woim.meta_key = 'custom_design_id' AND (woim.meta_value != '' AND woim.meta_value != '0') ";
            
        }


        if (isset($customer_id) && $customer_id != 0) {
            $sql .= " AND pm.meta_key = '_customer_user' AND pm.meta_value = '$customer_id'";
            $count .= " AND pm.meta_key = '_customer_user' AND pm.meta_value = '$customer_id'";
        }

        if (isset($is_customize) && $is_customize != 0) {
            $sql .= " AND woim.meta_key = 'custom_design_id' AND woim.meta_value != '' AND woim.meta_value != 0";
            $count .= " AND woim.meta_key = 'custom_design_id' AND woim.meta_value != '' AND woim.meta_value != 0";
        }

        if (isset($print_type) && $print_type != '') {
            $sql .= " AND woim.meta_key = 'print_type' AND woim.meta_value IN (" . $print_type . ")";
            $count .= " AND woim.meta_key = 'print_type' AND woim.meta_value IN (" . $print_type . ")";
            $customOrders .= " AND woim.meta_key = 'print_type' AND woim.meta_value IN (" . $print_type . ")";
        }

        if (isset($filter) && $filter != '') {
            $product_id = wc_get_product_id_by_sku($filter);
            $sql .= " AND woim.meta_key = '_product_id' AND woim.meta_value = $product_id";
            $count .= " AND woim.meta_key = '_product_id' AND woim.meta_value = $product_id";
            $customOrders .= " AND woim.meta_key = '_product_id' AND woim.meta_value = $product_id";
        }

        if (isset($search) && $search != '') {
            $sql .= " AND (p.ID LIKE '%$search%' OR (pm.meta_key = '_billing_first_name' AND pm.meta_value LIKE '%$search%') OR (pm.meta_key = '_billing_last_name' AND pm.meta_value LIKE '%$search%'))";
            $count .= " AND (p.ID LIKE '%$search%' OR (pm.meta_key = '_billing_first_name' AND pm.meta_value LIKE '%$search%') OR (pm.meta_key = '_billing_last_name' AND pm.meta_value LIKE '%$search%'))";
            $customOrders .= " AND p.ID LIKE '%$search%' OR (pm.meta_key = '_billing_first_name' AND pm.meta_value LIKE '%$search%') OR (pm.meta_key = '_billing_last_name' AND pm.meta_value LIKE '%$search%')";
        }
        $totalRecords = $wpdb->get_results($count);

        $sql .= " $date_range_query $order_query $order LIMIT $page, $post_per_page";
        $customOrders .= " $date_range_query $order_query $order";
        $customOrderList = $wpdb->get_results($customOrders, ARRAY_A);
        $customOrderList = array_column($customOrderList, 'ID');
        $result = $wpdb->get_results($sql);
        $response = array();
        $output['records'] = $totalRecords[0]->total;
        foreach ($result as $key => $value) {
			$customer_id = get_post_meta($value->ID, '_customer_user', true);
			$first_name = "";
			$last_name = "";
			if( $customer_id!=0 ){
				$user = get_user_by('id', $customer_id);
				$user_name = explode(" ", $user->display_name);
                $first_name = $user->first_name;
				$last_name = $user->last_name;
			}
			$response[$key]['id'] = $value->ID;
			$response[$key]['order_number'] = $value->ID;
			$order = wc_get_order($value->ID);
			$response[$key]['order_total_quantity'] = $order->get_item_count();
			$response[$key]['customer_first_name'] = ($first_name != "") ? 
													$first_name : get_post_meta($value->ID, '_billing_first_name', true);
			$response[$key]['customer_last_name'] = ($last_name != "") ? 
													$last_name : get_post_meta($value->ID, '_billing_last_name', true);
			$response[$key]['created_date'] = $value->post_date;
			$response[$key]['total_amount'] = get_post_meta($value->ID, '_order_total', true);
			$response[$key]['currency'] = get_post_meta($value->ID, '_order_currency', true);
			$response[$key]['is_customize'] = 0;
			if (in_array($value->ID, $customOrderList)) {
				$response[$key]['is_customize'] = 1;
			}
			$response[$key]['production'] = '';
			$response[$key]['status'] = substr($value->post_status, 3);
		}
        if ($order_by != 'xe_id') {
            $customer_first_name = array_column($response, 'customer_first_name');
            $customer_first_name = array_map('strtolower', $customer_first_name);
            array_multisort($customer_first_name, (strtolower($order) == 'desc') ? SORT_DESC : SORT_ASC, $response);
        }
        $output['data'] = $response;
        return rest_ensure_response($output);
    }

	/**
	 * We can use this function to contain our arguments for the example product endpoint.
	 */
	function inkxe_get_order_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['page'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to get max number of records '),
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 1,
		);
		$args['per_page'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to get max number of records '),
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 1000,
		);
		$args['sku'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter based on product sku'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['print_type'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter base on print methods'),
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => '',
		);
		$args['is_customize'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter customized orders'),
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 0,
		);
		$args['order_by'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used sort the response'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => 'xe_id',
		);
		$args['order'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used for sort order'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => 'DESC',
		);
		$args['search'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['from'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['to'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['customer_id'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 0,
		);
		$args['order_status'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);

		$args['order_status'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['store_id'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter order list by store id', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 1,
		);

		return $args;
	}

	function inkxe_get_products($request) {
		global $wpdb;
		$page = $request['range'] * ($request['page'] - 1);
		$post_per_page = $request['range'];
		$search = addslashes($request['search']);
		$category = $request['category'];
		$is_customize = $request['is_customize'];
		$is_catalog = $request['is_catalog'];
		$last_product_id = $request['last_id'];
		$order_by = $request['order_by'];
		$order = $request['order'];
		$fetch = $request['fetch'];
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$order_query = ($order_by == 'post_date') ? 'ORDER BY p.post_date' : 'ORDER BY p.post_title';
		$join_query = " p.ID = pm.post_id";
		if ($request['order_by'] == 'top') {
			$join_query = " (p.ID = pm.post_id AND pm.meta_key='total_sales')";
			$order_query = 'ORDER BY pm.meta_value';
		}
		$subCatArray = [];
		if (isset($category) && $category != '') {
			$catArray = explode(",", $category);
			foreach ($catArray as $cat) {
				$subCatArray[] = $cat;
				$subCat = get_terms('product_cat', array('child_of' => $cat));
				foreach ($subCat as $sub) {
					$subCatArray[] = $sub->term_id;
				}
			}
		}
		$category = !empty($subCatArray) ? implode(",", $subCatArray) : $category;
		$tableProduct = $wpdb->prefix . "posts";
		$tableProductMeta = $wpdb->prefix . "postmeta";
		$tableTermTaxonomy = $wpdb->prefix . "term_taxonomy";
		$tableTermRelationship = $wpdb->prefix . "term_relationships";
		$result1 = $wpdb->get_results("SELECT DISTINCT p.ID FROM $tableProduct as p LEFT JOIN $tableProductMeta as pm ON p.ID = pm.post_id AND pm.meta_key = 'custom_design_id' WHERE p.post_type = 'product' AND p.post_status = 'publish' AND pm.meta_key = 'custom_design_id' AND pm.meta_value != ''", ARRAY_N);
		$res = implode(",", array_map(function ($a) {return implode(",", $a);}, $result1));

		$resultCatalog = $wpdb->get_results("SELECT DISTINCT p.ID FROM $tableProduct as p LEFT JOIN $tableProductMeta as pm ON p.ID = pm.post_id AND pm.meta_key = 'is_catalog' WHERE p.post_type = 'product' AND p.post_status = 'publish' AND pm.meta_key = 'is_catalog' AND pm.meta_value != ''", ARRAY_N);
		$resCatalog = implode(",", array_map(function ($a) {return implode(",", $a);}, $resultCatalog));

		$sql = "SELECT DISTINCT p.ID, p.post_title, p.post_date, p.post_status FROM $tableProduct as p INNER JOIN $tableProductMeta as pm ON $join_query INNER JOIN $tableTermRelationship as tr ON pm.post_id = tr.object_id INNER JOIN $tableTermTaxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE p.post_type = 'product' AND p.post_status = 'publish'";
		$count = "SELECT COUNT(DISTINCT p.ID) as total FROM $tableProduct as p INNER JOIN $tableProductMeta as pm ON $join_query INNER JOIN $tableTermRelationship as tr ON pm.post_id = tr.object_id INNER JOIN $tableTermTaxonomy as tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE p.post_type = 'product' AND p.post_status = 'publish'";
		if (isset($category) && $category != '') {
			$sql .= " AND tt.term_id IN (" . $category . ")";
			$count .= " AND tt.term_id IN (" . $category . ")";
		}

		if(empty($fetch)) {
			if (isset($is_customize) && $is_customize != 0) {
			$sql .= " AND pm.meta_key = 'custom_design_id' AND pm.meta_value != ''";
			$count .= " AND pm.meta_key = 'custom_design_id' AND pm.meta_value != ''";
			}
			if (isset($is_catalog) && $is_catalog != 0) {
				$sql .= " AND pm.meta_key = 'is_catalog' AND pm.meta_value != ''";
				$count .= " AND pm.meta_key = 'is_catalog' AND pm.meta_value != ''";
			}
			if (isset($is_catalog) && $is_catalog == 0 && $resCatalog != '') {
				$sql .= " AND p.ID NOT IN ($resCatalog)";
				$count .= " AND p.ID NOT IN ($resCatalog)";
			}
			if (isset($is_customize) && $is_customize == 0 && $res != '') {
				$sql .= " AND p.ID NOT IN ($res)";
				$count .= " AND p.ID NOT IN ($res)";
			}
		}
		if (isset($search) && $search != '') {
			$sql .= " AND p.post_title LIKE '%$search%' OR (pm.meta_key = '_sku' AND pm.meta_value LIKE '%$search%') ";
			$count .= " AND p.post_title LIKE '%$search%' OR (pm.meta_key = '_sku' AND pm.meta_value LIKE '%$search%') ";
		}
		$sql .= " $date_range_query $order_query $order LIMIT $page, $post_per_page";
		//echo $sql;exit;
		$totalRecords = $wpdb->get_results($count);
		$result = $wpdb->get_results($sql);
		$response = array();
		$output['records'] = $totalRecords[0]->total;

		foreach ($result as $key => $value) {
			$imageThumb = array();
			$product = wc_get_product($value->ID);
			$response[$key]['id'] = $value->ID;
			$response[$key]['name'] = $value->post_title;
			$response[$key]['type'] = $product->get_type();
			$response[$key]['sku'] = get_post_meta($value->ID, '_sku', true);
			$response[$key]['price'] = get_post_meta($value->ID, '_price', true);
			if ($product->get_manage_stock() != 1 && $product->get_stock_status() == "instock") {
				$response[$key]['stock'] = 10000;
			} else {
				$response[$key]['stock'] = $product->get_stock_quantity(); 
			}
			if ($product->get_manage_stock() == 1 && $product->get_stock_status() == "outofstock") {
				$allVariation = $product->get_children();
		    	if (!empty($allVariation)) {
			    	foreach ($allVariation as $keyVar => $productVariationId) {
						$variation_obj = wc_get_product($productVariationId);
						if ($variation_obj->get_manage_stock() == 1 && $variation_obj->get_stock_status() == "outofstock") {
							$response[$key]['is_sold_out'] = true;
						} else {
							if($variation_obj->get_stock_quantity() == 0)
								$response[$key]['is_sold_out'] = true;
							else {
								$response[$key]['is_sold_out'] = false;
								break;
							}

						}
					}
		    	} else {
					$response[$key]['is_sold_out'] = true;
		    	}
			} else {
		    	$allVariation = $product->get_children();
		    	if (!empty($allVariation)) {
			    	foreach ($allVariation as $keyVar => $productVariationId) {
						$variation_obj = wc_get_product($productVariationId);
						if ($variation_obj->get_manage_stock() == 1 && $variation_obj->get_stock_status() == "outofstock") {
							$response[$key]['is_sold_out'] = true;
						} else {
							$response[$key]['is_sold_out'] = false;
							break;
						}
					}
		    	} else {
					$response[$key]['is_sold_out'] = false;
		    	}
			}
			if($fetch =='all') {
				$response[$key]['custom_design_id'] = get_post_meta($value->ID, 'custom_design_id', true) ? get_post_meta($value->ID, 'custom_design_id', true):'';
				$response[$key]['is_decorated_product'] = get_post_meta($value->ID, 'is_decorated_product', true) ? get_post_meta($value->ID, 'is_decorated_product', true):0;
				$response[$key]['is_redesign'] = $product->get_attribute( 'pa_xe_is_designer' ) ? $product->get_attribute( 'pa_xe_is_designer' ):0;
			}
			$variation_id = $value->ID;
			$i = 0;
			if ($product->get_type() == 'variable') {
				$args = array(
					'post_type' => 'product_variation',
					'post_status' => array('publish'),
					'order' => 'ASC',
					'post_parent' => $value->ID, // get parent post-ID
				);
				$variations = get_posts($args);
				if (!empty($variations)) {
					$variation_id = $variations[0]->ID;
					$variation = wc_get_product($variation_id);
					$imageId = $variation->get_image_id();
					if ($imageId != 0) {
						$imageSrc = wp_get_attachment_image_src($imageId, 'thumbnail');
						$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
						$i++;
					}
					// New changes on product variation gallary
					$galleryImageIds = get_post_meta($variation_id, '_product_image_gallery', true);
					if (empty($galleryImageIds)) {
						$galleryImageIds = get_post_meta($variation_id, 'variation_image_gallery', true);
					}
					$galleryImageIds = array_filter(explode(',', $galleryImageIds));
					foreach ($galleryImageIds as $id) {
						$imageSrc = wp_get_attachment_image_src($id, 'thumbnail');
						$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
						$i++;
					}
				} else {
					$imageId = get_post_meta($value->ID, '_thumbnail_id', true);
					if ($imageId != 0) {
						$imageSrc = wp_get_attachment_image_src($imageId, 'thumbnail');
						$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
						$i++;
					}
					$galleryImageIds = get_post_meta($value->ID, '_product_image_gallery', true);
					if ($galleryImageIds != "") {
						$galleryImageIds = explode(',', $galleryImageIds);
						foreach ($galleryImageIds as $id) {
							$imageSrc = wp_get_attachment_image_src($id, 'thumbnail');
							$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
							$i++;
						}
					}
				}

			} else {
				$imageId = get_post_meta($value->ID, '_thumbnail_id', true);
				if ($imageId != 0) {
					$imageSrc = wp_get_attachment_image_src($imageId, 'thumbnail');
					$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
					$i++;
				}
				$galleryImageIds = get_post_meta($value->ID, '_product_image_gallery', true);
				if ($galleryImageIds != "") {
					$galleryImageIds = explode(',', $galleryImageIds);
					foreach ($galleryImageIds as $id) {
						$imageSrc = wp_get_attachment_image_src($id, 'full');
						$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
						$i++;
					}
				}
			}
			// If product price is empty updated variation price.
			if (empty($response[$key]['price'])) {
				$response[$key]['price'] = get_post_meta($variation_id, '_price', true);
			}
			$response[$key]['variation_id'] = $variation_id;
			$response[$key]['image'] = $imageThumb; //$imageThumb;//get_post_meta($value->ID, '_price', true);
		}
		$output['data'] = $response;
		return rest_ensure_response($output);
	}
	function inkxe_get_product_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['range'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 20,
		);
		$args['page'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 1,
		);
		$args['search'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['catagory'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['order_by'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => 'post_date',
		);
		$args['order'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => 'DESC',
		);
		$args['is_customize'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to filter the collection of colors', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 0,
		);
		$args['fetch'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to display all products', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['store_id'] = array(
			// description should be a human readable description of the argument.
			'description' => esc_html__('The filter parameter is used to display all products per store wise', 'my-text-domain'),
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => '',
		);
		return $args;
	}

	function inkxe_get_attribute_options($request) {
		global $wpdb;
		$product_id = $request['product_id'];
		$attribute = $request['attribute'];
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$tablePost = $wpdb->prefix . "posts";
		$tablePostMeta = $wpdb->prefix . "postmeta";
		$meta_key = 'attribute_' . $attribute;
		$sql = "SELECT DISTINCT p.ID, pm.meta_value FROM $tablePost as p INNER JOIN $tablePostMeta as pm ON p.ID = pm.post_id WHERE p.post_type = 'product_variation' AND
        p.post_parent = $product_id AND pm.meta_key = '$meta_key'";
		$variants = $wpdb->get_results($sql);
		if (empty($variants)) {
			$product = wc_get_product($product_id);
			$variations = (array) $product->get_available_variations();
			$firstAttribute = $variations[0]['attributes'];
			reset($firstAttribute);
			$first_key = key($firstAttribute);
			$attribute = str_replace("attribute_", "", $first_key);
			$sql = "SELECT DISTINCT p.ID, pm.meta_value FROM $tablePost as p INNER JOIN $tablePostMeta as pm ON p.ID = pm.post_id WHERE p.post_type = 'product_variation' AND p.post_parent = $product_id AND pm.meta_key = '$first_key'";
			$variants = $wpdb->get_results($sql);
		}
		$tableTermOptions = $wpdb->prefix . "terms";
		$tableTermTaxonomy = $wpdb->prefix . "term_taxonomy";
		$tableTermRelationship = $wpdb->prefix . "term_relationships";
		$sql = "SELECT DISTINCT t.term_id, t.name, t.slug FROM $tableTermOptions as t INNER JOIN $tableTermTaxonomy as tt ON t.term_id = tt.term_id INNER JOIN $tableTermRelationship as tr ON tt.term_taxonomy_id = tr.term_taxonomy_id WHERE tr.object_id = $product_id AND tt.taxonomy = '$attribute'";
		$result = $wpdb->get_results($sql);
		$response = array();
		$key = 0;

		// For Tier Price
		$metaDataContent = get_post_meta($product_id, 'imprintnext_tier_content');
		$tierPriceData = array();
		$commonTierPrice = array();
		$variantTierPrice = array();
		$sameforAllVariants = $isTier = false;
		if (!empty($metaDataContent)) {
			$tierPriceData = $metaDataContent[0];
			$isTier = true;

			if ($tierPriceData['pricing_per_variants'] == 'true') {
				$sameforAllVariants = true;
				foreach ($tierPriceData['price_rules'][0]['discounts'] as $discount) {
					$commonTierPrice[] = array("quantity" => $discount['lower_limit'],
						"discount" => $discount['discount'],
						"discountType" => $tierPriceData['discount_type'],
					);
				}
			} else {
				foreach ($tierPriceData['price_rules'] as $variant) {
					foreach ($variant['discounts'] as $discount) {
						$variantTierPrice[$variant['id']][] = array("quantity" => $discount['lower_limit'],
							"discount" => $discount['discount'],
							"discountType" => $tierPriceData['discount_type'],
						);
					}
				}
			}
		}
		// End

		foreach ($result as $key => $value) {
			$vkey = array_search($value->slug, array_column((array) $variants, 'meta_value'));
			if (!is_bool($vkey)) {
				$response[$key]['id'] = $value->term_id;
				$response[$key]['slug'] = $value->slug;
				$response[$key]['name'] = $value->name;
				$response[$key]['variant_id'] = $variants[$vkey]->ID;
				//$pvariant = new WC_Product_Variation($variants[$vkey]->ID);
				$pvariant = wc_get_product($variants[$vkey]->ID);
				if ($pvariant->get_manage_stock() != 1 && $pvariant->get_stock_status() == "instock") {
					$stock_quantity = 999999999;
				} else {
					$stock_quantity = $pvariant->get_stock_quantity();
				}
				$response[$key]['inventory']['stock'] = $stock_quantity;
				$response[$key]['inventory']['min_quantity'] = 1;
				$response[$key]['inventory']['max_quantity'] = $stock_quantity;
				$response[$key]['inventory']['quantity_increments'] = 1;
				$response[$key]['price'] = $pvariant->price;
				$i = 0;
				if ($pvariant->image_id != 0) {
					$imageSrc = wp_get_attachment_image_src($pvariant->image_id, 'full');
					$imageSrcThumb = wp_get_attachment_image_src($pvariant->image_id, 'thumbnail');
					$response[$key]['sides'][$i]['image']['src'] = $imageSrc[0];
					$response[$key]['sides'][$i]['image']['thumbnail'] = $imageSrcThumb[0];
					$i++;
				}
				// New changes
				$attachments = get_post_meta($variants[$vkey]->ID, '_product_image_gallery', true);
				if (empty($attachments)) {
					$attachments = get_post_meta($variants[$vkey]->ID, 'variation_image_gallery', true);
				}
				$attachmentsExp = array_filter(explode(',', $attachments));
				foreach ($attachmentsExp as $id) {
					$imageSrc = wp_get_attachment_image_src($id, 'full');
					$imageSrcThumb = wp_get_attachment_image_src($id, 'thumbnail');
					$response[$key]['sides'][$i]['image']['src'] = $imageSrc[0];
					$response[$key]['sides'][$i]['image']['thumbnail'] = $imageSrcThumb[0];
					$i++;
				}
				if ($isTier) {
					$response[$key]['tier_prices'] = ($sameforAllVariants === true ? $this->createTierPrice($commonTierPrice, $pvariant->price) : $this->createTierPrice($variantTierPrice[$variants[$vkey]->ID], $pvariant->price));
				}
				$key++;
			}
		}
		return rest_ensure_response($response);
	}

	public function createTierPrice($tierPriceRule, $variantPrice) {
		$tierPrice = array();
		foreach ($tierPriceRule as $tier) {
			$thisTier = array();
			$thisTier['quantity'] = $tier['quantity'];
			$thisTier['percentage'] = ($tier['discountType'] == "percentage" ? $tier['discount'] : number_format(($tier['discount'] / $variantPrice) * 100, 2));
			$thisTier['price'] = ($tier['discountType'] == "flat" ? ($variantPrice - $tier['discount']) : ($variantPrice - (($tier['discount'] / 100) * $variantPrice)));
			$thisTier['discount'] = $tier['discount'] . "_" . $tier['discountType'];
			$tierPrice[] = $thisTier;
		}

		return $tierPrice;
	}

	function inkxe_get_options_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['product_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 20,
		);
		$args['attribute'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => 'pa_xe_color',
		);
		$args['store_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		return $args;
	}

	function get_attribute_type($id) {
		global $wpdb;
		$attribute = $wpdb->get_row($wpdb->prepare("
                SELECT *
                FROM {$wpdb->prefix}woocommerce_attribute_taxonomies
                WHERE attribute_id = %d
             ", $id));
		return $attribute; //->attribute_type;
	}
	function inkxe_get_product_attribute($request) {
		global $wpdb;
		$product_id = $request['product_id'];
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$product = wc_get_product($product_id);
		$attributes = $product->get_attributes();
		$response = array();
		$i = 0;
		foreach ($attributes as $attribute) {
			$attribute_details = $this->get_attribute_type($attribute['id']);
			$attribute_type = $attribute_details->attribute_type;
			if ($attribute_details->attribute_label != 'xe_is_designer') {
				$response[$i]['id'] = $attribute['id'];
				$response[$i]['name'] = $attribute_details->attribute_label;
				$j = 0;
				foreach ($attribute['options'] as $option) {
					$term = get_term_by('id', $option, 'pa_' . $attribute_details->attribute_name);
					$response[$i]['options'][$j]['id'] = $option;
					$response[$i]['options'][$j]['name'] = $term->name;
					$j++;
				}
				$i++;
			}
		}
		return rest_ensure_response($response);

	}
	function inkxe_get_attributes_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['product_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 20,
		);
		$args['store_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',

		);
		return $args;
	}

	function inkxe_get_product_image_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['product_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 0,
		);
		$args['variant_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 0,
		);
		$args['details'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 0,
		);
		$args['store_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',

		);
		return $args;
	}
	function inkxe_get_product_count($request) {
		global $wpdb;
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$tableProduct = $wpdb->prefix . "posts";
		$sql = "SELECT COUNT(DISTINCT ID) as total FROM $tableProduct WHERE post_type = 'product' AND post_status = 'publish'";
		$totalRecords = $wpdb->get_results($sql);
		$output['total'] = $totalRecords[0]->total;
		$output['vc'] = WC_VERSION;
		return rest_ensure_response($output);
	}

	function get_multiple_shipping_address_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['userId'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		return $args;
	}
	function delete_multiple_shipping_address_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		return $args;
	}
	function update_multiple_shipping_address_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['request'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		$args['id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		return $args;
	}
	function create_multiple_shipping_address_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['request'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);

		return $args;
	}
	function create_customer_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['request'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		return $args;
	}
	function update_customer_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['request'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		$args['user_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		return $args;
	}
	function delete_customer_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['user_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		$args['store_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		return $args;
	}
	function countries_arguments() {
		$args = array();
		$args['store_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		return $args;
	}
	function states_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['country_code'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		$args['store_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		return $args;
	}
	function country_code_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['country_code'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		return $args;
	}
	function country_state_code_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['country_code'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		$args['state_code'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		return $args;
	}
	function user_count_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['customer_no_order'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
		);
		$args['from_date'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
		);
		$args['to_date'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
		);
		$args['search'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
		);
		$args['store_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);
		$args['quote'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
		);
		$args['notification'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
		);

		return $args;
	}
	function inkxe_get_category_products($request) {
		global $wpdb;
		$post_per_page = 10; //$request['range'];
		$args = array(
			'taxonomy' => "product_cat",
			'parent' => 0,
			'include' => $ids,
		);
		$product_categories = get_terms($args);
		$j = 0;
		foreach ($product_categories as $key => $value) {
			$cats = array();
			$args = array(
				'taxonomy' => "product_cat",
				'child_of' => $value->term_id,
				'include' => $ids,
			);
			$sub_categories = get_terms($args);
			$cats[] = $value->slug;
			foreach ($sub_categories as $subcat) {
				$cats[] = $subcat->slug;
			}
			//$catString = implode(',',$cats);
			$products = wc_get_products(array(
				'numberposts' => 7,
				'post_status' => 'published',
				'category' => $cats,
			));
			$response = array();
			foreach ($products as $k => $product) {
				$response[$k]['id'] = $product->id;
				$response[$k]['name'] = $product->name;
				$response[$k]['type'] = $product->get_type();
				$response[$k]['sku'] = $product->sku;
				$response[$k]['price'] = $product->price;
				$i = 0;
				$imageThumb = array();
				if ($product->get_type() == 'variable') {
					$args = array(
						'post_type' => 'product_variation',
						'post_status' => array('publish'),
						'post_parent' => $product->id,
					);
					$variations = get_posts($args);
					if (!empty($variations)) {
						$variation_id = $variations[0]->ID;
						$variation = wc_get_product($variation_id);
						$imageId = $variation->get_image_id();
						if ($imageId != 0) {
							$imageSrc = wp_get_attachment_image_src($imageId, 'thumbnail');
							$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
							$i++;
						}
						//New changes
						$galleryImageIds = get_post_meta($variation_id, '_product_image_gallery', true);
						if (empty($galleryImageIds)) {
							$galleryImageIds = get_post_meta($variation_id, 'variation_image_gallery', true);
						}
						$galleryImageIds = array_filter(explode(',', $galleryImageIds));
						foreach ($galleryImageIds as $id) {
							$imageSrc = wp_get_attachment_image_src($id, 'thumbnail');
							$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
							$i++;
						}
					} else {
						$imageId = get_post_meta($product->id, '_thumbnail_id', true);
						if ($imageId != 0) {
							$imageSrc = wp_get_attachment_image_src($imageId, 'thumbnail');
							$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
							$i++;
						}
						$galleryImageIds = get_post_meta($product->id, '_product_image_gallery', true);
						if ($galleryImageIds != "") {
							$galleryImageIds = explode(',', $galleryImageIds);
							foreach ($galleryImageIds as $id) {
								$imageSrc = wp_get_attachment_image_src($id, 'thumbnail');
								$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
								$i++;
							}
						}
					}

				} else {
					$imageId = get_post_meta($product->id, '_thumbnail_id', true);
					if ($imageId != 0) {
						$imageSrc = wp_get_attachment_image_src($imageId, 'thumbnail');
						$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
						$i++;
					}
					$galleryImageIds = get_post_meta($product->id, '_product_image_gallery', true);
					if ($galleryImageIds != "") {
						$galleryImageIds = explode(',', $galleryImageIds);
						foreach ($galleryImageIds as $id) {
							$imageSrc = wp_get_attachment_image_src($id, 'full');
							$imageThumb[] = $imageSrc[0] . "?rvn=" . $i;
							$i++;
						}
					}
				}
				$response[$k]['image'] = $imageThumb;
			}
			$output['categories'][$j]['id'] = $value->term_id;
			$output['categories'][$j]['name'] = $value->name;
			$output['categories'][$j]['products'] = $response;
			//print_r($response);
			$j++;

		}
		return rest_ensure_response($output);
	}

	function product_images($request) {
		$product_id = $request['product_id'];
		$variant_id = $request['variant_id'];
		$details = $request['details'];
		$response = array();
		$id = ($product_id != $variant_id) ? $variant_id : $product_id;
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$product = wc_get_product($id);
		$attributes = $product->get_attributes();
		$i = 0;
		if ($product->image_id != 0) {
			$imageSrc = wp_get_attachment_image_src($product->image_id, 'full');
			$imageSrcThumb = wp_get_attachment_image_src($product->image_id, 'thumbnail');
			$response['images'][$i]['src'] = $imageSrc[0];
			$response['images'][$i]['thumbnail'] = $imageSrcThumb[0];
			$i++;
		}
		if ($product_id != $variant_id) {
			// New changes
			$attachments = get_post_meta($variant_id, '_product_image_gallery', true);
			if (empty($attachments)) {
				$attachments = get_post_meta($variant_id, 'variation_image_gallery', true);
			}
			$attachmentsExp = array_filter(explode(',', $attachments));
			foreach ($attachmentsExp as $id) {
				$imageSrc = wp_get_attachment_image_src($id, 'full');
				$imageSrcThumb = wp_get_attachment_image_src($id, 'thumbnail');
				$response['images'][$i]['src'] = $imageSrc[0];
				$response['images'][$i]['thumbnail'] = $imageSrcThumb[0];
				$i++;
			}
		} else {
			foreach ($product->gallery_image_ids as $id) {
				$imageSrc = wp_get_attachment_image_src($id, 'full');
				$imageSrcThumb = wp_get_attachment_image_src($id, 'thumbnail');
				$response['images'][$i]['src'] = $imageSrc[0];
				$response['images'][$i]['thumbnail'] = $imageSrcThumb[0];
				$i++;
			}
		}

		// For Tier Price
		$metaDataContent = get_post_meta($product_id, 'imprintnext_tier_content');
		$tierPriceData = array();
		$commonTierPrice = array();
		$variantTierPrice = array();
		$sameforAllVariants = $isTier = false;
		if (!empty($metaDataContent)) {
			$tierPriceData = $metaDataContent[0];
			$isTier = true;

			if ($tierPriceData['pricing_per_variants'] == 'true') {
				$sameforAllVariants = true;
				foreach ($tierPriceData['price_rules'][0]['discounts'] as $discount) {
					$commonTierPrice[] = array("quantity" => $discount['lower_limit'],
						"discount" => $discount['discount'],
						"discountType" => $tierPriceData['discount_type'],
					);
				}
			} else {
				foreach ($tierPriceData['price_rules'] as $variant) {
					foreach ($variant['discounts'] as $discount) {
						$variantTierPrice[$variant['id']][] = array("quantity" => $discount['lower_limit'],
							"discount" => $discount['discount'],
							"discountType" => $tierPriceData['discount_type'],
						);
					}
				}
			}
		}
		if ($isTier) {
			$response['tier_prices'] = ($sameforAllVariants === true ? $this->createTierPrice($commonTierPrice, $product->price) : $this->createTierPrice($variantTierPrice[$variant_id], $product->price));
		}
		// End

		if ($details) {
			$response['name'] = get_the_title($product_id);
			$response['price'] = $product->price;
			$attribute = [];
			if ($product_id != $variant_id) {
				foreach ($attributes as $key => $value) {
					$key = urldecode($key);
					$attrTermDetails = get_term_by('slug', $value, $key);
					if (empty($attrTermDetails)) {
						$attrTermDetails = get_term_by('name', $value, $key);
					}

					$term = wc_attribute_taxonomy_id_by_name($key);
					$attrName = wc_attribute_label($key);
					$attrValId = $attrTermDetails->term_id;
					$attrValName = $attrTermDetails->name;
					$attribute[$attrName]['id'] = $attrValId;
					$attribute[$attrName]['name'] = $attrValName;
					$attribute[$attrName]['attribute_id'] = $term;
				}
			} else {
				foreach ($attributes as $attrKey => $attributelist) {
					if ($attrKey != 'pa_xe_is_designer') {
						foreach ($attributelist['options'] as $key => $value) {
							$term = wc_attribute_taxonomy_id_by_name($attributelist['name']);
							$attrName = wc_attribute_label($attributelist['name']);
							$attrValId = $value;
							$attrTermDetails = get_term_by('id', absint($value), $attributelist['name']);
							$attrValName = $attrTermDetails->name;
							$attribute[$attrName]['id'] = $attrValId;
							$attribute[$attrName]['name'] = $attrValName;
							$attribute[$attrName]['attribute_id'] = $term;
						}
					}
				}
			}
			$response['attributes'] = $attribute;
		}
		return rest_ensure_response($response);
	}

	function wc_paths($request) {
		$output['abspath'] = ABSPATH;
		$output['wc_abspath'] = WC_ABSPATH;
		return rest_ensure_response($output);
	}

	function list_all_attributes() {
		global $wpdb;
		$tableTerm = $wpdb->prefix . "terms";
		$tableTermTaxonomy = $wpdb->prefix . "term_taxonomy";
		$tableTaxonomy = $wpdb->prefix . "woocommerce_attribute_taxonomies";
		$sql = "SELECT attribute_id as id, concat('pa_', attribute_name) as slug, attribute_label as name, attribute_type as type FROM $tableTaxonomy WHERE attribute_type='select'";
		$attributes = $wpdb->get_results($sql);
		$attributeList = [];
		foreach ($attributes as $key => $attrubute) {
			if ($attrubute->name != 'xe_is_designer') {
				$attributeList[$key] = $attrubute;
				$query = "SELECT t.term_id as id, t.name, t.slug FROM $tableTerm as t INNER JOIN  $tableTermTaxonomy as tt ON t.term_id = tt.term_id WHERE tt.taxonomy = '$attrubute->slug'";
				$terms = $wpdb->get_results($query);
				$attributeList[$key]->terms = $terms;
			}
		}
		return rest_ensure_response($attributeList);
	}

	function inkxe_get_customer_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['orderby'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => 'id',
		);
		$args['order'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => 'DESC',
		);
		$args['page'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 1,
		);
		$args['per_page'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 3,
		);
		$args['pagination'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 1,
		);
		$args['search'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['from_date'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['from_date'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['customer_no_order'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['store_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => '',
		);
		$args['quote'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		$args['notification'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',
			'default' => '',
		);
		return $args;
	}

	function inkxe_country_state_name() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['countryState'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 0,
		);

		return $args;
	}
	function inkxe_get_customer_details() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['customer_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 0,
		);
		return $args;
	}
	function inkxe_get_product_categories() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['categories_option'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',

		);
		return $args;
	}
	function inkxe_get_product_attributes() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['store_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',

		);
		//print_r($args);exit;
		return $args;
	}
	function inkxe_get_product_attributes_terms() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['store_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',

		);
		$args['attribute_name'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'string',

		);
		return $args;
	}
	function inkxe_create_product_attributes_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['attributes_option'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',

		);
		return $args;
	}
	function inkxe_get_order_details_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['order_option'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',

		);
		return $args;
	}
	function inkxe_order_item_details_arguments() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['order_item_option'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',

		);
		return $args;
	}
	function list_all_customers($request) {
		global $wpdb;
		$page = $request['per_page'] * ($request['page'] - 1);
		$pagination = $request['pagination'];
		$per_page = ($pagination != 0) ? $request['per_page'] : -1;
		if (!empty($request['fetch']) && !empty($request['search'])) {
			$per_page = -1;
		}
		$search = addslashes($request['search']);
		$order_by = $request['orderby'];
		$orderBy = "";
		$order_by = $request['orderby'];
		if ($order_by == "name") {
			$orderBy = "username";
		} else {
			$orderBy = $order_by;
		}
		$order = $request['order'];
		$from_date = $request['from_date'] ? $request['from_date'] : '';
		$to_date = $request['to_date'] ? $request['to_date'] : '';
		$customer_no_order = $request['customer_no_order'] ? $request['customer_no_order'] : '';
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$fetch = $request['fetch'] ? $request['fetch'] : '';
		$notification = $request['notification'] ? $request['notification'] : '';
		$inclusive = true;
		$metaQueryValue = '';
		$compare = '';
		$orderCount = [];
		if ($customer_no_order == 'true') {
			$metaQueryValue = 0;
			$compare = '=';
			$orderCount = array(
				'relation' => 'OR',
				array(
					'key' => '_order_count',
					'compare' => 'NOT EXISTS',
					'value' => '',
				),
				array(
					'key' => '_order_count',
					'value' => $metaQueryValue,
					'compare' => $compare,
				),
			);
		} else {
			$metaQueryValue = 0;
			$compare = '>';
			$orderCount = array(
				array(
					'key' => '_order_count',
					'value' => $metaQueryValue,
					'compare' => $compare,
				),
			);
		}
		$customerList = array();
		$metaQueryArray = [];
		if (empty($fetch) && empty($notification)) {
			$metaQueryArray = $orderCount;
		}
		$args = array(
			'role' => 'Customer',
			'orderby' => $orderBy,
			'order' => $order,
			'number' => $per_page,
			'offset' => $page,
			'search' => '*' . esc_attr($search) . '*',
			'meta_query' => $metaQueryArray,
			'date_query' => array(
				'relation' => 'OR',
				array(
					'before' => $to_date,
					'after' => $from_date,
					'inclusive' => $inclusive,
				),
			),
		);
		$count_args = array(
			'role' => 'Customer',
			'search' => '*' . esc_attr($search) . '*',
			'meta_query' => $metaQueryArray,
			'date_query' => array(
				'relation' => 'OR',
				array(
					'before' => $to_date,
					'after' => $from_date,
					'inclusive' => $inclusive,
				),
			),
		);
		$users = get_users($args);
		$wp_user_query = new WP_User_Query($count_args);
		$total_user = (int) $wp_user_query->get_total();
		$i = 0;
		foreach ($users as $user) {
			$total_orders = get_user_meta($user->ID, '_order_count', true) ? get_user_meta($user->ID, '_order_count', true) : 0;
			$order_id = '';
			if ($customer_no_order == 'false' && $total_orders > 0) {
				$customer = new WC_Customer($user->ID);
				$last_order = $customer->get_last_order();
				$order_id = $last_order->get_id();
			}
			$UserData = get_user_meta($user->ID);
			$user_name = explode(" ", $user->display_name);
			$first_name = !isset($UserData['billing_first_name'][0]) ? $user_name[0] : $UserData['billing_first_name'][0];
			$last_name = !isset($UserData['billing_last_name'][0]) ? $user_name[1] : $UserData['billing_last_name'][0];
			$customerList[$i]['id'] = $user->ID;
			$customerList[$i]['first_name'] = $first_name;
			$customerList[$i]['last_name'] = $last_name;
			$customerList[$i]['email'] = $user->user_email;
			$customerList[$i]['date_created'] = $user->user_registered;
			$customerList[$i]['total_orders'] = $total_orders;
			$customerList[$i]['last_order_id'] = $order_id;
			$i++;
		}
		$customerResponse['total_user'] = $total_user;
		$customerResponse['customer_list'] = $customerList;
		return rest_ensure_response($customerResponse);

	}

	/**
	 * This function is where we register our routes for our example endpoint.
	 */
	function inkxe_register_custom_routes() {
		// register_rest_route() handles more arguments but we are going to stick to the basics for now.
		register_rest_route("InkXEProductDesigner", '/orders', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_get_orders'),
			// Here we register our permissions callback. The callback is fired before the main callback to check if the current user can access the endpoint.
			'args' => $this->inkxe_get_order_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/products', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_get_products'),
			// Here we register our permissions callback. The callback is fired before the main callback to check if the current user can access the endpoint.
			'args' => $this->inkxe_get_product_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/options', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_get_attribute_options'),
			// Here we register our permissions callback. The callback is fired before the main callback to check if the current user can access the endpoint.
			'args' => $this->inkxe_get_options_arguments(),
		));
		register_rest_route("InkXEProductDesigner", '/product/attributes', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_get_product_attribute'),
			// Here we register our permissions callback. The callback is fired before the main callback to check if the current user can access the endpoint.
			'args' => $this->inkxe_get_attributes_arguments(),
		));
		register_rest_route("InkXEProductDesigner", '/product/count', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_get_product_count'),
		));

		register_rest_route("InkXEProductDesigner", '/categories/products', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_get_category_products'),
		));

		register_rest_route("InkXEProductDesigner", '/path', array(
			'methods' => 'GET',
			'callback' => array($this, 'wc_paths'),
		));

		register_rest_route("InkXEProductDesigner", '/product/images', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'product_images'),
			'args' => $this->inkxe_get_product_image_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/attributes', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'list_all_attributes'),
		));
		register_rest_route("InkXEProductDesigner", '/customer/multiple_shipping_address', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_multiple_shipping_address'),
			'args' => $this->get_multiple_shipping_address_arguments(),
		));
		register_rest_route("InkXEProductDesigner", '/customer/delete_shipping_address', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'delete_multiple_shipping_address'),
			'args' => $this->delete_multiple_shipping_address_arguments(),
		));
		register_rest_route("InkXEProductDesigner", '/customer/update_shipping_address', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'update_multiple_shipping_address'),
			'args' => $this->update_multiple_shipping_address_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/customer/create_shipping_address', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'create_multiple_shipping_address'),
			'args' => $this->create_multiple_shipping_address_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/customer/create_customer', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_create_customer'),
			'args' => $this->create_customer_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/customer/update_customer', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_update_customer'),
			'args' => $this->update_customer_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/customer/delete_customer', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_delete_customer'),
			'args' => $this->delete_customer_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/get_countries', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'get',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_countries'),

		));
		register_rest_route("InkXEProductDesigner", '/get_states', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'get',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_states'),
			'args' => $this->states_arguments(),

		));

		register_rest_route("InkXEProductDesigner", '/customer/get_country_name', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'get',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_country_name'),
			'args' => $this->country_code_arguments(),

		));
		register_rest_route("InkXEProductDesigner", '/customer/get_state_name', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'get',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_state_name'),
			'args' => $this->country_state_code_arguments(),
		));
		register_rest_route("InkXEProductDesigner", '/customer_count', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'get',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'user_count'),
			'args' => $this->user_count_arguments(),

		));
		register_rest_route("InkXEProductDesigner", '/order_shipping_address', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_order_shipping_address'),
			'args' => $this->inkxe_order_shipping_address(),
		));
		register_rest_route("InkXEProductDesigner", '/store_order_statuses', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_store_order_statuses'),
			'args' => $this->inkxe_order_statuses(),

		));
		register_rest_route("InkXEProductDesigner", '/customer/multiple_shipping_address', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_multiple_shipping_address'),
			'args' => $this->get_multiple_shipping_address_arguments(),
		));
		register_rest_route("InkXEProductDesigner", '/customer/delete_shipping_address', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'delete_multiple_shipping_address'),
			'args' => $this->delete_multiple_shipping_address_arguments(),
		));
		register_rest_route("InkXEProductDesigner", '/customer/update_shipping_address', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'update_multiple_shipping_address'),
			'args' => $this->update_multiple_shipping_address_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/customer/create_shipping_address', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'create_multiple_shipping_address'),
			'args' => $this->create_multiple_shipping_address_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/customer/create_customer', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_create_customer'),
			'args' => $this->create_customer_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/customer/update_customer', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_update_customer'),
			'args' => $this->update_customer_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/customer/delete_customer', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'inkxe_delete_customer'),
			'args' => $this->delete_customer_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/get_countries', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'get',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_countries'),
			'args' => $this->countries_arguments(),
		));
		register_rest_route("InkXEProductDesigner", '/get_states', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'get',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_states'),
			'args' => $this->states_arguments(),

		));

		register_rest_route("InkXEProductDesigner", '/customer/get_country_name', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'get',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_country_name'),
			'args' => $this->country_code_arguments(),

		));
		register_rest_route("InkXEProductDesigner", '/customer/get_state_name', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'get',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_state_name'),
			'args' => $this->country_state_code_arguments(),

		));
		register_rest_route("InkXEProductDesigner", '/orders/archive', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'archive_order'),
		));

		register_rest_route("InkXEProductDesigner", '/customers', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'list_all_customers'),
			'args' => $this->inkxe_get_customer_arguments(),
		));

		register_rest_route("InkXEProductDesigner", '/country_state_name', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_country_state_name'),
			'args' => $this->inkxe_country_state_name(),

		));
		register_rest_route("InkXEProductDesigner", '/customer_details', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_customer_details'),
			'args' => $this->inkxe_get_customer_details(),
		));

		register_rest_route("InkXEProductDesigner", '/products_categories', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_product_categories'),
			'args' => $this->inkxe_get_product_categories(),
		));

		register_rest_route("InkXEProductDesigner", '/products/attributes', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_product_attributes'),
			'args' => $this->inkxe_get_product_attributes(),
		));

		register_rest_route("InkXEProductDesigner", '/products/attributes/terms', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_product_attributes_terms'),
			'args' => $this->inkxe_get_product_attributes_terms(),
		));

		register_rest_route("InkXEProductDesigner", '/products/attributes/create', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'POST',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'create_product_attributes'),
			'args' => $this->inkxe_create_product_attributes_arguments(),
		));
		register_rest_route("InkXEProductDesigner", '/order_details', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_order_details'),
			'args' => $this->inkxe_get_order_details_arguments(),
		));
		register_rest_route("InkXEProductDesigner", '/order_item_details', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'get_order_item_details'),
			'args' => $this->inkxe_order_item_details_arguments(),
		));
		register_rest_route("InkXEProductDesigner", '/multi_store', array(
			// By using this constant we ensure that when the WP_REST_Server changes our readable endpoints will work as intended.
			'methods' => 'GET',
			// Here we register our callback. The callback is fired when this endpoint is matched by the WP_REST_Server class.
			'callback' => array($this, 'all_blogs_list'),
		));
	}

	function archive_order($request) {
		$request_parameter = $request->get_params();
		$status = 0;
		$store_id = $request_parameter['store_id'] ? $request_parameter['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$order_ids = json_decode($request_parameter['order_id']);
		foreach ($order_ids as $id) {
			if (wp_trash_post($id)) {
				$status = 1;
			}

		}
		$response['status'] = $status;
		return rest_ensure_response($response);
	}

	public function is_template_assign($categories) {
		$result = "";
		$url = $this->api_path . "api/v1/template-products?prodcatID=" . $categories;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$result = curl_exec($ch);
		curl_close($ch);
		return json_decode($result, true);
	}

	public function ink_pd_customize_button() {
		$productId = get_the_ID();
		$product = wc_get_product($productId);
		$categories = implode(",", $product->get_category_ids());
		$resTempAssign = $this->is_template_assign($categories);
		$isAssignTemp = 0;
		if ($resTempAssign['is_enabled']) {
			$isAssignTemp = 1;
		}
		$customDesignId = get_post_meta($productId, 'custom_design_id', true);
		foreach ($product->attributes as $key => $value) {
			$attrTaxoName = $value->get_name();
			if ($attrTaxoName == "pa_xe_is_designer") {
				if (get_term_by('id', $value->get_options()[0], $attrTaxoName)->name == 1) {
					$response = 1;
				} else {
					$response = 0;
				}
			}
		}
		if ($response) {
			$xepath = get_site_url();
			if ($product->get_type() == 'variable') {
				$args = array(
					'post_type' => 'product_variation',
					'post_status' => array('publish'),
					'post_parent' => $productId, // get parent post-ID
				);
				$variations = get_posts($args);
				$variation_id = $variations[0]->ID;
			} else {
				$variation_id = $productId;
			}

			$useragent = $_SERVER['HTTP_USER_AGENT'];
			// Checking mobile devices
			if (preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
				if ($customDesignId) {
					$url = $this->api_path . '?id=' . $productId . "&vid=" . $variation_id . "&dpid=" . $customDesignId . "&pbti=" . $isAssignTemp;
				} else {
					$url = $this->api_path . '?id=' . $productId . "&vid=" . $variation_id . "&pbti=" . $isAssignTemp;
				}
			} else {
				if ($customDesignId) {
					$url = $xepath . '/product-designer?id=' . $productId . "&vid=" . $variation_id . "&dpid=" . $customDesignId . "&pbti=" . $isAssignTemp;
				} else {
					$url = $xepath . '/product-designer?id=' . $productId . "&vid=" . $variation_id . "&pbti=" . $isAssignTemp;
				}
			}
			/* get current store id*/
			$currentBlogId = get_current_blog_id() ? get_current_blog_id() : 1;
			// Default quantity pass
			$url = $url . "&qty=1&store_id=" . $currentBlogId;
			if ($currentBlogId > 1) {
				$checkCustomizeButton = $this->api_path . 'api/v1/multi-store/customize-button/' . $currentBlogId;
				$responseArray = $this->getGeneralSetting($checkCustomizeButton);
				if ($responseArray['data'] == 1) {
					echo '<a style="margin-left: 5%;" id="customize" href="' . $url . '" class="customize-btn button disabled alt" >Customize</a>';
				}
			} else {
				echo '<a style="margin-left: 5%;" id="customize" href="' . $url . '" class="customize-btn button disabled alt" >Customize</a>';
			}
			?>
            <script>
                jQuery(document).ready(function($) {
                    jQuery("#customize").removeClass('disabled');
                });
            </script>
            <?php

		}
	}

	public function ink_pd_display_dropdown_variation_add_cart() {
		global $product;
		if ($product->is_type('variable')) {
			$customDesignId = get_post_meta(get_the_ID(), 'custom_design_id', true);
			if (!$customDesignId) {
				$customDesignId = 0;
			}
			$args = array(
				'post_type' => 'product_variation',
				'post_status' => array('publish'),
				'post_parent' => get_the_ID(), // get parent post-ID
			);
			$variations = get_posts($args);
			$variation_id = $variations[0]->ID;
			?>
            <script>
                jQuery(document).ready(function($) {
                    $('input.variation_id').change( function(){
                        var url = $("#customize").attr('href');
                        if (typeof url !== 'undefined') {
							var currentUrl = new URL(url);
                        	var storeId = currentUrl.searchParams.get('store_id') ? currentUrl.searchParams.get('store_id'):1;
                            var url1Split = url.split("&pbti=");
                            var qty = $("input[name=quantity]").val();
                            var dpid = <?php echo $customDesignId ?>;
                            if( '' != $('input.variation_id').val() ) {
                                var var_id = $('input.variation_id').val();
                                if(url.search("vid")!=-1)
                                {
                                    var urlSplit = url.split("&vid=");
                                    url = urlSplit[0]+"&vid="+var_id;
                                }
                                else
                                {
                                    url = url+"&vid="+var_id;
                                }
                            } else {
                                var pro_id = <?php echo $variation_id ?>;
                                if(url.search("vid")!=-1)
                                {
                                    var urlSplit = url.split("&vid=");
                                    url = urlSplit[0]+"&vid="+pro_id;
                                }
                                else
                                {
                                    url = url+"&vid="+pro_id;
                                }
                            }

                            if (dpid != "") {
                                url = url+"&dpid="+dpid;
                            }

                            if(url.search("pbti")!=-1)
                            {
                                url = url+"&pbti="+url1Split[1];
                            }
                            else
                            {
                                url = url+"&pbti="+url1Split[1];
                            }
                            if (dpid != "") {
                                url = url+"&dpid="+dpid;
                            }
                            let tempUrl = url.split("&qty");
                            url = tempUrl[0]+"&qty="+qty+"&store_id="+storeId;
                            $("#customize").attr('href',url);
                        }
                    });
                });
            </script>
            <?php
}
	}

	public function ink_pd_after_add_to_cart_quantity() {
		?>
        <script>
            jQuery(document).ready(function($) {
                $("input[name=quantity]").bind("change paste keyup", function() {
                    var qty = $("input[name=quantity]").val();
                    var url = $("#customize").attr('href');
                    if (typeof url !== 'undefined') {
                    	var currentUrl = new URL(url);
						var storeId = currentUrl.searchParams.get('store_id') ? currentUrl.searchParams.get('store_id'):1;
                        url = url.split("&qty");
                        url = url[0]+"&qty="+qty+"&store_id="+storeId;
                        $("#customize").attr('href',url);
                    }
                });
            });
        </script>

        <?php
}

	public function ink_pd_disable_customize_product_cart_item_quantity($product_quantity, $cart_item_key, $cart_item) {
		$custom_design_id = $cart_item['custom_design_id'];
		if ($custom_design_id) {
			$product_quantity = sprintf('' . $cart_item["quantity"] . ' <input type="hidden" name="cart[%s][qty]" value="' . $cart_item["quantity"] . '" />', $cart_item_key);
		}
		return $product_quantity;
	}
	/*soumya changes*/
	function get_multiple_shipping_address($request) {
		global $wpdb;
		$result = array();
		$userId = $request['userId'];
		$shippingAddress = $wpdb->prefix . "multipleshippingaddress";
		$query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($shippingAddress));
		if ($wpdb->get_var($query) == $shippingAddress) {
			$sql = "SELECT *  FROM " . $shippingAddress . " WHERE user_id=" . $userId;
			$result = $wpdb->get_results($sql);

		}
		return $result;
	}

	function delete_multiple_shipping_address($request) {
		global $wpdb;
		$result = array();
		$id = $request['id'];
		$shippingAddress = $wpdb->prefix . "multipleshippingaddress";
		$query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($shippingAddress));
		if ($wpdb->get_var($query) == $shippingAddress) {
			$sql = 'DELETE  FROM ' . $shippingAddress . ' WHERE id = "' . $id . '"';
			$status = $wpdb->query($sql);
			if ($status) {
				$result = array('status' => '1', 'message' => 'Deleted Successfully');
			} else {
				$result = array('status' => '0', 'message' => $wpdb->show_errors());
			}
		}
		return $result;

	}
	function update_multiple_shipping_address($request) {
		global $wpdb;
		$result = array();
		$id = $request['id'];
		$first_name = $request['request']['first_name'];
		$last_name = $request['request']['last_name'];
		$address_1 = $request['request']['address_1'];
		$address_2 = $request['request']['address_2'];
		$company = $request['request']['company'];
		$city = $request['request']['city'];
		$post_code = $request['request']['post_code'];
		$country = $request['request']['country'];
		$state = $request['request']['state'];
		$mobile_no = $request['request']['mobile_no'];
		if ($id == 0) {
			if (!empty($request['request']['user_id'])) {
				$user_id = $request['request']['user_id'];
				$update_user_meta = array(
					"shipping_address_1" => $address_1,
					"shipping_address_2" => $address_2,
					"shipping_city" => $city,
					"shipping_state" => $state,
					"shipping_postcode" => $post_code,
					"shipping_country" => $country,
					"shipping_company" => $company,
					"shipping_first_name" => $first_name,
					"shipping_last_name" => $last_name,
					"shipping_phone" => $mobile_no,
					"is_default" => $is_default,
				);
				$status = 0;
				foreach ($update_user_meta as $key => $value) {
					update_user_meta($user_id, $key, $value);
					$status = 1;
				}
				if ($status) {
					$result = array('status' => '1', 'message' => 'Updated Successfully');
				} else {
					$result = array('status' => '0', 'message' => $wpdb->show_errors());
				}
			} else {
				$result = array('status' => '0', 'message' => 'user id empty');
			}

		} else {
			$shippingAddress = $wpdb->prefix . "multipleshippingaddress";
			$query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($shippingAddress));
			if ($wpdb->get_var($query) == $shippingAddress) {
				$data = array(
					'first_name' => $first_name,
					'last_name' => $last_name,
					'address_line_one' => $address_1,
					'address_line_two' => $address_2,
					'company' => $company,
					'city' => $city,
					'postcode' => $post_code,
					'country' => $country,
					'state' => $state,
					'mobile_no' => $mobile_no,
				);
				$status = $wpdb->update($shippingAddress, $data, array('id' => $id));
				if ($status) {
					$result = array('status' => '1', 'message' => 'Updated Successfully');
				} else {
					$result = array('status' => '0', 'message' => $wpdb->show_errors());
				}
			}
		}
		return $result;
	}
	function create_multiple_shipping_address($request) {
		global $wpdb;
		$result = array();
		$user_id = $request['request']['user_id'];
		$first_name = $request['request']['first_name'] ? $request['request']['first_name'] : '';
		$last_name = $request['request']['last_name'] ? $request['request']['last_name'] : '';
		$address_1 = $request['request']['address_1'];
		$address_2 = $request['request']['address_2'];
		$company = $request['request']['company'] ? $request['request']['company'] : '';
		$city = $request['request']['city'];
		$post_code = $request['request']['post_code'];
		$country = $request['request']['country'];
		$state = $request['request']['state'];
		$mobile_no = $request['request']['mobile_no'] ? $request['request']['mobile_no'] : '';
		$store_id = $request['request']['store_id'] ? $request['request']['store_id'] : 1;
		$is_default = 1;
		/*check for shipping address*/
		$shipping_address_1 = get_user_meta($user_id, 'shipping_address_1', true);
		if (empty($shipping_address_1)) {
			if (is_multisite()) {
				switch_to_blog($store_id);
			}
			$user_dtails = get_userdata($user_id);
			$user_email = $user_dtails->data->user_email;
			$user_meta_data = array(
				'billing_address_1' => $address_1,
				'billing_address_2' => $address_2,
				'billing_city' => $city,
				'billing_state' => $state,
				'billing_postcode' => $post_code,
				'billing_country' => $country,
				'billing_email' => $user_email,
				'billing_phone' => $billing_phone,
				'billing_company' => $company_name,
				'shipping_address_1' => $address_1,
				'shipping_address_2' => $address_2,
				'shipping_city' => $city,
				'shipping_state' => $state,
				'shipping_postcode' => $post_code,
				'shipping_country' => $country,
				'shipping_company' => $company,
				'shipping_first_name' => $first_name,
				'shipping_last_name' => $last_name,
				'shipping_phone' => $mobile_no,
				'is_default' => $is_default,

			);
			$status = 0;
			foreach ($user_meta_data as $key => $value) {
				update_user_meta($user_id, $key, $value);
				$status = 1;
			}
			if ($status) {
				$result = array('status' => '1', 'message' => 'Updated Successfully');
			} else {
				$result = array('status' => '0' . $user_id, 'message' => $wpdb->show_errors());
			}
		} else {
			$shippingAddress = $wpdb->prefix . "multipleshippingaddress";
			$query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($shippingAddress));
			if ($wpdb->get_var($query) == $shippingAddress) {
				$data = array(
					'first_name' => $first_name,
					'last_name' => $last_name,
					'address_line_one' => $address_1,
					'address_line_two' => $address_2,
					'company' => $company,
					'city' => $city,
					'postcode' => $post_code,
					'country' => $country,
					'state' => $state,
					'mobile_no' => $mobile_no,
					'user_id' => $user_id,
				);
				$status = $wpdb->insert($shippingAddress, $data);
				if ($status) {
					$result = array('status' => '1', 'message' => 'Created Successfully');
				} else {
					$result = array('status' => '0', 'message' => $wpdb->show_errors());
				}
			}
		}
		return $result;
	}

	function inkxe_create_customer($request) {
		global $wpdb;
		$result = array();
		$user_email = $request['request']['user_email'];
		$user_password = $request['request']['user_password'];
		$user_name = preg_split("/@/", $user_email);
		$user_name = $wpdb->escape($user_name['0']);
		$user_email = $wpdb->escape($user_email);
		$user_password = $wpdb->escape($user_password);
		$first_name = $request['request']['first_name'];
		$last_name = $request['request']['last_name'];
		$company_name = $request['request']['company_name'];
		$company_url = $request['request']['company_url'];
		$user_role = 'customer';
		$store_id = $request['request']['store_id'] ? $request['request']['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$is_default = $request['request']['is_default'] ? $request['request']['is_default'] : 1; /*set default shipping address*/
		/*Billing  Details*/
		$billing_email = $user_email;
		$billing_phone = $request['request']['billing_phone'] ? $request['request']['billing_phone'] : '';
		$billing_address_1 = $request['request']['billing_address_1'] ? $request['request']['billing_address_1'] : '';
		$billing_address_2 = $request['request']['billing_address_2'] ? $request['request']['billing_address_2'] : '';
		$billing_city = $request['request']['billing_city'] ? $request['request']['billing_city'] : '';
		$billing_state = $request['request']['billing_state_code'] ? $request['request']['billing_state_code'] : '';
		$billing_postcode = $request['request']['billing_postcode'] ? $request['request']['billing_postcode'] : '';
		$billing_country = $request['request']['billing_country_code'] ? $request['request']['billing_country_code'] : '';
		/*Billing  Details*/
		/*Shipping Details*/
		$shipping_address_1 = $request['request']['shipping_address_1'] ? $request['request']['shipping_address_1'] : '';
		$shipping_address_2 = $request['request']['shipping_address_2'] ? $request['request']['shipping_address_2'] : '';
		$shipping_city = $request['request']['shipping_city'] ? $request['request']['shipping_city'] : '';
		$shipping_state = $request['request']['shipping_state_code'] ? $request['request']['shipping_state_code'] : '';
		$shipping_postcode = $request['request']['shipping_postcode'] ? $request['request']['shipping_postcode'] : '';
		$shipping_country = $request['request']['shipping_country_code'] ? $request['request']['shipping_country_code'] : '';
		/*Shipping Details*/
		if ($user_email) {
			/*check user email*/
			$check_user_email = get_user_by('email', $user_email);
			if (empty($check_user_email)) {
				$user_id = wp_insert_user(
					array(
						'user_login' => $user_name . time(),
						'user_pass' => $user_password,
						'first_name' => $first_name,
						'last_name' => $last_name,
						'user_email' => $user_email,
						'role' => $user_role,
						'user_url' => $company_url,
					)
				);
				if ($user_id) {
					$user_meta_data = array(
						'billing_first_name' => $first_name,
						'billing_last_name' => $last_name,
						'billing_address_1' => $billing_address_1,
						'billing_address_2' => $billing_address_2,
						'billing_city' => $billing_city,
						'billing_state' => $billing_state,
						'billing_postcode' => $billing_postcode,
						'billing_country' => $billing_country,
						'billing_email' => $billing_email,
						'billing_phone' => $billing_phone,
						'billing_company' => $company_name,
						'shipping_address_1' => $shipping_address_1,
						'shipping_address_2' => $shipping_address_2,
						'shipping_city' => $shipping_city,
						'shipping_state' => $shipping_state,
						'shipping_postcode' => $shipping_postcode,
						'shipping_country' => $shipping_country,
						'shipping_company' => $company_name,
						'shipping_first_name' => $first_name,
						'shipping_last_name' => $last_name,
						'shipping_phone' => $billing_phone,
						'is_default' => $is_default,
						'_order_count' => 0,
					);
					$status = 0;
					foreach ($user_meta_data as $key => $value) {
						add_user_meta($user_id, $key, $value);
						$status = 1;
					}
					if ($status) {
						$result = array('status' => '1', 'message' => 'Register Successfully', 'user_id' => $user_id);
					} else {
						$result = array('status' => '0', 'message' => 'Error');
					}
				}
			} else {
				$result = array('status' => '0', 'message' => 'Email id already exists. Please try another one');
			}
		} else {
			$result = array('status' => '0', 'message' => 'user email empty');
		}
		return $result;
	}
	function inkxe_update_customer($request) {
		global $wpdb;
		$result = array();
		$user_id = $request['user_id'];
		$first_name = $request['request']['first_name'];
		$last_name = $request['request']['last_name'];
		$company_name = $request['request']['company_name'];
		$company_url = $request['request']['company_url'];
		$is_default = $request['request']['is_default'];
		$billing_address_1 = $request['request']['billing_address_1'];
		$billing_address_2 = $request['request']['billing_address_2'];
		$billing_city = $request['request']['billing_city'];
		$billing_state_code = $request['request']['billing_state_code'];
		$billing_postcode = $request['request']['billing_postcode'];
		$billing_country_code = $request['request']['billing_country_code'];
		$billing_phone = $request['request']['billing_phone'];
		## SHIPPING INFORMATION:
		$shipping_address_1 = $request['request']['shipping_address_1'];
		$shipping_address_2 = $request['request']['shipping_address_2'];
		$shipping_city = $request['request']['shipping_city'];
		$shipping_state_code = $request['request']['shipping_state_code'];
		$shipping_postcode = $request['request']['shipping_postcode'];
		$shipping_country_code = $request['request']['shipping_country_code'];
		$store_id = $request['request']['store_id'] ? $request['request']['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		if (!empty($user_id)) {
			$user_data = array(
				"ID" => $user_id,
				"first_name" => $first_name,
				"last_name" => $last_name,
				"user_url" => $company_url,
			);
			$user_status = wp_update_user($user_data);
			if ($user_status) {
				$update_user_meta = array(
					"billing_address_1" => $billing_address_1,
					"billing_address_2" => $billing_address_2,
					"billing_country" => $billing_country_code,
					"billing_state" => $billing_state_code,
					"billing_city" => $billing_city,
					"billing_postcode" => $billing_postcode,
					"billing_phone" => $billing_phone,
					"billing_company" => $company_name,
					"shipping_address_1" => $shipping_address_1,
					"shipping_address_2" => $shipping_address_2,
					"shipping_city" => $shipping_city,
					"shipping_state" => $shipping_state_code,
					"shipping_postcode" => $shipping_postcode,
					"shipping_country" => $shipping_country_code,
					"shipping_phone" => $billing_phone,
					"is_default" => $is_default,
				);
				$cnt = 0;
				foreach ($update_user_meta as $key => $value) {
					update_user_meta($user_id, $key, $value);
					$cnt = 1;
				}
				if ($cnt) {
					$result = array('status' => '1', 'message' => 'Updated Successfully');
				} else {
					$result = array('status' => '0', 'message' => 'User meta updated error');
				}
			} else {
				$result = array('status' => '0', 'message' => 'user data updated error');
			}
		} else {
			$result = array('status' => '0', 'message' => 'user email empty');
		}
		return $result;
	}
	function inkxe_delete_customer($request) {
		global $wpdb;
		$result = array();
		$status = 0;
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		require_once ABSPATH . 'wp-admin/includes/user.php';
		if (!empty($request['user_id'])) {
			foreach ($request['user_id'] as $user_id) {
				wp_delete_user($user_id);
				$status = 1;
			}
		}
		if ($status == 1) {
			$result = array('status' => '1', 'message' => 'Deleted Successfully');
		} else {
			$result = array('status' => '0', 'message' => $wpdb->show_errors());
		}
		return $result;
	}
	function get_countries() {
		global $wpdb;
		$result = array();
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$countries_obj = new WC_Countries();
		$countries = $countries_obj->__get('countries');
		$i = 0;
		foreach ($countries as $key => $value) {
			$countries_code = $key;
			$countries_name = $value;
			$result[$i]['countries_code'] = $countries_code;
			$result[$i]['countries_name'] = html_entity_decode($countries_name);
			$i++;
		}
		return $result;
	}
	function get_states($request) {
		global $wpdb;
		$country_code = $request['country_code'];
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$result = array();
		$countries_obj = new WC_Countries();
		$country_states_array = $countries_obj->get_states($country_code);
		$i = 0;
		foreach ($country_states_array as $skey => $svalue) {
			$state_code = $skey;
			$state_name = $svalue;
			$result[$i]['state_code'] = $state_code;
			$result[$i]['state_name'] = html_entity_decode($state_name);
			$i++;
		}
		return $result;
	}
	function get_country_name($request) {
		$country_code = $request['country_code'];
		return WC()->countries->countries[$country_code] ? WC()->countries->countries[$country_code] : '';

	}
	function get_state_name($request) {
		$stateName = '';
		$country_code = $request['country_code'];
		$state_code = $request['state_code'];
		$stateName = WC()->countries->states[$country_code][$state_code] ? WC()->countries->states[$country_code][$state_code] : '';
		return $stateName;
	}
	function user_count($request) {
		$user_count = 0;
		$customer_no_order = $request['customer_no_order'];
		$from_date = $request['from_date'];
		$to_date = $request['to_date'];
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$search = addslashes($request['search']);
		$quote = $request['quote'] ? $request['quote'] : '';
		$notification = $request['notification'] ? $request['notification'] : '';
		$inclusive = true;
		$metaQueryValue = '';
		$compare = '';
		if ($customer_no_order == 'true') {
			$metaQueryValue = 0;
			$compare = '=';
		} else {
			$metaQueryValue = 0;
			$compare = '>';
		}
		$metaQueryArray = [];
		if (empty($quote) && empty($notification)) {
			$metaQueryArray = array(
				array(
					'key' => '_order_count',
					'value' => $metaQueryValue,
					'compare' => $compare,
				),
			);
		}
		$args = array(
			'role' => 'Customer',
			'search' => $search,
			'meta_query' => $metaQueryArray,
			'date_query' => array(
				'relation' => 'OR',
				array(
					'before' => $to_date,
					'after' => $from_date,
					'inclusive' => $inclusive,
				),
			),
		);
		$user = get_users($args);
		$user_count = count($user);
		return $user_count;
	}

	function inkxe_order_shipping_address() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['shipping_data'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
			'default' => 0,
		);

		return $args;
	}
	function inkxe_order_statuses() {
		$args = array();
		// Here we are registering the schema for the filter argument.
		$args['store_id'] = array(
			// type specifies the type of data that the argument should be.
			'type' => 'absint',
		);

		return $args;
	}

	public function get_order_shipping_address($request) {
		global $wpdb;
		$responseArray = array();
		$customerId = $request['shipping_data']['customerId'];
		$shippingId = $request['shipping_data']['shippingId'];
		if (!empty($customerId) && !empty($shippingId)) {
			/*check table*/
			$shippingAddress = $wpdb->prefix . "multipleshippingaddress";
			$query = $wpdb->prepare('SHOW TABLES LIKE %s', $wpdb->esc_like($shippingAddress));
			if ($wpdb->get_var($query) == $shippingAddress) {
				$sql = "SELECT *  FROM " . $shippingAddress . " WHERE user_id=" . $customerId . " AND id= " . $shippingId;
				$responseArray = $wpdb->get_results($sql);
			}
		}
		return $responseArray;
	}

	public function get_store_order_statuses($request) {
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$orderStatus = wc_get_order_statuses();
		$orderStatusArray = array();
		if (!empty($orderStatus)) {
			$i = 0;
			foreach ($orderStatus as $key => $value) {
				$orderStatusArray[$i]['value'] = $value;
				$orderStatusArray[$i]['key'] = str_replace('wc-', '', $key);
				$i++;
			}
		}
		return $orderStatusArray;
	}
	public function getGeneralSetting($url) {
		//  Initiate curl
		$ch = curl_init();
		// Will return the response, if false it print the response
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		// Set the url
		curl_setopt($ch, CURLOPT_URL, $url);
		// Execute
		$result = curl_exec($ch);
		// Closing
		curl_close($ch);
		return json_decode($result, true);
	}
	public function get_country_state_name($request) {
		$countryCode = $request['countryState']['countryCode'];
		$stateCode = $request['countryState']['stateCode'];
		$countryName = WC()->countries->countries[$countryCode];
		$stateName = WC()->countries->states[$countryCode][$stateCode];
		$responseArray = array(
			'countryName' => $countryName ? $countryName : $countryCode,
			'stateName' => $stateName ? $stateName : $stateCode,
		);
		return $responseArray;
	}
	function get_customer_details($request) {
		$customerDetails = array();
		$customer_id = $request['customer_id'];
		$last_order_id = 0;
		$order_value = 0;
		$total_order = 0;
		if ($customer_id > 0) {
			$result = wc_get_customer_last_order($customer_id);
			$orderData = json_decode($result, true);
			if (!empty($orderData)) {
				$last_order_id = $orderData['id'];
			}
			$orderTotalArray = array(
				'numberposts' => -1,
				'meta_key' => '_customer_user',
				'meta_value' => $customer_id,
				'post_type' => array('shop_order'),
				'post_status' => 'any',

			);
			$customer_order_total = get_posts($orderTotalArray);
			foreach ($customer_order_total as $customer_order) {
				$order = wc_get_order($customer_order);
				$order_value += $order->get_total();
			};
			$total_order = wc_get_customer_order_count($customer_id);
			$customerDetails['last_order_id'] = $last_order_id;
			$customerDetails['order_value'] = $order_value;
			$customerDetails['total_order'] = $total_order;
			$user_data = get_userdata($customer_id);
			$customerDetails['customer_id'] = $user_data->ID;
			$customerDetails['first_name'] = get_user_meta($customer_id, "first_name", true);
			$customerDetails['last_name'] = get_user_meta($customer_id, "last_name", true);
			$customerDetails['email'] = $user_data->user_email;
			$customerDetails['user_registered'] = date("M m, Y", strtotime($user_data->user_registered));
			/*GET BILLING DETAILS*/
			$customerDetails['billing_address']['billing_address_1'] = get_user_meta($customer_id, "billing_address_1", true);
			$customerDetails['billing_address']['billing_address_2'] = get_user_meta($customer_id, "billing_address_2", true);
			$customerDetails['billing_address']['billing_city'] = get_user_meta($customer_id, "billing_city", true);
			$customerDetails['billing_address']['billing_state_code'] = get_user_meta($customer_id, "billing_state", true);
			$customerDetails['billing_address']['billing_zip'] = get_user_meta($customer_id, "billing_postcode", true);
			$customerDetails['billing_address']['billing_country_code'] = get_user_meta($customer_id, "billing_country", true);
			$customerDetails['billing_address']['billing_phone'] = get_user_meta($customer_id, "billing_phone", true);
			$customerDetails['billing_address']['billing_country'] = WC()->countries->countries[get_user_meta($customer_id, "billing_country", true)];
			$customerDetails['billing_address']['billing_state'] = WC()->countries->states[get_user_meta($customer_id, "billing_country", true)][get_user_meta($customer_id, "billing_state", true)];
			/**GET SHIPPING DETAILS*/
			$customerDetails['shipping_address']['shipping_address_1'] = get_user_meta($customer_id, "shipping_address_1", true);
			$customerDetails['shipping_address']['shipping_address_2'] = get_user_meta($customer_id, "shipping_address_2", true);
			$customerDetails['shipping_address']['shipping_city'] = get_user_meta($customer_id, "shipping_city", true);
			$customerDetails['shipping_address']['shipping_state_code'] = get_user_meta($customer_id, "shipping_state", true);
			$customerDetails['shipping_address']['shipping_zip'] = get_user_meta($customer_id, "shipping_postcode", true);
			$customerDetails['shipping_address']['shipping_country_code'] = get_user_meta($customer_id, "shipping_country", true);
			$customerDetails['shipping_address']['shipping_country'] = WC()->countries->countries[get_user_meta($customer_id, "shipping_country", true)];
			$customerDetails['shipping_address']['shipping_state'] = WC()->countries->states[get_user_meta($customer_id, "shipping_country", true)][get_user_meta($customer_id, "shipping_state", true)];
		}
		return $customerDetails;
	}
	function get_product_categories($request) {
		$categories_option = $request['categories_option'];
		$name = $categories_option['name'] ? $categories_option['name'] : '';
		$store_id = $categories_option['store_id'] ? $categories_option['store_id'] : 1;
		$productId = $categories_option['productId'];
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$taxonomy = 'product_cat';
		$order = 'desc';
		$orderby = 'id';
		$show_count = 0; // 1 for yes, 0 for no
		$pad_counts = 0; // 1 for yes, 0 for no
		$hierarchical = 0; // 1 for yes, 0 for no
		$title = $name;
		$empty = 0;
		$args = array(
			'taxonomy' => $taxonomy,
			'orderby' => $orderby,
			'order' => $order,
			'show_count' => $show_count,
			'pad_counts' => $pad_counts,
			'hierarchical' => $hierarchical,
			'name' => $title,
			'hide_empty' => $empty,
		);
		if ($productId > 0) {
			$terms = get_the_terms($productId, 'product_cat');
			$all_categories = $terms;
		} else {
			$all_categories = get_categories($args);
		}

		return $all_categories;
	}
	function get_product_attributes($request) {
		global $woocommerce;
		$productAttributesList = [];
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$getProductAttributes = wc_get_attribute_taxonomies();
		if (!empty($getProductAttributes)) {
			$i = 0;
			foreach ($getProductAttributes as $key => $value) {
				$productAttributesList[$i]['id'] = $value->attribute_id;
				$productAttributesList[$i]['name'] = $value->attribute_label;
				$productAttributesList[$i]['slug'] = $value->attribute_name;
				$productAttributesList[$i]['type'] = $value->attribute_type;
				$productAttributesList[$i]['order_by'] = $value->attribute_orderby;
				$i++;
			}
		}
		return $productAttributesList;
	}
	function get_product_attributes_terms($request) {
		global $woocommerce;
		$productAttributesTermList = [];
		$store_id = $request['store_id'] ? $request['store_id'] : 1;
		$attributeName = $request['attribute_name'] ? $request['attribute_name'] : '';
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$taxonomyTerms = get_terms(wc_attribute_taxonomy_name($attributeName), 'orderby=name&hide_empty=0');
		if (!empty($taxonomyTerms)) {
			foreach ($taxonomyTerms as $key => $value) {
				$productAttributesTermList[$key]['id'] = $value->term_id;
				$productAttributesTermList[$key]['name'] = $value->name;
				$productAttributesTermList[$key]['slug'] = $value->slug;
				$productAttributesTermList[$key]['description'] = $value->description;
				$productAttributesTermList[$key]['menu_order'] = 0;
				$productAttributesTermList[$key]['count'] = $value->count;
			}
		}
		return $productAttributesTermList;
	}
	function create_product_attributes($request) {
		$colorIdArray = [];
		$attributes_option = $request['attributes_option'];
		$storeId = $attributes_option['store_id'] ? $attributes_option['store_id'] : '';
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$colorId = $attributes_option['color_id'] ? $attributes_option['color_id'] : '';
		$attributeName = $attributes_option['name'];
		$term = wc_get_attribute($colorId);
		$taxonomy = $term->slug;
		$slugName = preg_replace('/\s+/', '-', strtolower($attributeName));
		switch_to_blog($storeId);
		$insertTerm = wp_insert_term(
			$attributeName, // new term
			$taxonomy, // taxonomy
			array(
				'description' => '',
				'slug' => $slugName,
				'parent' => $colorId,
			)
		);
		if (!is_wp_error($insertTerm)) {
			$colorIdArray['id'] = $insertTerm['term_id'];
		} else {
			$colorIdArray['id'] = 0;
		}

		return $colorIdArray;

	}
	function get_order_details($request) {
		$orderOption = $request['order_option'];
		$store_id = $orderOption['store_id'] ? $orderOption['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$order_id = $orderOption['order_id'] ? $orderOption['order_id'] : '';
		$orderResponse = [];
		$billingAddress = [];
		$shippingAddress = [];
		if (!empty($store_id) && !empty($order_id)) {
			$order = wc_get_order($order_id);
			/** billing address  */
			$billingAddress['first_name'] = $order->get_billing_first_name();
			$billingAddress['last_name'] = $order->get_billing_last_name();
			$billingAddress['company'] = $order->get_billing_company();
			$billingAddress['address_1'] = $order->get_billing_address_1();
			$billingAddress['address_2'] = $order->get_billing_address_2();
			$billingAddress['city'] = $order->get_billing_city();
			$billingAddress['state'] = $order->get_billing_state();
			$billingAddress['postcode'] = $order->get_billing_postcode();
			$billingAddress['country'] = $order->get_billing_country();
			$billingAddress['email'] = $order->get_billing_email();
			$billingAddress['phone'] = $order->get_billing_phone();
			/**  */
			$shippingAddress['first_name'] = $order->get_shipping_first_name();
			$shippingAddress['last_name'] = $order->get_shipping_last_name();
			$shippingAddress['company'] = $order->get_shipping_company();
			$shippingAddress['address_1'] = $order->get_shipping_address_1();
			$shippingAddress['address_2'] = $order->get_shipping_address_2();
			$shippingAddress['city'] = $order->get_shipping_city();
			$shippingAddress['state'] = $order->get_shipping_state();
			$shippingAddress['postcode'] = $order->get_shipping_postcode();
			$shippingAddress['country'] = $order->get_shipping_country();
			if (is_multisite()) {
				$blogDetails = get_blog_details($store_id);
				$siteurl = $blogDetails->siteurl;
			} else {
				$siteurl = get_home_url();
			}
			$itemsArray = [];
			if (!empty($order->get_items())) {
				$i = 0;
				foreach ($order->get_items() as $item_id => $item) {
					$product = $item->get_product();
					$product_sku = null;
					if (is_object($product)) {
						$product_sku = $product->get_sku();
					}
					$product_id = $item->get_product_id();
					$variation_id = $item->get_variation_id();
					$variationId = isset($variation_id) && $variation_id > 0 ? $variation_id : $product_id;
					$itemsArray[$i]['id'] = $item->get_id();
					$itemsArray[$i]['product_id'] = $product_id;
					$itemsArray[$i]['variant_id'] = $variationId;
					$itemsArray[$i]['name'] = $item->get_name();
					$itemsArray[$i]['sku'] = $product_sku;
					$itemsArray[$i]['quantity'] = $item->get_quantity();
					$itemsArray[$i]['price'] = $product->get_price();
					$item_total = $order->get_item_meta($item_id, '_line_total', true);
					$itemsArray[$i]['total'] = $item_total;
					$meta_data = $item->get_meta_data();
					$formatted_meta = [];
					$productImageArray = [];
					$j = 0;
					$k = 0;
					foreach ($meta_data as $meta) {
						$name = str_replace("pa_", "", $meta->key);
						if ($name == 'custom_design_id') {
							$customDesignId = $meta->value;
							$formatted_meta[$j] = $customDesignId;
							$j++;
						}

					}
					$attributes = $product->get_attributes();
					if ($product->image_id != 0) {
						$imageSrc = wp_get_attachment_image_src($product->image_id, 'full');
						$imageSrcThumb = wp_get_attachment_image_src($product->image_id, 'thumbnail');
						$productImageArray[$k]['src'] = $imageSrc[0];
						$productImageArray[$k]['thumbnail'] = $imageSrcThumb[0];
						$k++;
					}
					if ($product_id != $variant_id) {
						$attachments = get_post_meta($variant_id, 'variation_image_gallery', true);
						$attachmentsExp = array_filter(explode(',', $attachments));
						foreach ($attachmentsExp as $id) {
							$imageSrc = wp_get_attachment_image_src($id, 'full');
							$imageSrcThumb = wp_get_attachment_image_src($id, 'thumbnail');
							$productImageArray[$k]['src'] = $imageSrc[0];
							$productImageArray[$k]['thumbnail'] = $imageSrcThumb[0];
							$k++;
						}
					} else {
						foreach ($product->gallery_image_ids as $id) {
							$imageSrc = wp_get_attachment_image_src($id, 'full');
							$imageSrcThumb = wp_get_attachment_image_src($id, 'thumbnail');
							$productImageArray[$k]['src'] = $imageSrc[0];
							$productImageArray[$k]['thumbnail'] = $imageSrcThumb[0];
							$k++;
						}
					}
					$itemsArray[$i]['custom_design_id'] = $formatted_meta[0];
					$itemsArray[$i]['images'] = $productImageArray;
					$i++;
				}

			}
			//getting fees
			foreach ($order->get_fees() as $fee_item_id => $fee_item) {
				$order_data['fee_lines'][] = array(
					'id' => $fee_item_id,
					'title' => $fee_item['name'],
					'tax_class' => (!empty($fee_item['tax_class'])) ? $fee_item['tax_class'] : null,
					'total' => wc_format_decimal($order->get_line_total($fee_item), $dp),
					'total_tax' => wc_format_decimal($order->get_line_tax($fee_item), $dp),
				);
			}

			$shippingCost = 0;
			//getting shipping
			foreach ($order->get_shipping_methods() as $shipping_item_id => $shipping_item) {
				$shippingCost = wc_format_decimal($shipping_item['cost'], $dp);
			}

			//getting taxes
			$totalTax = 0;
			foreach ($order->get_tax_totals() as $tax_code => $tax) {
				$totalTax = wc_format_decimal($tax->amount, $dp);
			}
			$orderResponse = [
				'id' => $order->get_id(),
				'order_number' => $order->get_id(),
				'customer_first_name' => $order->get_billing_first_name(),
				'customer_last_name' => $order->get_billing_last_name(),
				'customer_email' => $order->get_billing_email(),
				'customer_id' => $order->get_customer_id(),
				'created_date' => $order->get_date_created()->format('Y-m-d H:i:s'),
				'note' => $order->get_customer_order_notes(),
				'total_amount' => wc_format_decimal($order->get_total(), $dp),
				'total_tax' => $totalTax,
				'total_discounts' => wc_format_decimal($order->get_total_discount(), $dp),
				'total_shipping' => $shippingCost,
				'currency' => $order->get_currency(),
				'note' => $order->get_customer_note(),
				'status' => $order->get_status(),
				'total_orders' => wc_get_customer_order_count($order->get_customer_id()),
				'billing' => $billingAddress,
				'shipping' => $shippingAddress,
				'payment' => $order->get_payment_method_title(),
				'store_url' => $siteurl,
				'orders' => $itemsArray,

			];
		}

		return $orderResponse;
	}
	function get_order_item_details($request) {
		$orderItemResponse = [];
		$orderItems = [];
		$order_item_option = $request['order_item_option'];
		$store_id = $order_item_option['store_id'] ? $order_item_option['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$order_id = $order_item_option['order_id'] ? $order_item_option['order_id'] : '';
		if (!empty($store_id) && !empty($order_id)) {
			$order = wc_get_order($order_id);
			if (!empty($order->get_items())) {
				$i = 0;
				foreach ($order->get_items() as $item_id => $item) {
					$product = $item->get_product();
					$product_sku = null;
					if (is_object($product)) {
						$product_sku = $product->get_sku();
					}
					$product_id = $item->get_product_id();
					$variation_id = $item->get_variation_id();
					$variationId = isset($variation_id) && $variation_id > 0 ? $variation_id : $product_id;
					$id = isset($variation_id) && $variation_id > 0 ? $item->get_variation_id() : $item->get_product_id();
					$thumbnail = get_the_post_thumbnail_url($product_id);
					$orderItems[$i]['item_id'] = $item->get_id();
					$orderItems[$i]['product_id'] = $product_id;
					$orderItems[$i]['variant_id'] = $variationId;
					$orderItems[$i]['product_name'] = $item->get_name();
					$orderItems[$i]['product_sku'] = $product_sku;
					$orderItems[$i]['quantity'] = $item->get_quantity();
					$orderItems[$i]['price'] = $product->get_price();
					$orderItems[$i]['images'] = array(['src' => $thumbnail, 'thumbnail' => $thumbnail]);
					$item_total = $order->get_item_meta($item_id, '_line_total', true);
					$orderItems[$i]['total'] = $item_total;
					$meta_data = $item->get_meta_data();
					$product = wc_get_product($id);
					$attributes = $product->get_attributes();
					$attribute = [];
					if ($product_id != $variationId) {
						foreach ($attributes as $key => $value) {
							$key = urldecode($key);
							$attrTermDetails = get_term_by('slug', $value, $key);
							if (empty($attrTermDetails)) {
								$attrTermDetails = get_term_by('name', $value, $key);
							}
							$term = wc_attribute_taxonomy_id_by_name($key);
							$attrName = wc_attribute_label($key);
							$attrValId = $attrTermDetails->term_id;
							$attrValName = $attrTermDetails->name;
							$attribute[$attrName]['id'] = $attrValId;
							$attribute[$attrName]['name'] = $attrValName;
							$attribute[$attrName]['attribute_id'] = $term;
							$attribute[$attrName]['hex-code'] = '';
						}
					} else {
						foreach ($attributes as $attrKey => $attributelist) {
							if ($attrKey != 'pa_xe_is_designer' && $attrKey != 'pa_is_catalog') {
								foreach ($attributelist['options'] as $key => $value) {
									$term = wc_attribute_taxonomy_id_by_name($attributelist['name']);
									$attrName = wc_attribute_label($attributelist['name']);
									$attrValId = $value;
									$attrTermDetails = get_term_by('id', absint($value), $attributelist['name']);
									$attrValName = $attrTermDetails->name;
									$attribute[$attrName]['id'] = $attrValId;
									$attribute[$attrName]['name'] = $attrValName;
									$attribute[$attrName]['attribute_id'] = $term;
									$attribute[$attrName]['hex-code'] = '';
								}
							}
						}
					}
					$orderItems[$i]['attributes'] = $attribute;
					$formatted_meta = [];
					foreach ($meta_data as $meta) {
						$name = str_replace("pa_", "", $meta->key);
						if ($name == 'custom_design_id') {
							$name = 'ref_id';
						}
						$orderItems[$i][$name] = $meta->value;
					}
					$i++;
				}
				$orderItemResponse['order_id'] = $order_id;
				$orderItemResponse['order_incremental_id'] = $order_id;
				$orderItemResponse['customer_id'] = $order->get_customer_id();
				$orderItemResponse['store_id'] = $store_id;
				$orderItemResponse['order_items'] = $orderItems;
			}
		}
		return $orderItemResponse;
	}
	public function all_blogs_list() {
		$blogs_list_array = [];
		if (is_multisite()) {
			$all_blog = wp_get_sites();
			if (!empty($all_blog)) {
				foreach ($all_blog as $key => $value) {
					$blogs_list_array[$key]['store_id'] = $value['blog_id'];
					$blogs_list_array[$key]['store_url'] = $value['domain'];
					$blogs_list_array[$key]['is_active'] = $value['public'];
				}
			}
		}
		return $blogs_list_array;
	}
	function registration_save($user_id) {
		if (!empty($user_id)) {
			update_user_meta($user_id, '_order_count', 0);
		}
	}
} // end class

$inkxe_productdesigner_class = new InkXEProductDesigner();