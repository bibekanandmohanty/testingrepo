<?php
/**
 * Manage Woocommerce Store Products
 *
 * PHP version 5.6
 *
 * @category  Store_Product
 * @package   Store
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace ProductStoreSpace\Controllers;

use CommonStoreSpace\Controllers\StoreController;

/**
 * Store product Controller
 *
 * @category                Store_Product
 * @package                 Store
 * @author                  Tanmaya Patra <tanmayap@riaxe.com>
 * @license                 http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link                    http://inkxe-v10.inkxe.io/xetool/admin
 * @SuppressWarnings(PHPMD)
 */
class StoreProductsController extends StoreController {
	/**
	 * Instantiate Constructer
	 */
	public function __construct() {
		parent::__construct();
		$this->includeWordPressCoreFile();
	}
	/**
	 * Get: Get the list of product or a Single product from the WooCommerce API
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Array of list/one product(s)
	 */
	public function getProducts($request, $response, $args) {
		$storeResponse = $singleProductDetails = $categories = [];
		if (!empty($request->getQueryParam('store_id'))) {
			$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		} else {
			$storeId = $args['store_id'] ? $args['store_id'] : 1;
		}
		if (isset($args['id']) && $args['id'] > 0) {
			$type = 0;
			$productId = $args['id'];
			if (isset($args['product_id']) && $args['product_id'] != $args['id']) {
				$type = 1;
				$productId = $args['product_id'];
				$variantId = $args['id'];
			}
			try {
				if (is_multisite()) {
					switch_to_blog($storeId);
				}
				$product = wc_get_product($productId);
				$variationIds = $available_variations = $product->get_children();
				$categoryIds = $product->get_category_ids();
				if (!empty($categoryIds)) {
					$i = 0;
					foreach ($categoryIds as $categoryId) {
						$productCat = get_term_by('id', $categoryId, 'product_cat');
						$categories[$i] = [
							'id' => $productCat->term_id,
							'name' => $productCat->name,
							'slug' => $productCat->slug,
							'parent_id' => $productCat->parent,

						];
						$i++;
					}
				}
				$productType = $product->get_type();
				$variantId = $productType == 'variable'
				? $variationIds[0] : $args['id'];
				// Collecting Images into the Product Array
				$productImages = object_to_array(
					$this->plugin->get(
						'product/images',
						[
							'product_id' => $productId,
							'variant_id' => $variantId,
							'store_id' => $storeId,
						]
					)
				);

				$productType = $product->get_type();
				$attributes = $this->plugin->get(
					'product/attributes',
					[
						'product_id' => $productId,
						'store_id' => $storeId,
					]
				);
				$price = 0;
				if (!empty($product->get_sale_price())) {
					$price = $product->get_sale_price();
				} elseif (!empty($product->get_price())) {
					$price = $product->get_price();
				}
				$sanitizedProduct = [
					'id' => $product->get_id(),
					'name' => $product->get_name(),
					'sku' => $product->get_sku(),
					'type' => $product->get_type(),
					'variant_id' => $variantId,
					'description' => preg_replace(
						"/\r|\n/",
						"",
						$product->get_description()
					),
					'price' => $price,
					'stock_quantity' => $product->get_stock_quantity(),
					'images' => $productImages['images'],
					'categories' => $categories,
					'attributes' => $attributes,
				];
				$storeResponse = [
					'total_records' => 1,
					'products' => $sanitizedProduct,
				];
			} catch (\Exception $e) {
				$storeResponse[] = [];
				// Store exception in logs
				create_log(
					'store', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Get product',
						],
					]
				);
			}
		} else {
			// Get all requested Query params
			$filters = [
				'search' => $request->getQueryParam('name'),
				'category' => $request->getQueryParam('category'),
				'range' => $request->getQueryParam('per_page'),
				'page' => $request->getQueryParam('page'),
				'order' => !empty($request->getQueryParam('order'))
				? $request->getQueryParam('order') : 'desc',
				'order_by' => !empty($request->getQueryParam('orderby'))
				? $request->getQueryParam('orderby') : 'post_date',
				'is_customize' => (!empty($request->getQueryParam('is_customize'))
					&& $request->getQueryParam('is_customize') > 0) ? 1 : 0,
				'is_catalog' => (!empty($request->getQueryParam('is_catalog'))
					&& $request->getQueryParam('is_catalog') > 0) ? 1 : 0,
				'fetch' => $request->getQueryParam('fetch') ? $request->getQueryParam('fetch') : '',
				'store_id' => $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1,
			];
			// If any outer methods sends category(ies) then this code will run
			if (!empty($args['categories']) && $args['categories'] != "") {
				$filters['category'] = trim($args['categories']);
			}
			if (!empty($args['is_customize']) && $args['is_customize'] != "") {
				$filters['is_customize'] = trim($args['is_customize']);
			}
			$options = [];
			foreach ($filters as $filterKey => $filterValue) {
				if (isset($filterValue) && $filterValue != "") {
					$options += [$filterKey => $filterValue];
				}
			}
			/**
			 * Fetch All Products
			 */
			// Calling to Custom API for getting Product List
			try {
				$getStoreAllProducts = $this->plugin->get('products', $options);
				$getProducts = object_to_array($getStoreAllProducts);
				$productList = [];
				if (isset($getProducts) && count($getProducts['data']) > 0) {
					$storeResponse = [
						'total_records' => $getProducts['records'],
						'products' => $getProducts['data'],
					];
				}
			} catch (\Exception $e) {
				$storeResponse = [];
				// Store exception in logs
				create_log(
					'store', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Get all products',
						],
					]
				);
			}
		}

		return $storeResponse;
	}

	/**
	 * Get: Get minimal product details from the store end
	 * it gives: images, name, price, attributes
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   27 feb 2019
	 * @return Array of list/one product(s)
	 */
	public function getProductShortDetails($request, $response, $args) {
		$storeResponse = [];
		$productId = to_int($args['product_id']);
		$variantId = to_int($args['variant_id']);
		$responseType = to_int($args['details']);
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		if (is_multisite()) {
			switch_to_blog($storeId);
		}
		if ($productId > 0 && $variantId > 0) {
			$pluginOptions = [
				'product_id' => $productId,
				'variant_id' => $variantId,
				'details' => $responseType,
				'store_id' => $storeId,
			];
			try {
				$categoriesResponse = wp_get_post_terms($productId, 'product_cat', array('fields' => 'ids'));
				$categories = [];
				if (!empty($categoriesResponse)) {
					foreach ($categoriesResponse as $key => $value) {
						$categories[$key]['id'] = $value;
					}
				}
				$getLimitedDetails = $this->plugin->get('product/images', $pluginOptions);
				$getProductDetails = object_to_array($getLimitedDetails);
				$getProductDetails['categories'] = $categories;
				if (!empty($getProductDetails)) {
					$storeResponse = $getProductDetails;
				}
			} catch (\Exception $e) {
				create_log(
					'store', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Get product limited details',
						],
					]
				);
			}
		}

		return $storeResponse;
	}

	/**
	 * GET: Get list of category/subcategory or a Single category/subcategory
	 * from the WooCommerce API
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Array of list/one product(s)
	 */
	public function getCategories($request, $response, $args) {
		$categories = [];
		$storeResponse = [];
		$name = $request->getQueryParam('name') ? $request->getQueryParam('name') : '';
		$store_id = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$productId = 0;
		// Get all requested Query params
		$name = $request->getQueryParam('name');
		if (!empty($args['id'])) {
			$productId = $args['id'];
		}
		$options = [
			'name' => $name,
			'store_id' => $store_id,
			'productId' => $productId,
		];
		// End of the filter
		try {
			$getCategories = $this->plugin->get('products_categories', ['categories_option' => $options]);

			/**
			 * For single category listing, we get a 1D category array. But the
			 * for-loop works for Multi-Dimentional Array. So to push the single
			 * category array into the for-loop I converted the 1D array to Multi
			 * dimentional array, so that foreach loop will be intact
			 */
			if (empty($getCategories[0])) {
				$getCategories = [$getCategories];
			}
			if (!empty($getCategories[0])) {
				foreach ($getCategories as $key => $category) {
					$categories[$key] = [
						'id' => $category['term_id'],
						'name' => htmlspecialchars_decode($category['name'], ENT_NOQUOTES),
						'slug' => $category['slug'],
						'parent_id' => $category['parent'],
					];
				}
			}
			if (is_array($categories) && !empty($categories)) {
				$storeResponse = $categories;
			}
		} catch (\Exception $e) {
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Get categories details',
					],
				]
			);
		}

		return $storeResponse;
	}

	/**
	 * Generate thumb images from store product images by using store end image urls
	 *
	 * @param $imagePath  Image Path
	 * @param $resolution Image Resolution
	 *
	 * @author    tanmayap@riaxe.com
	 * @date      24 sep 2019
	 * @parameter Slim default params
	 * @return    Array of list/one product(s)
	 */
	public function getVariableImageSizes($imagePath, $resolution) {
		// Only available 100, 150, 300, 450 and 768 resolution image sizes
		$imageResolution = 300;
		if (isset($resolution)
			&& ($resolution == 100 || $resolution == 150
				|| $resolution == 300 || $resolution == 450
				|| $resolution == 768)
		) {
			$imageResolution = $resolution;
		}
		$explodeImage = explode('/', $imagePath);
		$getImageFromUrl = end($explodeImage);
		$fileExtension = pathinfo($getImageFromUrl, PATHINFO_EXTENSION);
		$fileName = pathinfo($getImageFromUrl, PATHINFO_FILENAME);
		$updatedImageName = $fileName . '-' . $imageResolution
			. 'x' . $imageResolution . '.' . $fileExtension;
		$updatedImagePath = str_replace(
			$getImageFromUrl, $updatedImageName, $imagePath
		);
		return $updatedImagePath;
	}

	/**
	 * GET: Product Attribute Pricing  Details by Product Id
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Arguments
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Oct 2019
	 * @return All store attributes
	 */
	public function storeProductAttrPrc($request, $response, $args) {
		$storeResponse = [];
		$productId = to_int($args['id']);
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$filters = [
			'product_id' => $productId,
			'store_id' => $storeId,
		];
		$options = [];
		foreach ($filters as $filterKey => $filterValue) {
			if (isset($filterValue) && $filterValue != "") {
				$options += [$filterKey => $filterValue];
			}
		}
		try {
			$singleProductDetails = $this->plugin->get(
				'product/attributes', $options
			);
			$storeResponse = $singleProductDetails;
		} catch (\Exception $e) {
			$storeResponse = [];
		}

		return $storeResponse;
	}

	/**
	 * Find Combinations for the existing Attributes like Woocommerce style
	 *
	 * @param $arrays Multidimentional Arrays
	 * @param $i      Key
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Comination Arrays
	 */
	public function combinations($arrays, $i = 0) {
		if (!isset($arrays[$i])) {
			return array();
		}
		if ($i == count($arrays) - 1) {
			return $arrays[$i];
		}
		// Get combinations from subsequent arrays
		$tmp = $this->combinations($arrays, $i + 1);
		$result = array();
		// Concat each array from tmp with each element from $arrays[$i]
		foreach ($arrays[$i] as $v) {
			foreach ($tmp as $t) {
				$result[] = is_array($t) ?
				array_merge(array($v), $t) :
				array($v, $t);
			}
		}
		return $result;
	}

	/**
	 * Post: Save predecorated products into the store
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Array records and server status
	 */
	public function saveProduct($request, $response) {
		require_once $this->storePath['abspath'] . "wp-blog-header.php";
		$storeResponse = [];
		$createVariation = true;
		$isUploadedImg = false;
		$getPostData = (isset($saveType) && $saveType == 'update')
		? $this->parsePut() : $request->getParsedBody();

		if (!empty($getPostData['data'])) {
			$predecorData = json_clean_decode($getPostData['data'], true);

			$productSaveEndPoint = 'products';
			$mode = 'saved';
			$getproductData = [];
			if (isset($predecorData['product_id'])
				&& $predecorData['product_id'] > 0
			) {
				$productSaveEndPoint = 'products/' . $predecorData['product_id'];
				$mode = 'updated';
				$getproductData = $this->wc->get(
					'products/' . $predecorData['product_id']
				);
			}

			$productType = 'simple';
			if (!empty($predecorData['type'])) {
				$productType = $predecorData['type'];
			}
			if ($predecorData['ref_id'] == 0) {
				$isUploadedImg = true;
			}

			// Append Attributes by Looping through each Attribute
			$productAttributes = $getAttributeCombinations = [];
			foreach ($predecorData['attributes'] as $prodAttributekey => $prodAttribute) {
				$getAttrTermsList = [];
				$taxonomy = wc_get_attribute($prodAttribute['attribute_id']);
				if (isset($getproductData) && !empty($getproductData)) {
					$attrKey = array_search(
						$prodAttribute['attribute_id'],
						array_column($getproductData['attributes'], 'id')
					);
					if (!is_bool($attrKey)) {
						foreach ($getproductData['attributes'][$attrKey]['options'] as $option) {
							$term = get_term_by('name', $option, $taxonomy->slug);
							$getAttrTermsList[] = $term->name;
						}
					}
				}
				// Append Attribute Term slugs
				if (!empty($prodAttribute['attribute_options'])) {
					foreach ($prodAttribute['attribute_options'] as $attrTermkey => $attrTerm) {
						if ($productType == 'simple') {
							$getAttrTermsList = [];
						}
						// Get Product Assoc. Terms from API
						$term = get_term_by('id', $attrTerm, $taxonomy->slug);
						$getAttrTermsList[] = $term->name;
						$getAttributeCombinations[$prodAttributekey][]
						= $prodAttributekey . '___'
						. $term->name;
					}
				}
				$productAttributes['attributes'][] = [
					'id' => $prodAttribute['attribute_id'],
					'variation' => true,
					'visible' => true,
					'options' => array_unique($getAttrTermsList),
				];
			}
			if ($mode == 'saved') {
				// Setup a array of Basic Product attributes
				$productSaveData = [
					'name' => $predecorData['name'],
					'sku' => $predecorData['sku'],
					'type' => strtolower($productType),
					'description' => !empty($predecorData['description'])
					? $predecorData['description'] : null,
					'short_description' => !empty($predecorData['short_description'])
					? $predecorData['short_description'] : null,
					'manage_stock' => true,
					'stock_quantity' => $predecorData['quantity'],
					'in_stock' => true,
					'visible' => true,
					'catalog_visibility' => "visible",
				];

				// Append category IDs product_image_filesproduct_image_files
				// product_image_files product_image_files
				$categories = [];
				if (!empty($predecorData['categories'])) {
					foreach ($predecorData['categories'] as $category) {
						$categories['categories'][] = [
							'id' => $category['category_id'],
						];
					}
					$productSaveData += $categories;
				}

				// End
				// Append Image Urls
				$productImages = [];
				$convertImageToSize = 500; // w*h pixel
				// If Images url are sent via json array Check for is_array()
				// because if they send images_url as string then it will not
				// satisfy. So if I check is_array() then if they send array or
				// string, both cond. will be stisfied
				if (is_array($predecorData['product_image_url'])
					&& !empty($predecorData['product_image_url'])
				) {
					$fileSavePath = path('abs', 'temp');
					$fileFetchPath = path('read', 'temp');

					foreach ($predecorData['product_image_url'] as $imageUrl) {
						// If product template assign to product then skip the uploading varitaion images.
						if (strpos($imageUrl, DESIGN_PREVIEW_FOLDER)) {
							$isUploadedImg = true;
						}

						// End
						$randomName = getRandom();
						$tempFileName = 'products_' . $randomName;
						$fileExtension = pathinfo($imageUrl, PATHINFO_EXTENSION);
						$filenameToProcess = $tempFileName . '.' . $fileExtension;
						// Downlaod the image so that we can change the dimension of
						// the received image file
						download_file($imageUrl, $fileSavePath, $filenameToProcess);
						$fileUrlToProcess = $fileFetchPath . $filenameToProcess;
						$imageManager = new \Intervention\Image\ImageManagerStatic();
						$img = $imageManager->make($fileUrlToProcess);
						$img->resize(
							$convertImageToSize, null, function ($constraint) {
								$constraint->aspectRatio();
							}
						);
						$img->save($fileSavePath . 'resize_' . $filenameToProcess);
						$productImages['images'][] = [
							'src' => $fileFetchPath . 'resize_' . $filenameToProcess,
						];
					}
					// foreach ($predecorData['product_image_url'] as $imageUrl) {
					//     $productImages['images'][] = [
					//         'src' => $imageUrl,
					//     ];
					// }
				} else {
					// If Images are sent from front-end
					$uploadedFileNameList = do_upload(
						'product_image_files', path('abs', 'product'), [], 'array'
					);
					foreach ($uploadedFileNameList as $uploadedImage) {
						$productImages['images'][] = [
							'src' => path('read', 'product') . $uploadedImage,
						];
					}
				}
				$productSaveData += $productImages;
				// End
				// Append Ref_ID to the Custom meta data. This data will be saved
				// into post_meta table in wooCommerce
				$metaData = [
					'meta_data' => [
						[
							'key' => 'custom_design_id',
							'value' => $predecorData['ref_id'],
						],
						[
							'key' => 'is_decorated_product',
							'value' => 1,
						],
					],
				];
				$productSaveData += $metaData;
				$variationAttributes = $productAttributes['attributes'];
				// Enable Customize button
				if ($predecorData['is_redesign']) {
					$designAttrId = $this->getAttributeIdByName('xe_is_designer');
					$isCustomize = [
						'id' => $designAttrId,
						'variation' => false,
						'visible' => false,
						'options' => ['1'],
					];
					if (empty($productAttributes['attributes'])) {
						$productAttributes['attributes'] = array($isCustomize);
					} else {
						array_push($productAttributes['attributes'], $isCustomize);
					}
				}
				$productSaveData += $productAttributes;

				if ($productType == 'simple') {
					$productSaveData += [
						'regular_price' => strval($predecorData['price']),
					];
				}
			} else {
				$productSaveData = $productAttributes;
			}
			// Process the Data to the Product's Post API
			try {
				$getProducts = $this->wc->post(
					$productSaveEndPoint, $productSaveData
				);
				// Call Another API for Create Variations
				if (!empty($getProducts['id'])) {
					$storeResponse = [
						'product_id' => $getProducts['id'],
					];
					// Create Predeco if the option is set to true
					if ($createVariation === true && $productType == 'variable') {
						$variationCreateData = [
							'product_id' => $getProducts['id'],
							'price' => $predecorData['price'],
							// Auto change SKU
							'sku' => $predecorData['sku'] . time(),
							'attributes' => $variationAttributes,
							'product_data' => $getproductData,
							'parent_product_id' => $predecorData['parent_product_id'],
						];
						$variationResponse = $this->createProductVariations(
							$request, $response, $variationCreateData, $isUploadedImg
						);
						//return $variationResponse;
						if (!empty($variationResponse)) {
							$storeResponse += [
								'variation_id' => $variationResponse['variation_id'],
							];
						}
					}
				}
				// End of Variation creation
			} catch (\Exception $e) {
				// Store exception in logs
				create_log(
					'store', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Create Predeco',
						],
					]
				);
			}
		}

		return $storeResponse;
	}

	/**
	 * Post: Create product variations and save to corsp. Product
	 *
	 * @param $request       Slim's Request object
	 * @param $response      Slim's Response object
	 * @param $variationData Variation Data
	 *
	 * @author tanmayap@riaxe.com
	 * @date   17 Mar 2019
	 * @return Array records and server status
	 */
	public function createProductVariations($request, $response, $variationData = [], $isUploadedImg) {
		$storeResponse = [];
		$getPostData = (isset($saveType) && $saveType == 'update')
		? $this->parsePut() : $request->getParsedBody();
		$predecorData = json_clean_decode($getPostData['data'], true);
		if (!empty($variationData) && count($variationData) > 0) {
			$predecorData = $variationData;
		}
		$oldProductId = $predecorData['parent_product_id'];
		$oldProductData = $this->wc->get('products/' . $oldProductId . '/variations');
		try {
			// Get products details from product Store by Product ID
			if (isset($variationData['product_data']) && !empty($variationData['product_data'])) {
				$getproductData = $variationData['product_data'];
			} else {
				$getproductData = $this->wc->get(
					'products/' . $predecorData['product_id']
				);
			}
			$variationsForProductId = $getproductData['id'];
			$variationsForProductImage = $getproductData['images'][0]['id'];
			/**
			 * Start process for creating Variation Array List
			 */
			$attributes = $predecorData['attributes'];
			// Modify the get Attribute array as per our requirements
			$attributesList = [];
			for ($loop = 0; $loop < count($attributes); $loop++) {
				$attributeId = $attributes[$loop]['id'];
				// $options = [];
				foreach ($attributes[$loop]['options'] as $attributeOptionVal) {
					$attributesList[$loop][] = $attributeId
						. '___' . $attributeOptionVal;
				}
			}
			$getCombinations = $this->combinations($attributesList);
			$oldCombination = [];
			foreach ($oldProductData as $key => $variants) {
				foreach ($variants['attributes'] as $attribute) {
					$oldCombination[$key]['attributes'][] = $attribute['id']
						. '___' . $attribute['option'];

				}
				$oldCombination[$key]['variant_id'] = $variants['id'];
			}
			$variantsPerCombination = [];
			$i = 0;
			foreach ($getCombinations as $eachComination) {
				// Below Comments can be used in future
				$variantsPerCombination[$i]['regular_price'] = strval($predecorData['price']);
				$variantsPerCombination[$i]['sku'] = strtoupper($getproductData['slug'] . time() . $i);

				// Append Image if Exists
				foreach ($oldCombination as $options) {
					if (empty(array_diff($eachComination, $options['attributes']))) {
						$oldVariantId = $options['variant_id'];
					}

				}
				$varKey = array_search(
					$oldVariantId,
					array_column($oldProductData, 'id')
				);
				if (!$isUploadedImg) {
					if (!is_bool($varKey)) {
						$variantsPerCombination[$i]['image']['id'] = $oldProductData[$varKey]['image']['id'];
					}
				}
				/*if (!empty($variationsForProductImage)) {
					                    $variantsPerCombination[$i]['image']['id'] = $variationsForProductImage;
				*/
				$j = 0;
				foreach ($eachComination as $eachCombinationOption) {
					$splitEachCombinationOption = explode(
						'___', $eachCombinationOption
					);
					// Get Attribute Option Slug from Store API
					$variantsPerCombination[$i]['attributes'][$j]['id'] = $splitEachCombinationOption[0];
					$variantsPerCombination[$i]['attributes'][$j]['option'] = $splitEachCombinationOption[1];
					$j++;
				}
				if (!$isUploadedImg) {
					$attachments = get_post_meta($oldVariantId, '_product_image_gallery', true);
					$attachmentsExp = array_filter(explode(',', $attachments));
					$attach_id = array();
					foreach ($attachmentsExp as $id) {
						$imageSrc = wp_get_attachment_image_src($id, 'full');
						$image = $imageSrc[0];
						$finfo = getimagesize($image);
						$type = $finfo['mime'];
						$filename = basename($image);
						$dirPath = explode("/uploads", $image);
						$subDir = explode($filename, $dirPath[1]);
						$attachment = array(
							'guid' => $image,
							'post_mime_type' => $type,
							'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
							'post_content' => '',
							'post_status' => 'inherit',
						);
						$attach_id[] = wp_insert_attachment($attachment, $subDir[0] . basename($filename), $variationsForProductId);
						//$image_array[] = $imageSrc[0];
					}
					if (!empty($attach_id)) {
						$var_image = implode(",", $attach_id);
					}
					$variantsPerCombination[$i]['meta_data'][0]['key'] = '_product_image_gallery';
					$variantsPerCombination[$i]['meta_data'][0]['value'] = $var_image;
				}
				$i++;
			}
			$variantsAvailable['create'] = $variantsPerCombination;
			try {
				$variationResponse = $this->wc->post(
					'products/' . $variationsForProductId . '/variations/batch', $variantsAvailable
				);
				if (!empty($variationResponse)) {
					$storeResponse = [
						'product_id' => $variationsForProductId,
						'variation_id' => $variationResponse['create'][0]['id'],
					];
				}
			} catch (\Exception $e) {
				$storeResponse = [];
				// Store exception in logs
				create_log(
					'store', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Create Variations',
						],
					]
				);
			}
		} catch (\Exception $e) {
			$storeResponse = [];
			// Store exception in logs
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Fetch Variations',
					],
				]
			);
		}

		return $storeResponse;
	}

	/**
	 * Post: Validate SKU or Name at Store end
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Validate response Array
	 */
	public function validateStoreSkuName($request, $response) {
		$storeResponse = 0;
		$rootEndpoint = 'products';
		$allPostPutVars = (isset($saveType) && $saveType == 'update')
		? $this->parsePut() : $request->getParsedBody();
		$filters = [];
		if (!empty($allPostPutVars['name'])) {
			$filters += [
				'search' => $allPostPutVars['name'],
			];
		}
		if (!empty($allPostPutVars['sku'])) {
			$filters += [
				'sku' => $allPostPutVars['sku'],
			];
		}
		try {
			$getProducts = $this->wc->get($rootEndpoint, $filters);
			if (!empty($getProducts[0]['id'])) {
				$storeResponse = $getProducts[0]['id'];
			}
		} catch (\Exception $e) {
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Fetch Variations',
					],
				]
			);
		}

		return $storeResponse;
	}

	/**
	 * Get: Get all Attributes List from Store-end
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   19 Mar 2019
	 * @return Array list of Attributes
	 */
	public function storeAttributeList($request, $response) {
		$storeResponse = [];
		$endPoint = 'attributes';

		$productId = $request->getQueryParam('product_id');
		if (!empty($productId)) {
			$attributeList = [];
			$getProductDetail = $this->getProducts($request, $response, ['id' => $productId]);
			if (!empty($getProductDetail['products'])) {
				$productAttributes = $getProductDetail['products']['attributes'];
				foreach ($productAttributes as $attribute) {
					if ($attribute['name'] != 'xe_is_designer') {
						$attributeList[] = [
							'id' => $attribute['id'],
							'name' => $attribute['name'],
							'terms' => $attribute['options'],
						];
					}
				}

				$storeResponse = $attributeList;
			}
		} else {
			try {
				$getAllAttributes = $this->plugin->get($endPoint);
				if (!empty($getAllAttributes)) {
					$storeResponse = $getAllAttributes;
				}
			} catch (\Exception $e) {
				$storeResponse = [];
				create_log(
					'store', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Create Variations',
						],
					]
				);
			}
		}

		return $storeResponse;
	}

	/**
	 * Get list of Color Variants from the WooCommerce API as per the product
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Arguments
	 *
	 * @author tanmayap@riaxe.com
	 * @date   5 Oct 2019
	 * @return Json
	 */
	public function colorsByProduct($request, $response, $args) {
		$storeResponse = [];
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$filters = [
			'product_id' => $args['product_id'],
			'attribute' => 'pa_' . $args['slug'],
			'store_id' => $storeId,
		];
		$options = [];
		$variantData = [];
		foreach ($filters as $filterKey => $filterValue) {
			if (isset($filterValue) && $filterValue != "") {
				$options += [$filterKey => $filterValue];
			}
		}
		try {
			$singleProductDetails = $this->plugin->get('options', $options);
			if (!empty($singleProductDetails)) {
				$storeResponse = $singleProductDetails;
			}
		} catch (\Exception $e) {
			$storeResponse = [];
			// Store exception in logs
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Create Predeco',
					],
				]
			);
		}

		return $storeResponse;
	}

	/**
	 * Get: Get all Attributes List from Store-end
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   31 Jan 2019
	 * @return Array list of Attributes
	 */
	public function getOnlyAttribute($request, $response) {
		$storeResponse = [];
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$endPoint = 'products/attributes';
		$allowedTypes = ['select', 'checkbox'];
		$attributeList = [];
		try {
			$getAllAttributes = $this->plugin->get($endPoint, ['store_id' => $storeId]);
			if (!empty($getAllAttributes)) {
				$loop = 0;
				foreach ($getAllAttributes as $attribute) {
					if ($attribute['name'] != "xe_is_designer") {
						if (in_array($attribute['type'], $allowedTypes)) {
							$attributeList[$loop] = [
								'id' => $attribute['id'],
								'name' => $attribute['name'],
							];
							$loop++;
						}
					}
				}
				$storeResponse = $attributeList;
			}
		} catch (\Exception $e) {
			// Store exception in logs
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Get specific attribute list',
					],
				]
			);
		}

		return $storeResponse;
	}

	/**
	 * Get: Get all Attributes Terms from Store-end as per Attribute
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   31 Jan 2019
	 * @return Array list of Attributes
	 */
	public function getAttributeTerms($request, $response, $args) {
		$storeResponse = [];
		$attributeId = to_int($args['id']);
		$endPoint = 'products/attributes/' . $attributeId . '/terms';
		$attributeList = [];
		try {
			$getAllAttributes = $this->wc->get($endPoint);
			if (!empty($getAllAttributes)) {
				foreach ($getAllAttributes as $loop => $attribute) {
					$attributeList[$loop] = [
						'id' => $attribute['id'],
						'name' => $attribute['name'],
					];
				}
				$storeResponse = $attributeList;
			}
		} catch (\Exception $e) {
			// Store exception in logs
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Get single attribute details',
					],
				]
			);
		}

		return $storeResponse;
	}

	/**
	 * Get: Get the list of product or a Single product from the WooCommerce API
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4 Feb 2019
	 * @return Array of products list
	 */
	public function getToolProducts($request, $response) {
		$storeResponse = [];
		try {
			$getProducts = object_to_array($this->plugin->get('categories/products'));
			$storeResponse = $getProducts;
		} catch (\Exception $e) {
			// Store exception in logs
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Get single attribute details',
					],
				]
			);
		}

		return $storeResponse;
	}
	/**
	 * Get total product count from the WooCommerce API
	 *
	 * @author debashrib@riaxe.com
	 * @date   06 Feb 2020
	 * @return count
	 */
	public function totalProductCount($store_id) {
		$totalCount = 0;
		try {
			$getCountDetails = $this->plugin->get('product/count', ['store_id' => $store_id]);
			$totalCountDetails = $getCountDetails;
		} catch (\Exception $e) {
			// Store exception in logs
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Get product count',
					],
				]
			);
		}

		return $totalCountDetails;
	}

	/**
	 * Get: Get variation's attribute details by variant ID
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument
	 *
	 * @author tanmayap@riaxe.com
	 * @date   13 Feb 2020
	 * @return Array records and server status
	 */
	public function storeVariantAttributeDetails($request, $response, $args) {
		$storeResponse = [];
		$filteredAttributes = [];
		$productId = to_int($args['pid']);
		$variationId = to_int($args['vid']);
		$option['per_page'] = 100;
		try {
			$getSelVarDetails = $this->wc->get(
				'products/' . $productId . '/' . 'variations', $option
			);
			$varKey = array_search(
				$variationId,
				array_column($getSelVarDetails, 'id')
			);
			$attrKey = array_search(
				$args['color_name'],
				array_column($getSelVarDetails[$varKey]['attributes'], 'name')
			);
			if (is_bool($attrKey)) {
				$primaryColorName = $getSelVarDetails[$varKey]['attributes'][0]['option'];
			} else {
				$primaryColorName = $getSelVarDetails[$varKey]['attributes'][$attrKey]['option'];
			}
			foreach ($getSelVarDetails as $variations) {
				$colorExist = array_search(
					$primaryColorName,
					array_column($variations['attributes'], 'option')
				);
				if ($variations['attributes'][$colorExist]['option'] == $primaryColorName) {
					$processedAttrList = [];
					foreach ($variations['attributes'] as $attributes) {
						$processedAttrList += [
							$attributes['name'] . '_id' => $attributes['id'],
							$attributes['name'] => $attributes['option'],
						];
					}
					$stockQty = 0;
					$minStockQty = 1;
					$maxStockQty = 1000;
					if (!empty($variations['stock_quantity']) && $variations['stock_quantity'] > 0) {
						$stockQty = $variations['stock_quantity'];
						$minStockQty = 1;
						$maxStockQty = $variations['stock_quantity'];
					}
					$filteredAttributes[] = [
						'variant_id' => $variations['id'],
						'inventory' => [
							'stock' => $stockQty,
							'min_quantity' => $minStockQty,
							'max_quantity' => $maxStockQty,
							'quantity_increments' => 1,
						],
						'price' => to_decimal($variations['price']),
						'tier_prices' => [],
						'attributes' => $processedAttrList,
					];
				}
			}
			if (!empty($filteredAttributes)) {
				$storeResponse = $filteredAttributes;
			}
		} catch (\Exception $e) {
			// Store exception in logs
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Store variant attribute details with quantity',
					],
				]
			);
		}

		return $storeResponse;
	}

	/**
	 * Get: Get Attribute List for Variants with Multiple attribute
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument
	 *
	 * @author malay@riaxe.com
	 * @date   10th April 2020
	 * @return Array records and server status
	 */
	public function storeMultiAttributeVariantDetails($request, $response, $args) {
		$storeResponse = [];
		$filteredAttributes = [];
		$productId = to_int($args['pid']);
		$variationId = to_int($args['vid']);
		$attribute = $argOptName = $args['attribute'];
		$details = $args['price'];
		$option['per_page'] = 100;
		$getStoreDetails = get_store_details($request);
		$store_id = $getStoreDetails['store_id'] ? $getStoreDetails['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$attributeName = $this->getAttributeName();
		// Fetch attribues orders from store end
		global $wpdb;
		$this->$attrValuesWithOrders = [];
		$tableAttrTaxonomy = $wpdb->prefix . "woocommerce_attribute_taxonomies";
		$sizeAttrSlug = $wpdb->get_var("SELECT attribute_name FROM $tableAttrTaxonomy WHERE attribute_label = '$attribute'");
		$attrTexonomy = "pa_" . $sizeAttrSlug;
		$attrValues = get_terms($attrTexonomy, 'hide_empty = 0');
		foreach ($attrValues as $key => $value) {
			$this->$attrValuesWithOrders[$value->name] = $key;
		}
		//End
		try {
			/*
				$getSelVarDetails = $this->wc->get(
					'products/' . $productId . '/' . 'variations', $option
				);
			*/
			$getSelVarDetails = $this->getProductVariantsData($productId, $store_id);
			$varKey = array_search(
				$variationId,
				array_column($getSelVarDetails, 'id')
			);
			// For Tier Price
			$metaDataContent = get_post_meta($productId, 'imprintnext_tier_content');
			$tierPriceData = array();
			$commonTierPrice = array();
			$variantTierPrice = array();
			$sameforAllVariants = $isTier = false;
			// print_r($metaDataContent);exit;
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
			$attrKey = array_search(
				$attribute,
				array_column($getSelVarDetails[$varKey]['attributes'], 'name')
			);
			$length = count($getSelVarDetails[$varKey]['attributes']) - 1;
			$attributes = [];
			$finalArray = [];
			$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['id'] = $getSelVarDetails[$varKey]['attributes'][$attrKey]['id'];
			$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['name'] = $getSelVarDetails[$varKey]['attributes'][$attrKey]['option'];
			$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['variant_id'] = $variationId;
			if ($length == $attrKey || $details == 1) {
				// Set Unlimited stock quantity
				$manageStock = get_post_meta($variationId, '_manage_stock', true);
				$stockStatus = get_post_meta($variationId, '_stock_status', true);
				if ($manageStock == "no" && $stockStatus == "instock") {
					$manageStock = get_post_meta($productId, '_manage_stock', true);
					$stockStatus = get_post_meta($productId, '_stock_status', true);
					if ($manageStock == "no" && $stockStatus == "instock") {
						$stockQty = 1000;
					} else {
						$stockQty = get_post_meta($productId, '_stock', true);
					}
				} else {
					$stockQty = get_post_meta($variationId, '_stock', true); // Stock qty
				}
				
				$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['inventory']['stock'] = $stockQty;
				$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['inventory']['min_quantity'] = 1;
				$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['inventory']['max_quantity'] = $stockQty;
				$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['inventory']['quantity_increments'] = 1;
				$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['price'] = $getSelVarDetails[$varKey]['price'];
				$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['tier_prices'] = [];
				// For Tier Pricing
				if ($isTier) {
					$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['tier_prices'] = ($sameforAllVariants === true ? $this->createTierPrice($commonTierPrice, $getSelVarDetails[$varKey]['price']) : $this->createTierPrice($variantTierPrice[$variationId], $getSelVarDetails[$varKey]['price']));
				} else {
					$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['tier_prices'] = [];
				}
				foreach ($getSelVarDetails[$varKey]['attributes'] as $attribute) {
					$name = $attribute['name'];
					$attributeList[$name . '_id'] = $attribute['id'];
					$attributeList[$name] = $attribute['option'];
				}
				$finalArray[$getSelVarDetails[$varKey]['attributes'][$attrKey]['name']][0]['attributes'] = $attributeList;
			}
			array_push($attributes, $getSelVarDetails[$varKey]['attributes'][$attrKey]['option']);
			$j = 1;
			foreach ($getSelVarDetails as $variations) {
				$count = 0;

				if ($length > $attrKey) {
					for ($i = 0; $i <= $length; $i++) {
						if ($i != $attrKey && $variations['attributes'][$i]['option'] == $getSelVarDetails[$varKey]['attributes'][$i]['option']) {
							$count++;
						}
					}
					$attributeKey = $length;
				} else {
					for ($i = 0; $i < $attrKey; $i++) {
						if ($variations['attributes'][$i]['option'] == $getSelVarDetails[$varKey]['attributes'][$i]['option']) {
							$count++;
						}
					}
					$attributeKey = $attrKey;
				}
				if (($count == $attributeKey) && (empty($attributes) || !in_array($variations['attributes'][$attrKey]['option'], $attributes))) {
					$finalArray[$variations['attributes'][$attrKey]['name']][$j]['id'] = $variations['attributes'][$attrKey]['id'];
					$finalArray[$variations['attributes'][$attrKey]['name']][$j]['name'] = $variations['attributes'][$attrKey]['option'];
					$finalArray[$variations['attributes'][$attrKey]['name']][$j]['variant_id'] = $variations['id'];
					if ($length == $attrKey || $details == 1) {
						$varId = $variations['id'];
						$manageStock = get_post_meta($varId, '_manage_stock', true);
						$stockStatus = get_post_meta($varId, '_stock_status', true);

						if ($manageStock == "no" && $stockStatus == "instock") {
							$manageStock = get_post_meta($productId, '_manage_stock', true);
							$stockStatus = get_post_meta($productId, '_stock_status', true);
							if ($manageStock == "no" && $stockStatus == "instock") {
								$stockQty = 1000;
							} else {
								$stockQty = get_post_meta($productId, '_stock', true);
							}
						} else {
							$stockQty = get_post_meta($varId, '_stock', true); // Stock qty
						}

						$finalArray[$variations['attributes'][$attrKey]['name']][$j]['inventory']['stock'] = $stockQty;
						$finalArray[$variations['attributes'][$attrKey]['name']][$j]['inventory']['min_quantity'] = 1;
						$finalArray[$variations['attributes'][$attrKey]['name']][$j]['inventory']['max_quantity'] = $stockQty;
						$finalArray[$variations['attributes'][$attrKey]['name']][$j]['inventory']['quantity_increments'] = 1;
						$finalArray[$variations['attributes'][$attrKey]['name']][$j]['price'] = $variations['price'];
						// For Tier Pricing
						if ($isTier) {
							$finalArray[$variations['attributes'][$attrKey]['name']][$j]['tier_prices'] = ($sameforAllVariants === true ? $this->createTierPrice($commonTierPrice, $variations['price']) : $this->createTierPrice($variantTierPrice[$variations['id']], $variations['price']));
						} else {
							$finalArray[$variations['attributes'][$attrKey]['name']][$j]['tier_prices'] = [];
						}
						foreach ($variations['attributes'] as $attribute) {
							$name = $attribute['name'];
							$attributeList[$name . '_id'] = $attribute['id'];
							$attributeList[$name] = $attribute['option'];
						}
						$finalArray[$variations['attributes'][$attrKey]['name']][$j]['attributes'] = $attributeList;
					}
					array_push($attributes, $variations['attributes'][$attrKey]['option']);
					$j++;
				}
			}
			// For sorting store wise
			usort($finalArray[$argOptName], function ($a, $b) {
				//Sort the array using a user defined function
				$sizes = $this->$attrValuesWithOrders;
				$asize = $sizes[$a['name']];
				$bsize = $sizes[$b['name']];

				if ($asize == $bsize) {
					return 0;
				}
				return ($asize > $bsize) ? 1 : -1; //Compare the scores
			});
			// End
			if (!empty($finalArray)) {
				$storeResponse = $finalArray;
			}
		} catch (\Exception $e) {
			// Store exception in logs
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Store variant attribute details with quantity',
					],
				]
			);
		}

		return $storeResponse;
	}

	/**
	 * Get: Get Attribute Id by name
	 *
	 * @param $attrName Attribute name
	 *
	 * @author mukeshp@riaxe.com
	 * @date   14th April 2020
	 * @return Integer Attribute Id
	 */
	private function getAttributeIdByName($attrName) {
		$attrId = 0;
		try {
			$attributeList = $this->wc->get('products/attributes');
			foreach ($attributeList as $val) {
				$val = (object) $val;
				if ($val->name == $attrName) {
					$attrId = $val->id;
				}
			}

			return $attrId;
		} catch (Exception $e) {
			return $attrId;
		}
	}

	/**
	 * Get: get variants of a product
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author mukeshp@riaxe.com
	 * @date   29 July 2020
	 * @return Array records
	 */
	public function productVariants($request, $response, $args) {
		$variants = [];
		if (!empty($args['productID'])) {
			$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
			/*
			$option['per_page'] = 100;
			$getSelVarDetails = $this->wc->get(
				'products/' . $args['productID'] . '/' . 'variations', $option
			);
			*/
			$getSelVarDetails = $this->getProductVariantsData($args['productID'], $storeId);
			foreach ($getSelVarDetails as $key => $value) {
				$totalOpt = count($value['attributes']);
				if ($totalOpt > 0) {
					$i = 0;
					foreach ($value['attributes'] as $keyAttr => $valueAttr) {
						if ($i > 0) {
							$variationName .= " / " . $value['attributes'][$keyAttr]['option'];
						} else {
							$variationName = $value['attributes'][$keyAttr]['option'];
						}
						$i++;
					}
				} else {
					$variationName = $value['attributes'][0]['option'];
				}
				$variants[$key]['id'] = $value['id'];
				$variants[$key]['title'] = $variationName;
				$variants[$key]['price'] = $value['price'];
			}
		}
		return $variants;
	}

	/**
	 * Get: get tier Details of a product
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author mukeshp@riaxe.com
	 * @date   29 July 2020
	 * @return Array records
	 */
	public function productTierDiscounts($request, $response, $args) {
		$tierContent = [];
		if (!empty($args['productID'])) {
			$getStoreDetails = get_store_details($request);
			$store_id = $getStoreDetails['store_id'] ? $getStoreDetails['store_id'] : 1;
			if (is_multisite()) {
				switch_to_blog($store_id);
			}
			$product = wc_get_product($args['productID']);
			$metaDataContent = get_post_meta($args['productID'], 'imprintnext_tier_content');
			if (!empty($metaDataContent)) {
				$tierContent = $metaDataContent[0];
			}
			$tierContent['name'] = $product->name;
			$tierContent['price'] = $product->price;
		}
		return $tierContent;
	}

	/**
	 * Post: Save tier pricing
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author mukeshp@riaxe.com
	 * @date   29 July 2020
	 * @return Boolean status
	 */
	public function saveTierDiscount($request, $response, $args) {
		$status = false;
		$result = 0;
		$tierData = $request->getParsedBody();
		$getStoreDetails = get_store_details($request);
		$store_id = $getStoreDetails['store_id'] ? $getStoreDetails['store_id'] : 1;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$tierData['productID'] = $args['productID'];
		$tierData['price_rules'] = json_decode($tierData['price_rules'], true);
		$metaDataContent = get_post_meta($args['productID'], 'imprintnext_tier_content');
		if (!empty($metaDataContent)) {
			delete_post_meta($args['productID'], 'imprintnext_tier_content');
		}
		$result = add_post_meta($args['productID'], 'imprintnext_tier_content', $tierData);
		if ($result) {
			$status = true;
		}

		return $status;
	}

	/**
	 * Include file
	 * @author mukeshp@riaxe.com
	 * @date   29 July 2020
	 */
	private function includeWordPressCoreFile() {
		// Include files from Wordpress core
		require_once $this->storePath['abspath'] . "wp-blog-header.php";
	}

	private function createTierPrice($tierPriceRule, $variantPrice) {
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

	/**
	 * POST: Product add to store
	 *
	 * @param $productData Product data
	 * @param $catalog Catalog details
	 *
	 * @author radhanatham@riaxe.com
	 * @date  05 June 2020
	 * @return array json
	 */
	public function addProductToStore($productData, $catalog, $discountType, $storeId) {
		ini_set('memory_limit', '1024M');
		// Include files from Wordpress core
		require_once $this->storePath['abspath'] . "wp-blog-header.php";
		if (is_multisite()) {
			switch_to_blog($storeId);
		}
		global $wpdb;
		$pro_id = 0;
		$xeColor = 'color';
		$xeSize = 'size';
		$productArr = [];
		if (!empty($productData)) {
			foreach ($productData as $k => $v) {
				$price = $v['price'];
				$catalog_price = $v['catalog_price'] ? $v['catalog_price'] : $price;
				$params = array('style_id' => $v['style_id'], "catalog_code" => $catalog);
				$returnData = api_call_by_curl($params, 'product');
				$predecorData = $returnData['data'];
				$sideName = $predecorData['variations'][0]['side_name'];
				if (!empty($predecorData)) {
					$arraySize = $predecorData['size_data'];
					$arrayColor = $predecorData['color_data'];
					// Create Attributes For Color
					if (!empty($arrayColor)) {
						foreach ($arrayColor as $color) {
							$attribute = array('attribute_label' => $xeColor, 'attribute_name' => $xeColor, 'attribute_type' => 'select', 'attribute_orderby' => 'menu_order', 'attribute_public' => '0');
							$term_color = array('name' => $color, 'slug' => strtolower($color), 'term_group' => '0');
							$tableTaxonomy = $wpdb->prefix . "woocommerce_attribute_taxonomies";
							$resultColor = $wpdb->get_var("SELECT attribute_id FROM $tableTaxonomy WHERE attribute_name = '$xeColor'");
							if (!$resultColor) {
								$wpdb->insert($wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute);
								$attr_id = $wpdb->insert_id;
								do_action('woocommerce_attribute_added', $attr_id, $attribute);
								flush_rewrite_rules();
								delete_transient('wc_attribute_taxonomies');
								$this->createAttribute($attribute, $term_color);
							} else {
								$this->createAttribute($attribute, $term_color);

							}
						}
					}
					// Create Attributes For Size
					if (!empty($arraySize)) {
						foreach ($arraySize as $size) {
							$attribute1 = array('attribute_label' => $xeSize, 'attribute_name' => $xeSize, 'attribute_type' => 'select', 'attribute_orderby' => 'menu_order', 'attribute_public' => '0');
							$term_size = array('name' => $size, 'slug' => strtolower($size), 'term_group' => '0');
							$resultSize = $wpdb->get_var("SELECT attribute_id FROM $tableTaxonomy WHERE attribute_name = '$xeSize'");
							if (!$resultSize) {
								$wpdb->insert($wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute1);
								$attr_id = $wpdb->insert_id;
								do_action('woocommerce_attribute_added', $attr_id, $attribute1);
								flush_rewrite_rules();
								delete_transient('wc_attribute_taxonomies');
								$this->createAttribute($attribute1, $term_size);
							} else {
								$this->createAttribute($attribute1, $term_size);
							}

						}
					}

					$pro_id = $this->addProduct($predecorData, $price, $catalog_price, $discountType);
					if ($pro_id) {
						$productArr[$k]['product_id'] = $pro_id;
						$productArr[$k]['style_id'] = $v['style_id'];
						$productArr[$k]['decorData'] = $predecorData;
						//array_push($productIds, $pro_id);
						if (!empty($v['categories'])) {
							wp_set_object_terms($pro_id, $v['categories'] , 'product_cat');
						}
						add_post_meta($pro_id, 'is_catalog', "1");
						$product = wc_get_product($pro_id);
						$product->save();
						if (empty($sideName)) {
							// Added Side if side is empty
							$dummySideName = "Side_";
							$i = 0;
							$sideName = [];
							if (!empty($product->image_id)) {
								$sideName[$i] = $dummySideName . intval($i + 1);
								$i++;
							}
							if (!empty($product->gallery_image_ids)) {
								foreach ($product->gallery_image_ids as $value) {
									$sideName[$i] = $dummySideName . intval($i + 1);
									$i++;
								}
							}
							//End
						}
						$productArr[$k]['product_side'] = $sideName;
					}
				}
			}
		}
		return $productArr;
	}

	/**
	 * POST: Create attribute value
	 *
	 * @param $attribute Product attribute data
	 * @param $term Term details
	 *
	 * @author radhanatham@riaxe.com
	 * @date  05 June 2020
	 * @return boolean
	 */
	public function createAttribute($attribute, $term = array()) {
		global $wpdb;
		$tableName = $wpdb->prefix . "terms";
		$tableTaxonomy = $wpdb->prefix . "term_taxonomy";
		$status = 0;
		if (!empty($term)) {
			$attr_slug = wc_attribute_taxonomy_name($attribute['attribute_name']);
			$taxval = $wpdb->get_var("SELECT ts.term_id FROM $tableTaxonomy as ts
                join $tableName as tm on ts.term_id = tm.term_id
                WHERE ts.taxonomy = '$attr_slug' and tm.name ='" . $term['name'] . "'");
			if (!$taxval) {
				$wpdb->insert($wpdb->prefix . 'terms', $term);
				$term_id = $wpdb->insert_id;
				$taxonomy = 'pa_' . strtolower($attribute['attribute_name']);
				$term_taxonomy_color = array('term_id' => $term_id, 'taxonomy' => $taxonomy, 'description' => '', 'parent' => '0', 'count' => '0');
				$wpdb->insert($wpdb->prefix . 'term_taxonomy', $term_taxonomy_color);
				$status = 1;
			}
		}
		return $status;
	}

	/**
	 * POST: Create product
	 *
	 * @param $product_data Product data
	 * @param $term Term details
	 *
	 * @author radhanatham@riaxe.com
	 * @date  05 June 2020
	 * @return integer
	 */
	public function addProduct($product_data, $price, $catalog_price, $discountType) {
		$totlaQty = $product_data['total_qty'];
		$attributesXeIsDesignerArr = array('name' => 'xe_is_designer', 'position' => 0, 'visible' => false, 'variation' => false, 'options' => array("1"));
		$post = array( // Set up the basic post data to insert for our product

			'post_author' => 1,
			'post_content' => $product_data['description'],
			'post_excerpt' => $product_data['name'],
			'post_status' => 'publish',
			'post_title' => $product_data['name'],
			'post_parent' => '',
			'post_type' => 'product',
		);

		$post_id = wp_insert_post($post); // Insert the post returning the new post id

		if (!$post_id) // If there is no post id something has gone wrong so don't proceed
		{
			return false;
		}
		// Set Product Properties
		update_post_meta($post_id, '_sku', $product_data['sku']); // Set its SKU
		update_post_meta($post_id, '_visibility', 'visible'); // Set the product to visible, if not it won't show on the front end
		update_post_meta($post_id, '_price', $price); // Set Price.
		update_post_meta($post_id, '_regular_price', $price); // Set Regular Price.
		update_post_meta($post_id, '_stock_status', 'instock');
		update_post_meta($post_id, '_manage_stock', 'yes');
		update_post_meta($post_id, '_stock', $totlaQty);

		/* Set Product Images */
		$i = 0;
		$attach_id = array();
		// Include files for uploading product images
		if (!function_exists('media_handle_upload')) {
			require_once $this->storePath['abspath'] . "wp-admin" . '/includes/image.php';
			require_once $this->storePath['abspath'] . "wp-admin" . '/includes/file.php';
			require_once $this->storePath['abspath'] . "wp-admin" . '/includes/media.php';
		}

		// Add Product First Image
		$thumb_url = $product_data['images']['src'];
		// Download file to temp location
		$tmp = download_url($thumb_url);
		// Set variables for storage
		// fix file name for query strings
		preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $thumb_url, $matches);
		$file_array['name'] = basename($matches[0]);
		$file_array['tmp_name'] = $tmp;
		// If error storing temporarily, unlink
		if (is_wp_error($tmp)) {
			@unlink($file_array['tmp_name']);
			$file_array['tmp_name'] = '';
		}
		//use media_handle_sideload to upload img:
		$thumbid = media_handle_sideload($file_array, $post_id, 'product image');
		// If error storing permanently, unlink
		if (is_wp_error($thumbid)) {
			@unlink($file_array['tmp_name']);
		}
		set_post_thumbnail($post_id, $thumbid);
		// }
		// $i++;
		// }
		if (!isset($product_data['variations']) && empty($product_data['variations'])) {
			$product_data['type'] = 'simple';
		}
		wp_set_object_terms($post_id, $product_data['type'], 'product_type'); // Set it to a variable product type
		if (isset($product_data['attributes']) && !empty($product_data['attributes'])) {
			$product_data['attributes'][2] = $attributesXeIsDesignerArr;
			$this->addProductAttributes($post_id, $product_data['attributes']); // Add attributes passing the new post id, attributes & variations
		}
		if (isset($product_data['variations']) && !empty($product_data['variations'])) {
			$this->addProductVariation($post_id, $product_data, $price, $catalog_price, $discountType); // Insert variations passing the new post id & variations
		}
		return $post_id;
	}

	/**
	 * POST: Add product variation
	 *
	 * @param $product_data Product data
	 * @param $post_id post id
	 *
	 * @author radhanatham@riaxe.com
	 * @date  05 June 2020
	 * @return nothing
	 */
	public function addProductVariation($post_id, $product_data, $maxprice = 0, $catalog_price, $discountType) {
		global $wpdb;
		$tableName = $wpdb->prefix . "terms";
		$tableTaxonomy = $wpdb->prefix . "term_taxonomy";
		$j = 0;
		$imgArr = [];
		$var_image = "";
		foreach ($product_data['variations'] as $index => $variation) {
			$price = 0;
			if ($variation['piece_price'] > 0) {
				if ($discountType['is_margin'] == 1) {
					$percentage = ($variation['piece_price'] * $discountType['product_margin']) / 100;
					$price = round($variation['piece_price'] + $percentage, 2);
				} else {
					$diffPrice = $maxprice - $catalog_price;
					$price = $variation['piece_price'] + $diffPrice;
				}
			} else {
				$price = $maxprice;
			}
			$variation_post = array(

				'post_title' => $product_data['name'],
				'post_name' => $product_data['name'],
				'post_status' => 'publish',
				'post_parent' => $post_id,
				'post_type' => 'product_variation',
			);
			// Insert the variation
			$variation_post_id = wp_insert_post($variation_post);
			// Loop through the variations attributes
			foreach ($variation['attributes'] as $attribute => $value) {
				$attrVal = $value;

				// We need to insert the slug not the name into the variation post meta
				$attr_slug = wc_attribute_taxonomy_name($attribute);
				$attribute_term = get_term_by('name', $attrVal, $attr_slug);
				$attribute_term_slug = $wpdb->get_var("SELECT tm.slug FROM $tableTaxonomy AS ts JOIN $tableName as tm ON ts.term_id = tm.term_id WHERE ts.taxonomy = '$attr_slug' AND tm.name ='$attrVal'");
				update_post_meta($variation_post_id, 'attribute_' . $attr_slug, $attribute_term_slug);
			}
			// Set variation product properties
			update_post_meta($variation_post_id, '_stock_status', 'instock');
			update_post_meta($variation_post_id, '_manage_stock', 'yes');
			update_post_meta($variation_post_id, '_sku', $variation['sku']); // Set its SKU
			update_post_meta($variation_post_id, '_stock', $variation['quantity']);
			//update_post_meta($variation_post_id, '_stock', 1000);
			update_post_meta($variation_post_id, '_sale_price', $price);
			update_post_meta($variation_post_id, '_regular_price', $price);
			if (array_key_exists($product_data['variations'][$j]['attributes']['color'], $imgArr)) {
				// Only Image Id assign to product.
				$thumbid = $imgArr[$product_data['variations'][$j]['attributes']['color']][0];
				set_post_thumbnail($variation_post_id, $thumbid);
				$attach_id_list = $imgArr[$product_data['variations'][$j]['attributes']['color']];
				unset($attach_id_list[0]);
				$var_image = implode(",", $attach_id_list);
				update_post_meta($variation_post_id, '_product_image_gallery', $var_image);
				$attach_id_list = array();
				$thumbid = "";
				$var_image = "";
			} else {
				// New Image Insert
				$i = 0;
				foreach ($product_data['variations'][$j]['image_path'] as $image) {
					if ($this->remoteFileExists($image)) {
						if ($i > 0) {
							// Add Product Gallary Images.
							$thumb_url = $image;
							// Download file to temp location
							$tmp = download_url($thumb_url);
							// fix file name for query strings
							preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $thumb_url, $matches);
							$file_array['name'] = basename($matches[0]);
							$file_array['tmp_name'] = $tmp;
							if (!empty($file_array['name'])) {
								$attach_id[] = media_handle_sideload($file_array, $variation_post_id, 'product gallery');
							}
						} else {
							// Add Product First Image
							$thumb_url = $image;
							// Download file to temp location
							$tmp = download_url($thumb_url);
							// Set variables for storage
							preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $thumb_url, $matches);
							$file_array['name'] = basename($matches[0]);
							$file_array['tmp_name'] = $tmp;
							// If error storing temporarily, unlink
							if (is_wp_error($tmp)) {
								@unlink($file_array['tmp_name']);
								$file_array['tmp_name'] = '';
							}
							//use media_handle_sideload to upload img:
							if (!empty($file_array['name'])) {
								$thumbid = media_handle_sideload($file_array, $variation_post_id, 'product Variant image');
								// If error storing permanently, unlink
								if (is_wp_error($thumbid)) {
									@unlink($file_array['tmp_name']);
								}
								set_post_thumbnail($variation_post_id, $thumbid);
								$attach_id[] = $thumbid;
							}
						}
						$i++;
					}
				}
				// Added product gallery images
				if (!empty($attach_id)) {
					$imgArr[$product_data['variations'][$j]['attributes']['color']] = $attach_id;
					unset($attach_id[0]);
					$var_image = implode(",", $attach_id);
					update_post_meta($variation_post_id, '_product_image_gallery', $var_image);
					$attach_id = array();
				}
				// End
			}
			$j++;
		}
	}

	/**
	 * POST: Add product attributes
	 *
	 * @param $attributes Product attributes data
	 * @param $post_id post id
	 *
	 * @author radhanatham@riaxe.com
	 * @date  05 June 2020
	 * @return nothing
	 */
	public function addProductAttributes($post_id, $attributes) {
		global $wpdb;
		$tableName = $wpdb->prefix . "terms";
		$tableTaxonomy = $wpdb->prefix . "term_taxonomy";
		$table_term_rel = $wpdb->prefix . "term_relationships";
		$product_attributes_data = array();
		// echo "<pre>"; print_r($attributes);
		foreach ($attributes as $attribute) // Go through each attribute
		{
			$attr_slug = wc_attribute_taxonomy_name($attribute['name']);
			foreach ($attribute['options'] as $value) // Loop each variation in the file
			{
				// $value = strtolower($value);
				$taxval = $wpdb->get_var("SELECT ts.term_id FROM $tableTaxonomy as ts join $tableName as tm on ts.term_id = tm.term_id WHERE ts.taxonomy = '$attr_slug' and tm.name ='$value'");
				if (isset($taxval)) {
					$sql = "INSERT INTO $table_term_rel (`object_id`,`term_taxonomy_id`,`term_order`) values ($post_id, $taxval, 0)";
					$wpdb->query($sql);
				}
			}
			$product_attributes_data[$attr_slug] = array(
				'name' => $attr_slug,
				'value' => '',
				'is_visible' => $attribute['visible'],
				'is_variation' => $attribute['variation'],
				'is_taxonomy' => '1',

			);
		}
		$product_attributes_data['pa_is_catalog'] = array(
			'name' => 'pa_is_catalog',
			'value' => '',
			'is_visible' => false,
			'is_variation' => false,
			'is_taxonomy' => '1',

		);
		$taxvalct = $wpdb->get_var("SELECT ts.term_id FROM $tableTaxonomy as ts join $tableName as tm on ts.term_id = tm.term_id WHERE ts.taxonomy = 'pa_is_catalog' and tm.name ='1'");
		if (isset($taxvalct)) {
			$sql = "INSERT INTO $table_term_rel (`object_id`,`term_taxonomy_id`,`term_order`) values ($post_id, $taxvalct, 0)";
			$wpdb->query($sql);
		}
		update_post_meta($post_id, '_product_attributes', $product_attributes_data);
		$attribute2 = array('attribute_label' => 'xe_is_designer', 'attribute_name' => 'xe_is_designer', 'attribute_type' => 'text', 'attribute_orderby' => 'menu_order', 'attribute_public' => '0');
		$this->createNewAttribute($attribute2);
	}

	/**
	 * POST: Add product attributes
	 *
	 * @param $attributes Product attributes data
	 * @param $term Term
	 *
	 * @author radhanatham@riaxe.com
	 * @date  05 June 2020
	 * @return nothing
	 */
	private function createNewAttribute($attribute, $term = array()) {
		global $wpdb;

		$isAttrExist = $this->checkCreateAttribute($attribute['attribute_name']);

		if (!$isAttrExist) {
			$wpdb->insert($wpdb->prefix . 'woocommerce_attribute_taxonomies', $attribute);
			$attr_id = $wpdb->insert_id;
			if (!empty($term)) {
				$wpdb->insert($wpdb->prefix . 'terms', $term);
				$term_id = $wpdb->insert_id;
				$taxonomy = 'pa_' . $attribute['attribute_name'];
				$term_taxonomy_color = array('term_id' => $term_id, 'taxonomy' => $taxonomy, 'description' => '', 'parent' => '0', 'count' => '0');
				$wpdb->insert($wpdb->prefix . 'term_taxonomy', $term_taxonomy_color);
			}
			do_action('woocommerce_attribute_added', $attr_id, $attribute);
			flush_rewrite_rules();
			delete_transient('wc_attribute_taxonomies');
		}
	}

	/**
	 * GET: It will check if custom collection has been created or not
	 *
	 * @param $attributes Product attributes data
	 * @param $term Term
	 *
	 * @author radhanatham@riaxe.com
	 * @date  05 June 2020
	 * @return Boolean
	 */
	private function checkCreateAttribute($attName) {
		$isExistAttr = false;
		if (taxonomy_exists(wc_attribute_taxonomy_name($attName))) {
			$isExistAttr = true;
		}
		return $isExistAttr;
	}

	public function createProductImportCSV($request, $response, $args) {
		$getStoreDetails = get_store_details($request);
		$predecoDetails = $request->getParsedBody();
		$productData = json_clean_decode($predecoDetails['product_data']);
		$catalog = $predecoDetails['catalog_code'];
		$assetsPath = path('abs', 'assets');
		$cataloAssetsPath = $assetsPath . 'catalog';
		if (!is_dir($cataloAssetsPath)) {
			mkdir($cataloAssetsPath, 0755);
		}
		$headerData = [
			"ID", "Type", "SKU", "Name", "Published", "Is featured?", "Visibility in catalog", "Short description", "Description", "Date sale price starts", "Date sale price ends", "Tax status", "Tax class", "In stock?", "Stock", "Low stock amount", "Backorders allowed?", "Sold individually?", "Weight (kg)", "Length (cm)", "Width (cm)", "Height (cm)", "Allow customer reviews?", "Purchase note", "Sale price", "Regular price", "Categories", "Tags", "Shipping class", "Images", "Download limit", "Download expiry days", "Parent", "Grouped products", "Upsells", "Cross-sells", "External URL", "Button text", "Position", "Attribute 1 name", "Attribute 1 value(s)", "Attribute 1 visible", "Attribute 1 global", "Attribute 2 name", "Attribute 2 value(s)", "Attribute 2 visible", "Attribute 2 global", "Attribute 3 name", "Attribute 3 value(s)", "Attribute 3 visible", "Attribute 3 global", "Attribute 4 name", "Attribute 4 value(s)", "Attribute 4 visible", "Attribute 4 global", "Meta: is_catalog",
		];
		$rowData = $productData;
		$randNo = getRandom();
		$csvFilename = $randNo . '.csv';
		if (!empty($productData)) {
			$productArray = [];
			$productArray[0] = $headerData;
			$i = 1;
			$j = 0;
			$variants = [];
			$newArr = [];
			foreach ($productData as $k => $v) {
				$price = $v['price'];
				$catalog_price = $v['catalog_price'];
				$params = array("catalog_code" => $catalog, 'style_id' => $v['style_id']);
				$returnData = api_call_by_curl($params, 'product');
				$predecorData = $returnData['data'];
				$category = $categories = '';
				foreach ($predecorData['category'] as $key => $cat) {
					$category .= $cat . '>';
				}

				$categories = rtrim($category, ">");
				$arraySize = $predecorData['size_data'];
				$arrayColor = $predecorData['color_data'];
				$stock = $predecorData['total_qty'];
				$color = $colors = '';
				if (!empty($arrayColor)) {
					foreach ($arrayColor as $cl) {
						$color .= $cl . ', ';
					}
					$colors = rtrim($color, ', ');
				}

				$size = $sizes = '';
				if (!empty($arraySize)) {
					foreach ($arraySize as $sz) {
						$size .= $sz . ', ';
					}
					$sizes = rtrim($size, ', ');
				}
				$productImageUrl = $predecorData['images']['src'];
				$productArray[$i] = [
					"", $predecorData['type'], $predecorData['sku'], $predecorData['name'], 1, 0, "visible", $predecorData['name'], $predecorData['description'], "", "", "taxable", "", 1, $stock, "", 0, 0, "", "", "", "", 1, "", $price, $price, $categories, "", "", $productImageUrl, "", "", "", "", "", "", "", "", 0, "size", $sizes, 1, 1, 'color', $colors, 1, 1, "xe_is_designer", 1, 0, 1, "is_catalog", 1, 0, 1, 1,
				];
				if (!empty($predecorData['variations'])) {
					$variationSKU = "";
					$color = "";
					$size = "";
					foreach ($predecorData['variations'] as $keys => $variations) {
						$quantity = $variations['quantity'];
						$color = $variations['attributes']['color'];
						$size = $variations['attributes']['size'];
						$varintPrice = 0;
						if ($variations['piece_price'] > 0) {
							$diffPrice = $price - $catalog_price;
							$varintPrice = $variations['piece_price'] + $diffPrice;
						} else {
							$varintPrice = $maxprice;
						}
						$image_path = $variations['image_path'];
						$image = $images = '';
						if (!empty($image_path)) {
							foreach ($image_path as $img) {
								if ($img != '') {
									$image .= $img . ', ';
								}
							}
							$images = rtrim($image, ', ');
						}
						$variationSKU = $variations['sku'];
						$variants[$j] = [
							"", "variation", $variationSKU, $predecorData['name'], 1, 0, "visible", "", "", "", "", "taxable", "parent", 1, $quantity, "", 0, 0, "", "", "", "", 0, "", $varintPrice, $varintPrice, "", "", "", $images, "", "", $predecorData['sku'], "", "", "", "", "", 0, "size", $size, "", 1, 'color', $color, "", 1, "", "", "", "", "", "", "", "", "",
						];
						$j++;
					}
				}
				$i++;
			}
		}

		$newArr = array_merge($productArray, $variants);
		if (!empty($newArr)) {
			$cfilename = $cataloAssetsPath . '/' . $csvFilename;
			if (is_dir($cataloAssetsPath)) {
				$fp = fopen($cfilename, 'w');
				foreach ($newArr as $fields) {
					fputcsv($fp, $fields);
				}
			}
			fclose($fp);
		}
		return $csvFilename;
	}
	/**
	 * GET: Get product variants vata
	 *
	 * @param $product_id
	 *
	 * @author soumays@riaxe@riaxe.com
	 * @date  24 Dec 2020
	 * @return Array
	 */
	public function getProductVariantsData($product_id, $store_id = 1) {
		global $wpdb;
		if (is_multisite()) {
			switch_to_blog($store_id);
		}
		$prefix = $wpdb->prefix;
		$attribute_taxonomies = $prefix . 'woocommerce_attribute_taxonomies';
		$productVariationArray = [];
		$product = wc_get_product($product_id);
		$prouctName = $product->get_name() ? $product->get_name() : '';
		$src = wp_get_attachment_url($product->get_image_id()) ? wp_get_attachment_url($product->get_image_id()) : '';
		if (!empty($product->get_children())) {
			$current_products = $product->get_children();
			foreach ($product->get_children() as $key => $values) {
				$attributesArray = [];
				$productVariationId = $values;
				$max_regular_price = $product->get_variation_regular_price('max');
				$url = get_permalink($productVariationId);
				$variation_obj = wc_get_product($productVariationId);
				$manage_stock = $variation_obj->manage_stock;
				$stock_status = $variation_obj->stock_status;
				$sale_price = $variation_obj->sale_price;
				$regular_price = $variation_obj->regular_price;
				$description = $variation_obj->description;
				$sku = $variation_obj->sku;
				$price = $variation_obj->price;
				// Get the variation quantity
				if ($manage_stock != 1 && $stock_status == "instock") {
					$stock_qty = 1000;
				} else {
					$stock_qty = $variation_obj->get_stock_quantity(); // Stock qty
				}

				$variationAttributesobj = new \WC_Product_Variation($productVariationId);
				$j = 0;
				foreach ($variationAttributesobj->get_attributes() as $taxonomy => $terms_slug) {
					// To get the taxonomy object
					$taxonomy_obj = get_taxonomy($taxonomy);
					$termDetails = get_term_by('slug', $terms_slug, $taxonomy);
					$taxonomy_name = $taxonomy_obj->name;
					$taxonomy_label = $taxonomy_obj->label;
					$name = str_replace("pa_", "", $taxonomy_name);
					$sql = "SELECT * FROM " . $attribute_taxonomies . " WHERE attribute_name ='" . $name . "'";
					$results = $wpdb->get_results($sql, ARRAY_A);
					$attributesArray[$j]['id'] = $results[0]['attribute_id'];
					$attributesArray[$j]['name'] = $results[0]['attribute_label'];
					$attributesArray[$j]['option'] = $termDetails->name;
					$j++;
				}

				/** get image data */

				$productVariationArray[$key]['id'] = $productVariationId;
				$productVariationArray[$key]['description'] = $description;
				$productVariationArray[$key]['permalink'] = $url;
				$productVariationArray[$key]['sku'] = $sku;
				$productVariationArray[$key]['price'] = $price;
				$productVariationArray[$key]['regular_price'] = $regular_price;
				$productVariationArray[$key]['sale_price'] = $sale_price;
				$productVariationArray[$key]['manage_stock'] = $manage_stock;
				$productVariationArray[$key]['stock_status'] = $stock_status;
				$productVariationArray[$key]['stock_quantity'] = $stock_qty;
				$productVariationArray[$key]['image'] = array('id' => $productVariationId, 'src' => $src, 'name' => $prouctName);
				$productVariationArray[$key]['attributes'] = $attributesArray;

			}
			return $productVariationArray;
		}

	}

	/**
	 * GET: Get list of category/subcategory or a Single category/subcategory
	 * from the WooCommerce API
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author mukeshp@riaxe.com
	 * @date   1 March 2021
	 * @return Array of list/one categories(s)
	 */
	public function getCategoriesSubcategories($request, $response, $args) {
		$categories = [];
		$storeResponse = [];
		$name = $request->getQueryParam('name') ? $request->getQueryParam('name') : '';
		$store_id = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		try {

			$taxonomy = 'product_cat';
			$order = 'desc';
			$orderby = 'id';
			$empty = 0;
			$args = array(
				'taxonomy' => $taxonomy,
				'orderby' => $orderby,
				'order' => $order,
				'hide_empty' => $empty,
			);
			$categoriesList = get_categories($args); 
			$i = 0;
			foreach ($categoriesList as $cat) 
			{ 
			    if($cat->parent < 1) 
			    {

				    $subArgs=array( 
				      'taxonomy' => $taxonomy, 
				      'orderby' => 'name', 
				      'order' => 'ASC', 
				      'child_of' => $cat->cat_ID,
				      'hide_empty' => $empty,
				      ); 
				    $subCategoriesList=get_categories($subArgs); 
				    $j=0;
				    $subCategories = [];
					foreach($subCategoriesList as $subCategory) {  
				        $subCategories[$j] = [
							'id' => $subCategory->term_id,
							'name' => htmlspecialchars_decode($subCategory->name, ENT_NOQUOTES),
							'slug' => $subCategory->slug,
							'parent_id' => $subCategory->parent,
						];
						$j++;
			       	}  
			    	$categories[$i] = [
						'id' => $cat->term_id,
						'name' => htmlspecialchars_decode($cat->name, ENT_NOQUOTES),
						'slug' => $cat->slug,
						'parent_id' => $cat->parent,
						'sub_catagory' => $subCategories,
					];
					$i++;
			    } 
			}

			if (is_array($categories) && !empty($categories)) {
				$storeResponse = $categories;
			}
		} catch (\Exception $e) {
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Get categories details',
					],
				]
			);
		}

		return $storeResponse;
	}

	/**
	 * Post: Create product catagories/subcategories.
	 *
	 * @param $request       Slim's Request object
	 * @param $response      Slim's Response object
	 *
	 * @author mukeshp@riaxe.com
	 * @date   02 Mar 2021
	 * @return Array records and server status
	 */
	public function createProductCatagories($request, $response) {
		$storeResponse = [];
		$getPostData = $request->getParsedBody();
		$catName = $getPostData['name'];
		$catId = $getPostData['catId'];
		
		try {
			$catData = term_exists($catName, 'product_cat');
			if ($catData == 0 && $catData == null) {
				$catRes = wp_insert_term( $catName, 'product_cat', array('parent' => $catId));
				if ( is_wp_error($catRes) ) {
					$storeResponse = [];
				} else {
					$storeResponse = [
						'status' => 1,
						'catatory_id' => $catRes['term_id'],
						'message' => message('Catagories', 'saved'),
					];
				}
			} else {
				$storeResponse = [
						'status' => 0,
						'message' => 'Category already exist.',
					];
			}
		} catch (\Exception $e) {
			$storeResponse = [];
			// Store exception in logs
			create_log(
				'store', 'error',
				[
					'message' => $e->getMessage(),
					'extra' => [
						'module' => 'Create Categories',
					],
				]
			);
		}

		return $storeResponse;
	}

	/**
	 * Remove: Delete catagories and subcatagories.
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author mukeshp@riaxe.com
	 * @date   4 March 2021
	 * @return Array of list/one product(s)
	 */
	public function removeCategories($request, $response, $args) {
		$storeResponse = [];
		if (isset($args['id']) && $args['id'] > 0) {
			try {
				$catId = (int)$args['id'];
				if (wp_delete_term( $catId, 'product_cat' )) {
					$storeResponse = [
			            'status' => 1,
			            'message' => message('Categories', 'deleted'),
			        ];
				} else {
				  	$storeResponse = [
			            'status' => 0,
			            'message' => message('Categories', 'error'),
			        ];
				}
			} catch (\Exception $e) {
				$storeResponse = [
		            'status' => 0,
		            'message' => $e->getMessage(),
		        ];
				// Store exception in logs
				create_log(
					'store', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Remove catagories',
						],
					]
				);
			}
		}
		return $storeResponse;
	}

	/**
     * Get: Check remote file exist or not.
     *
     * @param $url 
     *
     * @author mike@imprintnext.com
     * @date  16 July 2021
     */
    public function remoteFileExists($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if( $httpCode == 200 ){return true;}
    }
}