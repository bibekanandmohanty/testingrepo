<?php
/**
 * Manage Masks
 *
 * PHP version 5.6
 *
 * @category  Mask
 * @package   Eloquent
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Masks\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\Masks\Models\Mask;
use App\Modules\Masks\Models\MaskTagRelation;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Mask Controller
 *
 * @category Class
 * @package  Mask
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class MaskController extends ParentController {

	/**
	 * POST: Save Mask
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4th Nov 2019
	 * @return json response wheather data is saved or any error occured
	 */
	public function saveMasks($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Masks', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);
		$getUploadedFileName = do_upload('upload', path('abs', 'mask'), [150], 'string');
		if (!empty($getUploadedFileName)) {
			$allPostPutVars += ['file_name' => $getUploadedFileName];
		}
		$getMaskedFileName = do_upload('mask', path('abs', 'mask'), [150], 'string');
		if (!empty($getMaskedFileName)) {
			$allPostPutVars += ['mask_name' => $getMaskedFileName];
		}

		$allPostPutVars['store_id'] = $getStoreDetails['store_id'];
		// Save Mask Data
		$tags = "";
		$mask = new Mask($allPostPutVars);
		if ($mask->save()) {
			$lastInsertId = $mask->xe_id;
			/**
			 * Save tags
			 * Tag Names format : tag1,tag2,tag3
			 */
			if (!empty($allPostPutVars['tags'])) {
				$tags = $allPostPutVars['tags'];
			}
			$this->saveMaskTags(
				$getStoreDetails['store_id'], $lastInsertId, $tags
			);
			$jsonResponse = [
				'status' => 1,
				'message' => message('Masks', 'saved'),
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Save Tags and Mask-Tag Relations
	 *
	 * @param $storeId Store ID
	 * @param $maskId  Mask ID
	 * @param $tags    Multiple tags
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4th Nov 2019
	 * @return boolean
	 */
	protected function saveMaskTags($storeId, $maskId, $tags) {
		// Save Mask and tags relation
		if (!empty($tags)) {
			$getTagIds = $this->saveTags($storeId, $tags);
			// SYNC Tags into Relationship Table
			$maskInit = new Mask();
			$findMask = $maskInit->find($maskId);
			if ($findMask->tags()->sync($getTagIds)) {
				return true;
			}
		} else {
			// Clean relation in case no tags supplied
			$tagRelInit = new MaskTagRelation();
			$maskTags = $tagRelInit->where('mask_id', $maskId);
			if ($maskTags->delete()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * GET: List of Mask
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4th Nov 2019
	 * @return All/Single Mask List
	 */
	public function getMasks($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$maskData = [];
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('Masks', 'not_found'),
		];
		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);
		$maskInit = new Mask();
		$getMasks = $maskInit->where('xe_id', '>', 0)
			->where('store_id', '=', $getStoreDetails['store_id']);

		if (!empty($args)) {
			$maskId = to_int($args['id']);
			//For single Mask data
			$maskData = $getMasks->where('xe_id', $maskId)->first();
			$getTags = $this->getTagsById(
				'Masks', 'MaskTagRelation', 'mask_id', $maskId
			);
			$maskData['tags'] = $getTags;
			$jsonResponse = [
				'status' => 1,
				'data' => [
					$maskData,
				],
			];
		} else {
			//All Filter columns from url
			$page = $request->getQueryParam('page');
			$perpage = $request->getQueryParam('perpage');
			$sortBy = !empty($request->getQueryParam('sortby'))
			&& $request->getQueryParam('sortby') != ""
			? $request->getQueryParam('sortby') : 'xe_id';
			$order = !empty($request->getQueryParam('order'))
			&& $request->getQueryParam('order') != ""
			? $request->getQueryParam('order') : 'desc';
			$name = $request->getQueryParam('name');
			$offset = 0;
			$type = $request->getQueryParam('type') ? $request->getQueryParam('type') : '';
			$source = $request->getQueryParam('source') ? $request->getQueryParam('source') : '';
			// For multiple Mask data
			$getMasks->select('xe_id', 'name', 'file_name', 'mask_name');
			if (!empty($name)) {
				$name = '\\' . $name;
				// Search name inside Mask
				$getMasks->where('name', 'LIKE', '%' . $name . '%')
				// Search name inside Tags
					->orWhereHas(
						'maskTags.tag', function ($q) use ($name) {
							return $q->where('name', 'LIKE', '%' . $name . '%');
						}
					);
			}
			// Total records including all filters
			$getTotalPerFilters = $getMasks->count();
			// Pagination Data
			if (isset($page) && $page != "") {
				$totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
				$offset = $totalItem * ($page - 1);
				$getMasks->skip($offset)->take($totalItem);
			}
			// Sorting All records by column name and sord order parameter
			if (isset($sortBy) && $sortBy != "" && isset($order) && $order != "") {
				$getMasks->orderBy($sortBy, $order);
			}
			if ($getStoreDetails['store_id'] > 1 && $type == 'tool' && empty($source)) {
				$maskData = [];
				$currentStoreUrl = '';
				$databaseStoreInfo = DB::table('stores')->where('xe_id', '=', $getStoreDetails['store_id']);
				if ($databaseStoreInfo->count() > 0) {
					$storeData = $databaseStoreInfo->get()->toArray();
					$storeDataArray = (array) $storeData[0];
					$currentStoreUrl = $storeDataArray['store_url'];
				}

				$maskDataResponse = $getMasks->get();
				foreach ($maskDataResponse->toArray() as $key => $value) {
					$file_name = $value['file_name'];
					$thumbnail = $value['thumbnail'];
					$mask_name = $value['mask_name'];
					$hostname = parse_url($file_name, PHP_URL_HOST); //hostname
					$value['file_name'] = str_replace($hostname, $currentStoreUrl, $file_name);
					$value['thumbnail'] = str_replace($hostname, $currentStoreUrl, $thumbnail);
					$value['mask_name'] = str_replace($hostname, $currentStoreUrl, $mask_name);
					$maskData[] = $value;
				}
			} else {

				$maskData = $getMasks->get();
			}
			$jsonResponse = [
				'status' => 1,
				'records' => count($maskData),
				'total_records' => $getTotalPerFilters,
				'data' => $maskData,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * PUT: Update a Single Mask
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4th Nov 2019
	 * @return json response wheather data is updated or not
	 */
	public function updateMask($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Masks', 'not_found'),
		];
		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);
		$allPostPutVars = $updateData = $request->getParsedBody();

		if (!empty($args['id'])) {
			$maskId = to_int($args['id']);
			$maskInit = new Mask();
			$getOldMask = $maskInit->where('xe_id', $maskId);
			if ($getOldMask->count() > 0) {
				unset(
					$updateData['id'], $updateData['upload'],
					$updateData['mask'], $updateData['tags'],
					$updateData['maskId']
				);
				// Delete old file
				$this->deleteOldFile(
					"masks", "file_name", ['xe_id' => $maskId], path('abs', 'mask')
				);
				$getUploadedFileName = do_upload('upload', path('abs', 'mask'), [150], 'string');
				if (!empty($getUploadedFileName)) {
					$updateData += ['file_name' => $getUploadedFileName];
				}
				// Delete old mask file
				$this->deleteOldFile(
					"masks", "mask_name", ['xe_id' => $maskId], path('abs', 'mask')
				);
				$getUploadedMaskName = do_upload('mask', path('abs', 'mask'), [150], 'string');
				if (!empty($getUploadedMaskName)) {
					$updateData += ['mask_name' => $getUploadedMaskName];
				}
				$updateData += ['store_id' => $getStoreDetails['store_id']];
				// Update record
				try {
					$maskInit = new Mask();
					$maskInit->where('xe_id', '=', $maskId)->update($updateData);
					/**
					 * Save tags
					 * Tag Names format : tag1,tag2,tag3
					 */
					$tags = !empty($allPostPutVars['tags'])
					? $allPostPutVars['tags'] : "";
					$this->saveMaskTags(
						$getStoreDetails['store_id'], $maskId, $tags
					);
					$jsonResponse = [
						'status' => 1,
						'message' => message('Masks', 'updated'),
					];
				} catch (\Exception $e) {
					// Store exception in logs
					create_log(
						'Assets', 'error',
						[
							'message' => $e->getMessage(),
							'extra' => [
								'module' => 'Masks',
							],
						]
					);
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * DELETE: Delete single/multiple Mask(s)
	 *
	 * @param $request  Slim's Argument parameters
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4th Nov 2019
	 * @return json response wheather data is deleted or not
	 */
	public function deleteMask($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Mask', 'not_found'),
		];
		$success = 0;
		if (!empty($args)) {
			$getDeleteIds = $args['id'];
			$getDeleteIdsToArray = json_clean_decode($getDeleteIds, true);
			$totalCount = count($getDeleteIdsToArray);
			if (!empty($getDeleteIdsToArray) && $totalCount > 0) {
				$maskInit = new Mask();
				if ($maskInit->whereIn('xe_id', $getDeleteIdsToArray)->count() > 0) {
					try {
						foreach ($getDeleteIdsToArray as $maskId) {
							$this->deleteOldFile(
								"masks", "file_name", [
									'xe_id' => $maskId,
								], path('abs', 'mask')
							);
							$this->deleteOldFile(
								"masks", "mask_name", [
									'xe_id' => $maskId,
								], path('abs', 'mask')
							);
							$maskInit->where('xe_id', $maskId)->delete();
							$success++;
						}
						if ($success > 0) {
							$jsonResponse = [
								'status' => 1,
								'message' => $success . ' out of ' . $totalCount
								. ' Mask(s) deleted successfully',
							];
						}
					} catch (\Exception $e) {
						// Store exception in logs
						create_log(
							'Assets', 'error',
							[
								'message' => $e->getMessage(),
								'extra' => [
									'module' => 'Masks',
								],
							]
						);
					}
				}
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

}
