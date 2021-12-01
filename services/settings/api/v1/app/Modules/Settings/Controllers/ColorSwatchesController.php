<?php
/**
 * Manage Color Swatches
 *
 * PHP version 5.6
 *
 * @category  Products
 * @package   Store
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Settings\Controllers;

use App\Modules\Settings\Models\ColorSwatch;
use App\Modules\Settings\Models\ColorType;
use SwatchStoreSpace\Controllers\StoreColorVariantController;

/**
 * Color Swatches Controller
 *
 * @category Class
 * @package  Color_Swatch
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ColorSwatchesController extends StoreColorVariantController {
	/**
	 * GET: Get color data from store
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Dec 2019
	 * @return A JSON Response
	 */
	public function getColorSwatch($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'color_id' => null,
			'message' => message('Variant Data', 'not_found'),
			'data' => [],
		];
		$storeResponse = $this->getColorVariants($request, $response, $args);
		if (!empty($storeResponse)) {
			$variantData = $this->getColorSwatchData(
				$storeResponse['attribute_terms']
			);
			$jsonResponse = [
				'status' => 1,
				'records' => count($variantData),
				'color_id' => $storeResponse['color_id'],
				'data' => $variantData,
			];
		}
		return response(
			$response, [
				'data' => $jsonResponse, 'status' => $serverStatusCode,
			]
		);
	}

	/**
	 * Post: Save Color in Store
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Oct 2019
	 * @return A JSON Response
	 */
	public function saveStoreColor($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$jsonResponse = [
			'status' => 0,
			'message' => message('Color Swatch', 'exist'),
		];
		$storeDetails = get_store_details($request);
		$storeId = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
		if (isset($allPostPutVars['name'])
			&& $allPostPutVars['name'] != ""
			&& isset($allPostPutVars['color_id'])
			&& $allPostPutVars['color_id'] > 0
		) {
			if (STORE_NAME == 'Prestashop') {
				//call prestashop store api
				$newColor = $this->saveColorValue($allPostPutVars);
			} else {
				$newColor = $this->saveColor($allPostPutVars['name'], $allPostPutVars['color_id'], $storeId);
			}
		}
		if (!empty($newColor)) {
			if (isset($newColor['id']) && $newColor['id'] != "") {
				$allPostPutVars += ['attribute_id' => $newColor['id']];
			}
			if (isset($allPostPutVars['attribute_id'])) {
				if (STORE_NAME == 'Prestashop') {
					$getUploadedFileName = '';
				} else {
					$uploadedFiles = $request->getUploadedFiles();
					if (!empty($uploadedFiles)) {
						$getUploadedFileName = do_upload(
							'upload',
							path('abs', 'swatch'),
							[150],
							'string'
						);
						$allPostPutVars += ['file_name' => $getUploadedFileName];
					}
				}
				$colorSwatchInit = new ColorSwatch($allPostPutVars);
				try {
					$colorSwatchInit->save();
					$jsonResponse = [
						'status' => 1,
						'message' => message('Color Swatch', 'saved'),
					];
				} catch (\Exception $e) {
					$serverStatusCode = EXCEPTION_OCCURED;
					$jsonResponse = [
						'status' => 0,
						'message' => message('Color Swatch', 'exception'),
						'exception' => show_exception() === true ?
						$e->getMessage() : '',
					];
				}
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Post: Save Color Swatches
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Oct 2019
	 * @return A JSON Response
	 */
	public function saveColorSwatch($request, $response) {

		$serverStatusCode = OPERATION_OKAY;
		$allPostPutVars = $request->getParsedBody();
		$jsonResponse = [
			'status' => 0,
			'message' => message('Color Swatch', 'error'),
		];

		if (isset($allPostPutVars['attribute_id'])) {
			$uploadedFiles = $request->getUploadedFiles();
			if (!empty($uploadedFiles)) {
				if (STORE_NAME == 'Prestashop') {
					//call prestashop store api
					$this->saveColorValue($allPostPutVars);
					$getUploadedFileName = '';
				} else {
					$getUploadedFileName = do_upload(
						'upload',
						path('abs', 'swatch'),
						[150],
						'string'
					);
				}
				$allPostPutVars += ['file_name' => $getUploadedFileName];
			}
			$colorSwatchInit = new ColorSwatch($allPostPutVars);
			try {
				$colorSwatchInit->save();
				$jsonResponse = [
					'status' => 1,
					'message' => message('Color Swatch', 'saved'),
				];
			} catch (\Exception $e) {
				$serverStatusCode = EXCEPTION_OCCURED;
				$jsonResponse = [
					'status' => 0,
					'message' => message('Color Swatch', 'exception'),
					'exception' => show_exception() === true ?
					$e->getMessage() : '',
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Put: Update Product Images
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Oct 2019
	 * @return A JSON Response
	 */
	public function updateColorSwatch($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Color Swatch', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		if (isset($args['color_swatch_id']) && $args['color_swatch_id'] > 0) {
			$colorSwatchId = $args['color_swatch_id'];
			$colorSwatchInit = new ColorSwatch();
			$colorSwatchData = $colorSwatchInit->where(['xe_id' => $colorSwatchId]);

			if ($colorSwatchData->count() > 0) {
				if (isset($allPostPutVars['attribute_id'])) {
					if (isset($allPostPutVars['hex_code'])
						&& $allPostPutVars['hex_code'] != ""
					) {
						$this->deleteOldFile(
							"color_swatches", "file_name", [
								'xe_id' => $colorSwatchId], path('abs', 'swatch')
						);
						$colorSwatchInit->where(
							'xe_id', $colorSwatchId
						)->update(["file_name" => null]);
					}
					if (STORE_NAME == 'Prestashop') {
						//call prestashop store api
						$this->saveColorValue($allPostPutVars);
						$getUploadedFileName = '';
					} else {
						$getUploadedFileName = do_upload(
							'upload',
							path('abs', 'swatch'),
							[150],
							'string'
						);
					}
					$swatchData = [
						'attribute_id' => $allPostPutVars['attribute_id'],
						'hex_code' => $allPostPutVars['hex_code'],
						'color_type' => $allPostPutVars['color_type'],
					];
					if (!empty($getUploadedFileName)) {
						$swatchData['file_name'] = $getUploadedFileName;
					}
					try {
						$colorSwatchData->update($swatchData);
						$jsonResponse = [
							'status' => 1,
							'message' => message('Color Swatch', 'updated'),
							'color_swatch_id' => $colorSwatchId,
						];
					} catch (\Exception $e) {
						$serverStatusCode = EXCEPTION_OCCURED;
						$jsonResponse = [
							'status' => 0,
							'message' => message('Color Swatch', 'exception'),
							'exception' => show_exception() === true ?
							$e->getMessage() : '',
						];
					}
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: Get color type
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   5 Dec 2019
	 * @return A JSON Response
	 */
	public function getColorType($request, $response) {
		$jsonResponse = [
			'status' => 0,
			'message' => message('Color Type', 'not_found'),
		];
		$serverStatusCode = OPERATION_OKAY;
		$colorTypeInit = new ColorType();
		if ($colorTypeInit->count() > 0) {
			$jsonResponse = [
				'status' => 1,
				'data' => $colorTypeInit->get(),
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
}
