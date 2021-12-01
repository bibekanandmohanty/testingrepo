<?php
/**
 * Manage Cliparts
 *
 * PHP version 5.6
 *
 * @category  Clipart
 * @package   Eloquent
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Cliparts\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\Cliparts\Models\Clipart;
use App\Modules\Cliparts\Models\ClipartCategory as Category;
use App\Modules\Cliparts\Models\ClipartTag as Tag;
use App\Modules\Cliparts\Models\ClipartTagRelation;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Clipart Controller
 *
 * @category Class
 * @package  Clipart
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class ClipartController extends ParentController {
	/**
	 * POST: Save Clipart
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author tanmayap@riaxe.com
	 * @date   12 Aug 2019
	 * @return json
	 */
	public function saveCliparts($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$getStoreDetails = get_store_details($request);
		$jsonResponse = [
			'status' => 0,
			'message' => message('Cliparts', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$allFileNames = [];
		$saveClipartList = [];
		$success = 0;
		// Upload multiple Files
		$allFileNames = do_upload('upload', path('abs', 'vector'), [100], 'array');
		if (!empty($allFileNames)) {
			foreach ($allFileNames as $eachFileKey => $eachFile) {
				$clipartId = 0;
				if (!empty($eachFile) && $eachFile != "") {
					$saveClipartList[$eachFileKey] = [
						'store_id' => $getStoreDetails['store_id'],
						'name' => $allPostPutVars['name'],
						'price' => $allPostPutVars['price'],
						'width' => $allPostPutVars['width'],
						'height' => $allPostPutVars['height'],
						'file_name' => $eachFile,
						'is_scaling' => $allPostPutVars['is_scaling'],
					];
					$saveEachClipart = new Clipart($saveClipartList[$eachFileKey]);
					if ($saveEachClipart->save()) {
						$clipartId = $saveEachClipart->xe_id;
						/**
						 * Save category and subcategory data
						 * Category id format: [4,78,3]
						 */
						if (isset($allPostPutVars['categories'])
							&& $allPostPutVars['categories'] != ""
						) {
							$categoryIds = $allPostPutVars['categories'];
							$this->saveClipartCategories(
								$clipartId, $categoryIds
							);
						}
						/**
						 * Save tags with respect to the cliparts
						 * Tag Names format : tag1,tag2,tag3
						 */
						$tags = !empty($allPostPutVars['tags'])
						? $allPostPutVars['tags'] : "";
						$this->saveClipartTags(
							$getStoreDetails['store_id'], $clipartId, $tags
						);
						$success++;
					}
				}
			}
			if ($success > 0) {
				$jsonResponse = [
					'status' => 1,
					'message' => message('Clipart', 'saved'),
				];
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: List of Clipart
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   13 Aug 2019
	 * @return json
	 */
	public function getCliparts($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 1,
			'data' => [],
			'message' => message('Clipart', 'not_found'),
		];
		// Get Store Specific Details from helper

		if (!empty($request->getQueryParam('store_id'))) {
			$getStoreDetails['store_id'] = $request->getQueryParam('store_id');
		} else {
			$getStoreDetails = get_store_details($request);
		}
		$clipartInit = new Clipart();
		$getCliparts = $clipartInit->where('xe_id', '>', 0);
		if (!empty($args['id'])) {
			$clipartId = to_int($args['id']);
			$getCliparts->where('xe_id', $clipartId)
				->select(
					'xe_id', 'name', 'price', 'width', 'height',
					'file_name', 'is_scaling',
					'store_id'
				);

			$getCategories = $this->getCategoriesById(
				'Cliparts', 'ClipartCategoryRelation', 'clipart_id', $clipartId
			);
			$getTags = $this->getTagsById(
				'Cliparts', 'ClipartTagRelation', 'clipart_id', $clipartId
			);
			if ($getCliparts->count() > 0) {
				$getClipart = $getCliparts->first()->toArray();
				$getClipart['categories'] = $getCategories;
				$getClipart['tags'] = $getTags;
				// Unset category_name Key in case of single record fetch
				unset($getClipart['category_names']);
				$jsonResponse = [
					'status' => 1,
					'records' => 1,
					'data' => [
						$getClipart,
					],
				];
			}
		} else {

			//All Filter columns from url
			$type = (
				!empty($request->getQueryParam('type'))
				&& $request->getQueryParam('type') != null
			) ? $request->getQueryParam('type') : '';
			$page = $request->getQueryParam('page');
			$perpage = $request->getQueryParam('perpage');
			$categoryId = $request->getQueryParam('category');
			$sortBy = (
				!empty($request->getQueryParam('sortby'))
				&& $request->getQueryParam('sortby') != null
			) ? $request->getQueryParam('sortby') : 'xe_id';
			$order = (
				!empty($request->getQueryParam('order'))
				&& $request->getQueryParam('order') != null
			) ? $request->getQueryParam('order') : 'desc';
			$name = $request->getQueryParam('name');
			$printProfileKey = $request->getQueryParam('print_profile_id');
			$source = $request->getQueryParam('source') ? $request->getQueryParam('source') : '';
			if ($type == 'tool') {
				$currentStoreUrl = '';
				$defaultStoreUrl = '';
				if ($getStoreDetails['store_id'] > 1) {
					$databaseStoreInfo = DB::table('stores')->where('xe_id', '=', $getStoreDetails['store_id']);
					if ($databaseStoreInfo->count() > 0) {
						$storeData = $databaseStoreInfo->get()->toArray();
						$storeDataArray = (array) $storeData[0];
						$currentStoreUrl = $storeDataArray['store_url'];
					}
				}
				$records = 0;
				$totalRecords = 0;
				$clipartCategoryList = $this->getCategoryByPrintProfileId($request, $response, $args);
				if (!empty($clipartCategoryList) && !empty($clipartCategoryList['data'])) {
					$records = $clipartCategoryList['records'];
					$totalRecords = $clipartCategoryList['total_records'];
					$i = 0;
					$categoriesList = [];

					foreach ($clipartCategoryList['data'] as $key => $category) {
						// Filter by Category IDs and It's Subcategory IDs
						if (!empty($category['id'])) {
							$clipartInit = new Clipart();
							$getCliparts = $clipartInit->where('xe_id', '>', 0);
							$getCliparts->select(
								'xe_id', 'name', 'height', 'width', 'price',
								'file_name', 'is_scaling'
							);
							$getCliparts->where(['store_id' => $getStoreDetails['store_id']]);
							$searchCategories = $category['id'];
							$getCliparts->whereHas(
								'clipartCategory', function ($q) use ($searchCategories) {
									return $q->where('category_id', $searchCategories);
								}
							);

							$getTotalPerFilters = $getCliparts->count();
							// Pagination Data
							$offset = 0;
							if (!empty($page)) {
								$totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
								$offset = $totalItem * ($page - 1);
								$getCliparts->skip($offset)->take($totalItem);
							}
							// Sorting All records by column name and sord order parameter
							if (!empty($sortBy) && !empty($order)) {
								$getCliparts->orderBy($sortBy, $order);
							}
							$cliparts = $getCliparts->get();
							$clipartsTool = [];
							if (!empty($currentStoreUrl)) {
								foreach ($cliparts->toArray() as $key => $value) {
									if ($source != 'admin') {
										$file_name = $value['file_name'];
										$thumbnail = $value['thumbnail'];
										$hostname = parse_url($file_name, PHP_URL_HOST); //hostname
										$value['file_name'] = str_replace($hostname, $currentStoreUrl, $file_name);
										$value['thumbnail'] = str_replace($hostname, $currentStoreUrl, $thumbnail);
									}
									$clipartsTool[] = $value;
								}
							} else {
								$clipartsTool = $cliparts->toArray();
							}

							//$clipartsTool =  $cliparts->toArray();

							$categoriesList[$i]['id'] = $category['id'];
							$categoriesList[$i]['name'] = $category['name'];
							$categoriesList[$i]['order'] = $category['order'];
							$categoriesList[$i]['is_disable'] = $category['is_disable'];
							$categoriesList[$i]['is_default'] = $category['is_default'];
							$categoriesList[$i]['cliparts'] = $clipartsTool;
							$categoriesList[$i]['records'] = count($cliparts);
							$categoriesList[$i]['total_records'] = $getTotalPerFilters;
							$i++;
						}
					}
				}
				if (!empty($categoriesList)) {
					$jsonResponse = [
						'status' => 1,
						'records' => $records,
						'total_records' => $totalRecords,
						'data' => $categoriesList,
					];
				}
			} else {

				$getCliparts->select(
					'xe_id', 'name', 'height', 'width', 'price',
					'file_name', 'is_scaling'
				);
				$getCliparts->where(['store_id' => $getStoreDetails['store_id']]);
				// Searching as per clipart name, category name & tag name
				if (isset($name) && $name != "") {
					$name = '\\' . $name;
					$getCliparts->where(
						function ($query) use ($name) {
							$query->where('name', 'LIKE', '%' . $name . '%')
								->orWhereHas(
									'clipartTags.tag', function ($q) use ($name) {
										return $q->where(
											'name', 'LIKE', '%' . $name . '%'
										);
									}
								)->orWhereHas(
								'clipartCategory.category', function ($q) use ($name) {
									return $q->where(
										'name', 'LIKE', '%' . $name . '%'
									);
								}
							);
						}
					);
				}
				// Filter By Print Profile Id
				if (!empty($printProfileKey)) {
					$assetTypeArr = $this->assetsTypeId('cliparts');
					$profileCatRelObj = new \App\Modules\PrintProfiles\Models\PrintProfileAssetsCategoryRel();
					$profileCatRelDetails = $profileCatRelObj->where(
						[
							'asset_type_id' => $assetTypeArr['asset_type_id'],
						]
					)
						->where('print_profile_id', $printProfileKey)
						->get();

					$relCatIds = [];
					foreach ($profileCatRelDetails->toArray() as $value) {
						$relCatIds[] = $value['category_id'];
					}
					$categoryId = json_clean_encode($relCatIds);
				}
				// Filter by Category IDs and It's Subcategory IDs
				if (!empty($categoryId)) {
					$searchCategories = json_clean_decode($categoryId, true);
					$getCliparts->whereHas(
						'clipartCategory', function ($q) use ($searchCategories) {
							return $q->whereIn('category_id', $searchCategories);
						}
					);
				}
				// Total records including all filters
				$getTotalPerFilters = $getCliparts->count();
				// Pagination Data
				$offset = 0;
				if (!empty($page)) {
					$totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
					$offset = $totalItem * ($page - 1);
					$getCliparts->skip($offset)->take($totalItem);
				}
				// Sorting All records by column name and sord order parameter
				if (!empty($sortBy) && !empty($order)) {
					$getCliparts->orderBy($sortBy, $order);
				}
				if ($getTotalPerFilters > 0) {

					if ($source == 'admin') {
						$cliparts = $getCliparts->get();
						$jsonResponse = [
							'status' => 1,
							'records' => count($cliparts),
							'total_records' => $getTotalPerFilters,
							// Convert object to Array
							'data' => $cliparts->toArray(),
						];
					} else {
						$clipartsTool = [];
						$databaseStoreInfo = DB::table('stores')->where('xe_id', '=', $getStoreDetails['store_id']);
						if ($databaseStoreInfo->count() > 0) {
							$storeData = $databaseStoreInfo->get()->toArray();
							$storeDataArray = (array) $storeData[0];
							$currentStoreUrl = $storeDataArray['store_url'];
						}
						$cliparts = $getCliparts->get();
						foreach ($cliparts->toArray() as $key => $value) {
							$file_name = $value['file_name'];
							$thumbnail = $value['thumbnail'];
							$hostname = parse_url($file_name, PHP_URL_HOST); //hostname
							$value['file_name'] = str_replace($hostname, $currentStoreUrl, $file_name);
							$value['thumbnail'] = str_replace($hostname, $currentStoreUrl, $thumbnail);
							$clipartsTool[] = $value;
						}
						$jsonResponse = [
							'status' => 1,
							'records' => count($clipartsTool),
							'total_records' => $getTotalPerFilters,
							// Convert object to Array
							'data' => $clipartsTool,
						];
					}

				}
			}
			//exit;
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * PUT: Update a single clipart
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   13 Aug 2019
	 * @return json
	 */
	public function updateClipart($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Clipart', 'error'),
		];
		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);
		$allPostPutVars = $updateData = $request->getParsedBody();

		if (!empty($args['id'])) {
			$clipartId = to_int($args['id']);

			$clipartInit = new Clipart();
			$getOldClipart = $clipartInit->where('xe_id', $clipartId);
			if ($getOldClipart->count() > 0) {
				unset(
					$updateData['id'], $updateData['tags'],
					$updateData['categories'], $updateData['upload'],
					$updateData['clipartId']
				);

				// delete old file
				$this->deleteOldFile(
					'cliparts', 'file_name', ['xe_id' => $clipartId], path(
						'abs', 'vector'
					)
				);

				$getUploadedFileName = do_upload(
					'upload', path('abs', 'vector'), [100], 'string'
				);

				if (!empty($getUploadedFileName)) {
					$updateData['file_name'] = $getUploadedFileName;
				}

				$updateData['store_id'] = $getStoreDetails['store_id'];
				// Update record into the database
				try {
					$clipartInit = new Clipart();
					$clipartInit->where('xe_id', '=', $clipartId)
						->update($updateData);
					$jsonResponse = [
						'status' => 1,
						'message' => message('Clipart', 'updated'),
					];
					/**
					 * Save category and subcategory data
					 * Category id format: [4,78,3]
					 */
					if (isset($allPostPutVars['categories'])
						&& $allPostPutVars['categories'] != ""
					) {
						$categoryIds = $allPostPutVars['categories'];
						$this->saveClipartCategories($clipartId, $categoryIds);
					}
					/**
					 * Save tags with respect to the cliparts
					 * Tag Names format : tag1,tag2,tag3
					 */
					$tags = !empty($allPostPutVars['tags'])
					? $allPostPutVars['tags'] : "";
					$this->saveClipartTags(
						$getStoreDetails['store_id'], $clipartId, $tags
					);
				} catch (\Exception $e) {
					$serverStatusCode = EXCEPTION_OCCURED;
					create_log(
						'clipart', 'error',
						[
							'message' => $e->getMessage(),
							'extra' => [
								'module' => 'Updating a clipart',
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
	 * Delete: Delete a clipart along with all the tags and categories
	 *
	 * @param $request  Slim's Argument parameters
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author tanmayap@riaxe.com
	 * @date   13 Aug 2019
	 * @return json
	 */
	public function deleteClipart($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Clipart', 'error'),
		];
		if (!empty($args)) {
			// Multiple Ids in json format
			$getDeleteIds = trim($args['id']);
			$getDeleteIdsToArray = json_clean_decode($getDeleteIds, true);
			$totalCount = count($getDeleteIdsToArray);
			if (!empty($getDeleteIdsToArray)) {
				$clipartInit = new Clipart();
				$clipartCount = $clipartInit->whereIn('xe_id', $getDeleteIdsToArray)
					->count();
				if ($clipartCount > 0) {
					try {
						$success = 0;
						foreach ($getDeleteIdsToArray as $clipartId) {
							// Delete from Directory
							$this->deleteOldFile(
								'cliparts', 'file_name', [
									'xe_id' => $clipartId,
								], path('abs', 'vector')
							);
							$clipartInit->where('xe_id', $clipartId)->delete();
							$success++;
						}
						$jsonResponse = [
							'status' => 1,
							'message' => $success . ' out of ' . $totalCount .
							' Clipart(s) deleted successfully',
						];
					} catch (\Exception $e) {
						$serverStatusCode = EXCEPTION_OCCURED;
						create_log(
							'clipart', 'error',
							[
								'message' => $e->getMessage(),
								'extra' => [
									'module' => 'Deleting a clipart',
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

	/**
	 * Save Tags and Clipart-Tag Relations
	 *
	 * @param $storeId   Store Id
	 * @param $clipartId Clipart's ID
	 * @param $tags      Tags(in comma separated)
	 *
	 * @author tanmayap@riaxe.com
	 * @date   13 Aug 2019
	 * @return boolean
	 */
	public function saveClipartTags($storeId, $clipartId, $tags) {
		// Save Clipart and tags relation
		if (!empty($tags)) {
			$getTagIds = $this->saveTags($storeId, $tags);
			// SYNC Tags into Clipart Tag Relationship Table
			$clipartInit = new Clipart();
			$findClipart = $clipartInit->find($clipartId);
			if ($findClipart->tags()->sync($getTagIds)) {
				return true;
			}
		} else {
			// Clean relation in case no tags supplied
			$tagRelInit = new ClipartTagRelation();
			$clipartTags = $tagRelInit->where('clipart_id', $clipartId);
			if ($clipartTags->delete()) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Save Categories/Sub-categories and Clipart-Category Relations
	 *
	 * @param $clipartId   Clipart's ID
	 * @param $categoryIds (in comma separated)
	 *
	 * @author tanmayap@riaxe.com
	 * @date   13 Aug 2019
	 * @return boolean
	 */
	public function saveClipartCategories($clipartId, $categoryIds) {
		$getAllCategoryArr = json_clean_decode($categoryIds, true);
		// SYNC Categories to the Clipart_Category Relationship Table
		$clipartInit = new Clipart();
		$findClipart = $clipartInit->find($clipartId);
		if ($findClipart->categories()->sync($getAllCategoryArr)) {
			return true;
		}

		return false;
	}

	/**
	 * Delete a category from the table
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   20 Jan 2020
	 * @return Delete Json Status
	 */
	public function deleteCategory($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Category', 'error'),
		];
		if (!empty($args)) {
			$categoryId = $args['id'];
			$jsonResponse = $this->deleteCat(
				'cliparts', $categoryId, 'Cliparts', 'ClipartCategoryRelation'
			);
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * Get most used cliparts
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author debashrib@riaxe.com
	 * @date   30 Jan 2020
	 * @return A json
	 */
	public function mostUsedCliparts($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Cliparts', 'error'),
		];
		$getStoreDetails = get_store_details($request);
		$page = $request->getQueryParam('page');
		$perpage = $request->getQueryParam('perpage');
		$clipartInit = new Clipart();
		$getCliparts = $clipartInit->where(
			['store_id' => $getStoreDetails['store_id']]
		)
			->select('xe_id', 'name', 'file_name');
		$totalCounts = $getCliparts->count();
		if ($totalCounts > 0) {
			// Get pagination data
			$offset = 0;
			if (!empty($page)) {
				$totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
				$offset = $totalItem * ($page - 1);
				$getCliparts->skip($offset)->take($totalItem);
			}
			$clipartsData = $getCliparts->orderBy('total_used', 'DESC')
				->get();
			$clipartsDataArr = json_clean_decode($clipartsData, true);
			$jsonResponse = [
				'status' => 1,
				'total_records' => $totalCounts,
				'records' => count($clipartsData),
				'data' => $clipartsData,
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Get all Categories in Recursion format from the Database
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author radhanatham@riaxe.com
	 * @date   10 Spt 2020
	 * @return json
	 */
	private function getCategoryByPrintProfileId($request, $response, $args) {
		//All Filter columns from url
		$getStoreDetails = get_store_details($request);

		$categoryId = $request->getQueryParam('category');
		$page = $request->getQueryParam('cat_page');
		$perpage = $request->getQueryParam('cat_perpage');
		$sortBy = (
			!empty($request->getQueryParam('sortby'))
			&& $request->getQueryParam('sortby') != null
		) ? $request->getQueryParam('sortby') : 'category_id';
		$order = (
			!empty($request->getQueryParam('cat_order'))
			&& $request->getQueryParam('cat_order') != null
		) ? $request->getQueryParam('cat_order') : 'desc';
		$printProfileId = $request->getQueryParam('print_profile_id');
		$primaryCategory = (
			!empty($request->getQueryParam('primary_cat'))
			&& $request->getQueryParam('primary_cat') != null
		) ? $request->getQueryParam('primary_cat') : 0;
		$name = $request->getQueryParam('name');

		$clipartsCategory = $clipartsCategories = $primaryCategoryArr = $getclipartsCategories = $primaryCategoryData = $clipartsCategoriesData = [];
		if ($printProfileId > 0) {
			$moduleSlugName = 'cliparts';
			// Getting Assets module id
			$assetTypeArr = $this->assetsTypeId($moduleSlugName);
			if (!empty($assetTypeArr) && $assetTypeArr['status'] == 1) {
				$assetTypeId = $assetTypeArr['asset_type_id'];

				if (isset($printProfileId) && $printProfileId > 0) {
					if (!empty($primaryCategory) && $primaryCategory && $page == 1) {

						$primaryProfileCat = DB::table('print_profile_assets_category_rel as ppac')
							->leftjoin('clipart_category_rel as ccr', 'ppac.category_id', '=', 'ccr.category_id')
							->join('categories as cat', 'ppac.category_id', '=', 'cat.xe_id')
							->join('cliparts as c', 'ccr.clipart_id', '=', 'c.xe_id')
							->leftjoin('clipart_tag_rel as ct', 'c.xe_id', '=', 'ct.clipart_id')
							->leftjoin('tags as t', 'ct.tag_id', '=', 't.xe_id')
							->where('ppac.asset_type_id', $assetTypeId)
							->where('ppac.print_profile_id', $printProfileId)
							->where('ppac.category_id', $primaryCategory)
							->select('cat.xe_id as id', 'cat.parent_id', 'cat.name', 'cat.sort_order', 'cat.is_disable', 'cat.is_default')->distinct('cat.xe_id');
						$getPrimayTotalPerFilters = $primaryProfileCat->get()->count();

						if ($getPrimayTotalPerFilters > 0) {
							$primaryCategories = $primaryProfileCat->get();

							$clipartsPrimaryCategory = $primaryCategories->toArray();

							$primaryCategoryData[$clipartsPrimaryCategory[0]->id] = [
								'id' => $clipartsPrimaryCategory[0]->id,
								'name' => $clipartsPrimaryCategory[0]->name,
								'order' => $clipartsPrimaryCategory[0]->sort_order,
								'is_disable' => $clipartsPrimaryCategory[0]->is_disable,
								'is_default' => $clipartsPrimaryCategory[0]->is_default,
							];
						}

						if (is_array($primaryCategoryData) && count($primaryCategoryData) > 0) {
							$primaryCategoryArr = array_values($primaryCategoryData);
						}
					}

					$profileCat = DB::table('print_profile_assets_category_rel as ppac')
						->leftjoin('clipart_category_rel as ccr', 'ppac.category_id', '=', 'ccr.category_id')
						->join('categories as cat', 'ppac.category_id', '=', 'cat.xe_id')
						->join('cliparts as c', 'ccr.clipart_id', '=', 'c.xe_id')
						->leftjoin('clipart_tag_rel as ct', 'c.xe_id', '=', 'ct.clipart_id')
						->leftjoin('tags as t', 'ct.tag_id', '=', 't.xe_id')
						->where('ppac.asset_type_id', $assetTypeId)->where('ppac.print_profile_id', $printProfileId)->where('c.store_id', $getStoreDetails['store_id'])->where('cat.store_id', $getStoreDetails['store_id']);
					if (!empty($categoryId)) {
						$searchCategories = json_clean_decode($categoryId, true);
						if ($searchCategories != '') {
							$profileCat = $profileCat->whereIn('ppac.category_id', $searchCategories);
						}
					}
					if (isset($name) && $name != "") {
						$profileCat->where(
							function ($query) use ($name) {
								$query->where('c.name', 'LIKE', '%' . $name . '%')
									->orWhere('cat.name', 'LIKE', '%' . $name . '%')
									->orWhere('t.name', 'LIKE', '%' . $name . '%');
							}
						);
					}
					//echo '<pre>';print_r($primaryCategoryArr);exit;
					$profileCatRel = $profileCat->select('cat.xe_id as id', 'cat.parent_id', 'cat.name', 'cat.sort_order', 'cat.is_disable', 'cat.is_default')->distinct('cat.xe_id');
					$getTotalPerFilters = $profileCatRel->get()->count();
					//echo $getTotalPerFilters;exit;
					$offset = 0;
					if (!empty($page)) {
						$totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
						$offset = $totalItem * ($page - 1);
						$profileCatRel->orderBy('sort_order', 'asc')->skip($offset)->take($totalItem);
					}
					if ($getTotalPerFilters > 0) {
						$profileCategory = $profileCatRel->get();
						$profileCatRelDetails = $profileCategory->toArray();
						foreach ($profileCatRelDetails as $value) {
							$parentDetails[$value->id] = [
								'id' => $value->id,
								'name' => $value->name,
								'order' => $value->sort_order,
								'is_disable' => $value->is_disable,
								'is_default' => $value->is_default,
							];
						}
						$clipartsCategories['records'] = count($profileCategory);
						$clipartsCategories['total_records'] = $getTotalPerFilters;
						if (is_array($parentDetails) && count($parentDetails) > 0) {
							$clipartsCategoriesData = array_values($parentDetails);
						}
						$allClipartCategory = array_merge($primaryCategoryArr, $clipartsCategoriesData);
						$clipartsCategories['data'] = array_map("unserialize", array_unique(array_map("serialize", $allClipartCategory)));
					}
				}
			}
		}
		return $clipartsCategories;
	}
}
