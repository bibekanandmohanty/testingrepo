<?php
/**
 * Manage Shapes
 *
 * PHP version 5.6
 *
 * @category  Shape
 * @package   Eloquent
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Shapes\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\Shapes\Models\Shape;
use App\Modules\Shapes\Models\ShapeCategory as Category;
use App\Modules\Shapes\Models\ShapeTagRelation;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Shape Controller
 *
 * @category Class
 * @package  Shape
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ShapeController extends ParentController {
	/**
	 * POST: Save Shape
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4th Nov 2019
	 * @return json
	 */
	public function saveShapes($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Shapes', 'error'),
		];
		$success = 0;
		$saveShapeList = [];
		$allFileNames = [];

		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);
		$allPostPutVars = $request->getParsedBody();
		// Save file if request contain files
		$allFileNames = do_upload('upload', path('abs', 'shape'), [150], 'array');
		if (!empty($allFileNames)) {
			foreach ($allFileNames as $eachFile) {
				$lastInsertId = 0;
				$saveShapeList = [];
				if (!empty($eachFile)) {
					$saveShapeList = [
						'store_id' => $getStoreDetails['store_id'],
						'name' => $allPostPutVars['name'],
						'file_name' => $eachFile,
					];
					$saveEachShape = new Shape($saveShapeList);
					if ($saveEachShape->save()) {
						$lastInsertId = $saveEachShape->xe_id;
						/**
						 * Save category and subcategory data
						 * Category id format: [4,78,3]
						 */
						if (!empty($allPostPutVars['categories'])) {
							$categoryIds = $allPostPutVars['categories'];
							$this->saveShapeCategories(
								$lastInsertId, $categoryIds
							);
						}
						/**
						 * Save tags
						 * Tag Names format : tag1,tag2,tag3
						 */
						$tags = !empty($allPostPutVars['tags'])
						? $allPostPutVars['tags'] : "";
						$this->saveShapeTags(
							$getStoreDetails['store_id'], $lastInsertId, $tags
						);
						$success++;
					}
				}
			}
		}
		if (!empty($success) && $success > 0) {
			$jsonResponse = [
				'status' => 1,
				'message' => $success . ' out of ' . $uploadingFilesNo
				. ' Shape(s) uploaded successfully',
			];
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Save Categories/Sub-categories and Shape-Category Relations
	 *
	 * @param $shapeId     Shape ID
	 * @param $categoryIds (in  an array with comma separated)
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4th Nov 2019
	 * @return boolean
	 */
	protected function saveShapeCategories($shapeId, $categoryIds) {
		$getAllCategoryArr = json_clean_decode($categoryIds, true);
		// SYNC Categories to the Shape_Category Relationship Table
		$shapeInit = new Shape();
		$findShape = $shapeInit->find($shapeId);
		if ($findShape->categories()->sync($getAllCategoryArr)) {
			return true;
		}
		return false;
	}

	/**
	 * Save Tags and Shape-Tag Relations
	 *
	 * @param $storeId Shape ID
	 * @param $shapeId Shape ID
	 * @param $tags    Multiple Tags
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4th Nov 2019
	 * @return boolean
	 */
	protected function saveShapeTags($storeId, $shapeId, $tags) {
		// Save Shape and tags relation
		if (!empty($tags)) {
			$getTagIds = $this->saveTags($storeId, $tags);
			// SYNC Tags into Relationship Table
			$shapeInit = new Shape();
			$findShape = $shapeInit->find($shapeId);
			if ($findShape->tags()->sync($getTagIds)) {
				return true;
			}
		} else {
			// Clean relation in case no tags supplied
			$tagRelInit = new ShapeTagRelation();
			$shapeTags = $tagRelInit->where('shape_id', $shapeId);
			if ($shapeTags->delete()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * GET: List of Shape
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4th Nov 2019
	 * @return All/Single Shape List
	 */
	public function getShapes($request, $response, $args) {
        $serverStatusCode = OPERATION_OKAY;
        $shapeData = [];
        $jsonResponse = [
            'status' => 0,
            'data' => [],
            'message' => message('Shapes', 'not_found'),
        ];
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $shapeInit = new Shape();
        $getShapes = $shapeInit->where('xe_id', '>', 0)
            ->where('store_id', '=', $getStoreDetails['store_id']);

        if (!empty($args)) {
            $shapeId = to_int($args['id']);
            //For single Shape data
            $shapeData = $getShapes->where('xe_id', $shapeId)->first();
            // Get Category Ids
            $getCategories = $this->getCategoriesById(
                'Shapes', 'ShapeCategoryRelation', 'shape_id', $shapeId
            );
            $getTags = $this->getTagsById(
                'Shapes', 'ShapeTagRelation', 'shape_id', $shapeId
            );
            $shapeData['categories'] = $getCategories;
            $shapeData['tags'] = $getTags;
            // Unset category_name Key in case of single record fetch
            $shapeData = json_clean_decode($shapeData, true);
            unset($shapeData['category_names']);
            $jsonResponse = [
                'status' => 1,
                'data' => [
                    $shapeData,
                ],
            ];
        } else {
            //All Filter columns from url
            $page = $request->getQueryParam('page');
            $perpage = $request->getQueryParam('perpage');
            $categoryId = $request->getQueryParam('category');
            $sortBy = !empty($request->getQueryParam('sortby'))
            && $request->getQueryParam('sortby') != ""
            ? $request->getQueryParam('sortby') : 'xe_id';
            $order = !empty($request->getQueryParam('order'))
            && $request->getQueryParam('order') != ""
            ? $request->getQueryParam('order') : 'desc';
            $name = $request->getQueryParam('name');
            $printProfileKey = $request->getQueryParam('print_profile_id');
            $type = (
                !empty($request->getQueryParam('type'))
                && $request->getQueryParam('type') != null
            ) ? $request->getQueryParam('type') : '';
            $source = $request->getQueryParam('source') ? $request->getQueryParam('source') : '';
            if ($type == 'tool') {

            	$currentStoreUrl = '';
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
                $shapesCategoryList = $this->getCategoryByPrintProfileId($request, $response, $args);
                if (!empty($shapesCategoryList) && !empty($shapesCategoryList['data'])) {
                    $records = $shapesCategoryList['records'];
                    $totalRecords = $shapesCategoryList['total_records'];
                    $i = 0;
                    $categoriesList = [];
                    foreach ($shapesCategoryList['data'] as $key => $category) {
                        // Filter by Category IDs and It's Subcategory IDs
                        if (!empty($category['id'])) {
                            $shapeInit = new Shape();
                            $getShapes = $shapeInit->where('xe_id', '>', 0)->where('store_id', '=', $getStoreDetails['store_id']);
                            $getShapes->select(
                                'xe_id', 'name', 'file_name'
                            );
                            $searchCategories = $category['id'];
                            $getShapes->whereHas(
                                'shapeCategory', function ($q) use ($searchCategories) {
                                    return $q->where('category_id', $searchCategories);
                                }
                            );
                            
                            $getTotalPerFilters = $getShapes->count();
                            // Pagination Data
                            $offset = 0;
                            if (!empty($page)) {
                                $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
                                $offset = $totalItem * ($page - 1);
                                $getShapes->skip($offset)->take($totalItem);
                            }
                            // Sorting All records by column name and sord order parameter
                            if (!empty($sortBy) && !empty($order)) {
                                $getShapes->orderBy($sortBy, $order);
                            }
                            

                            if ($getStoreDetails['store_id'] > 1 && !empty($currentStoreUrl) && $source != 'admin'){
                            	foreach ($shapes->toArray() as $shapesKey => $shapesValue){
                            		$hostname = parse_url($shapesValue['file_name'], PHP_URL_HOST); //hostname
                            		$shapes[$shapesKey]['file_name'] = str_replace($hostname, $currentStoreUrl, $shapesValue['file_name']);
									
                            	}

                            } else {
                            	$shapes = $getShapes->get();
                            }
                            //$shapes = $getShapes->get();
                            $categoriesList[$i]['id'] = $category['id'];
                            $categoriesList[$i]['name'] = $category['name'];
                            $categoriesList[$i]['order'] = $category['order'];
                            $categoriesList[$i]['is_disable'] = $category['is_disable'];
                            $categoriesList[$i]['is_default'] = $category['is_default'];
                            $categoriesList[$i]['shapes'] = $shapes->toArray();
                            $categoriesList[$i]['records'] = count($shapes);
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
                $offset = 0;
                // For multiple Shape data
                $getShapes->select('xe_id', 'name', 'file_name');
                // Searching as per name, category name & tag name
                if (isset($name) && $name != "") {
                    $name = '\\' . $name;
                    $getShapes->where(
                        function ($query) use ($name) {
                            $query->where('name', 'LIKE', '%' . $name . '%')
                                ->orWhereHas(
                                    'shapeTags.tag', function ($q) use ($name) {
                                        return $q->where(
                                            'name', 'LIKE', '%' . $name . '%'
                                        );
                                    }
                                )->orWhereHas(
                                'shapeCategory.category', function ($q) use ($name) {
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
                    $profileCatRelObj = new \App\Modules\PrintProfiles\Models\PrintProfileAssetsCategoryRel();
                    $assetTypeArr = $this->assetsTypeId('shapes');
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
                // Filter by Category ID
                if (!empty($categoryId)) {
                    $searchCategories = json_clean_decode($categoryId, true);
                    $getShapes->whereHas(
                        'shapeCategory', function ($q) use ($searchCategories) {
                            return $q->whereIn('category_id', $searchCategories);
                        }
                    );
                }
                // Total records including all filters
                $getTotalPerFilters = $getShapes->count();
                // Pagination Data
                if (isset($page) && $page != "") {
                    $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
                    $offset = $totalItem * ($page - 1);
                    $getShapes->skip($offset)->take($totalItem);
                }
                // Sorting All records by column name and sord order parameter
                if (isset($sortBy) && $sortBy != "" && isset($order) && $order != "") {
                    $getShapes->orderBy($sortBy, $order);
                }
                $shapeData = $getShapes->get();
                $jsonResponse = [
                    'status' => 1,
                    'records' => count($shapeData),
                    'total_records' => $getTotalPerFilters,
                    'data' => $shapeData,
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
	}

	/**
	 * PUT: Update a Single Shape
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4th Nov 2019
	 * @return json
	 */
	public function updateShape($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Shape', 'not_found'),
		];
		$allPostPutVars = $updateData = $request->getParsedBody();
		// Get Store Specific Details from helper
		$getStoreDetails = get_store_details($request);
		if (!empty($args['id'])) {
			$shapeId = $args['id'];
			$shapeInit = new Shape();
			$getOldShape = $shapeInit->where('xe_id', '=', $shapeId);
			if ($getOldShape->count() > 0) {
				unset(
					$updateData['id'], $updateData['tags'],
					$updateData['categories'], $updateData['upload'],
					$updateData['shapeId']
				);

				// Delete old file if exist
				$this->deleteOldFile(
					"shapes", "file_name", [
						'xe_id' => $shapeId,
					], path('abs', 'shape')
				);
				$getUploadedFileName = do_upload('upload', path('abs', 'shape'), [150], 'string');
				if (!empty($getUploadedFileName)) {
					$updateData += ['file_name' => $getUploadedFileName];
				}
				$updateData += ['store_id' => $getStoreDetails['store_id']];
				// Update record
				try {
					$shapeInit = new Shape();
					$shapeInit->where('xe_id', '=', $shapeId)->update($updateData);
					/**
					 * Save category and subcategory data
					 * Category id format: [4,78,3]
					 */
					if (isset($allPostPutVars['categories'])
						&& $allPostPutVars['categories'] != ""
					) {
						$categoryIds = $allPostPutVars['categories'];
						$this->saveShapeCategories($shapeId, $categoryIds);
					}
					/**
					 * Save tags
					 * Tag Names format : tag1,tag2,tag3
					 */
					$tags = !empty($allPostPutVars['tags'])
					? $allPostPutVars['tags'] : "";
					$this->saveShapeTags(
						$getStoreDetails['store_id'], $shapeId, $tags
					);
					$jsonResponse = [
						'status' => 1,
						'message' => message('Shape', 'updated'),
					];
				} catch (\Exception $e) {
					// Store exception in logs
					create_log(
						'shapes', 'error',
						[
							'message' => $e->getMessage(),
							'extra' => [
								'module' => 'Shapes',
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
	 * DELETE: Delete single/multiple Shape
	 *
	 * @param $request  Slim's Argument parameters
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4th Nov 2019
	 * @return json
	 */
	public function deleteShape($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Shape', 'not_found'),
		];
		if (!empty($args)) {
			$getDeleteIds = $args['id'];
			$getDeleteIdsToArray = json_clean_decode($getDeleteIds, true);
			$totalCount = count($getDeleteIdsToArray);
			if (!empty($getDeleteIdsToArray) && $totalCount > 0) {
				$shapeInit = new Shape();
				$shapesCount = $shapeInit->whereIn('xe_id', $getDeleteIdsToArray)
					->count();
				if ($shapesCount > 0) {
					try {
						$success = 0;
						foreach ($getDeleteIdsToArray as $shapeId) {
							$this->deleteOldFile(
								"shapes", "file_name", [
									'xe_id' => $shapeId,
								], path('abs', 'shape')
							);
							$shapeInit->where('xe_id', $shapeId)->delete();
							$success++;
						}
						if ($success > 0) {
							$jsonResponse = [
								'status' => 1,
								'message' => $success . ' out of '
								. $totalCount . ' Shape(s) deleted successfully',
							];
						}
					} catch (\Exception $e) {
						$serverStatusCode = EXCEPTION_OCCURED;
						create_log(
							'Assets', 'error',
							[
								'message' => $e->getMessage(),
								'extra' => [
									'module' => 'Shapes',
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
				'shapes', $categoryId, 'Shapes', 'ShapeCategoryRelation'
			);
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
	 * @date   10 Sept 2020
	 * @return json
	 */
	private function getCategoryByPrintProfileId($request, $response, $args) {
		//All Filter columns from url
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
		// $printProfileId = $request->getQueryParam('print_profile_id');
		$primaryCategory = (
			!empty($request->getQueryParam('primary_cat'))
			&& $request->getQueryParam('primary_cat') != null
		) ? $request->getQueryParam('primary_cat') : 0;
		$name = $request->getQueryParam('name');

		$shapesCategories = $primaryCategoryArr = $getShapesCategories = $primaryCategoryData = [];
		// if ($printProfileId > 0) {
			$moduleSlugName = 'shapes';
			// Getting Assets module id
			$assetTypeArr = $this->assetsTypeId($moduleSlugName);
			if (!empty($assetTypeArr) && $assetTypeArr['status'] == 1) {
				$assetTypeId = $assetTypeArr['asset_type_id'];
				// if (isset($printProfileId) && $printProfileId > 0) {
					if (!empty($primaryCategory) && $primaryCategory && $page == 1) {
						$profileCat = DB::table('print_profile_assets_category_rel as ppac')
							->leftjoin('shape_category_rel as scr', 'ppac.category_id', '=', 'scr.category_id')
							->join('categories as cat', 'ppac.category_id', '=', 'cat.xe_id')
							->join('shapes as s', 'scr.shape_id', '=', 's.xe_id')
							->leftjoin('shape_tag_rel as st', 's.xe_id', '=', 'st.shape_id')
							->leftjoin('tags as t', 'st.tag_id', '=', 't.xe_id')
							->where('ppac.asset_type_id', $assetTypeId);
							// ->where('ppac.print_profile_id', $printProfileId)->where('ppac.category_id', $primaryCategory);
						$profileCatRel = $profileCat->select('cat.xe_id as id', 'cat.parent_id', 'cat.name', 'cat.sort_order', 'cat.is_disable', 'cat.is_default')->distinct('cat.xe_id');
						$getTotalPerFilters = $profileCatRel->get()->count();
						if ($getTotalPerFilters > 0) {
							$categories = $profileCatRel->get();
							$clipartsPrimaryCategory = $categories->toArray();
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
						->leftjoin('shape_category_rel as scr', 'ppac.category_id', '=', 'scr.category_id')
						->join('categories as cat', 'ppac.category_id', '=', 'cat.xe_id')
						->join('shapes as s', 'scr.shape_id', '=', 's.xe_id')
						->leftjoin('shape_tag_rel as st', 's.xe_id', '=', 'st.shape_id')
						->leftjoin('tags as t', 'st.tag_id', '=', 't.xe_id');
						// ->where('ppac.asset_type_id', $assetTypeId)->where('ppac.print_profile_id', $printProfileId);
					if (!empty($categoryId)) {
						$searchCategories = json_clean_decode($categoryId, true);
						if ($searchCategories != '') {
							$profileCat = $profileCat->whereIn('ppac.category_id', $searchCategories);
						}
					}
					if (isset($name) && $name != "") {
						$profileCat->where(
							function ($query) use ($name) {
								$query->where('s.name', 'LIKE', '%' . $name . '%')
									->orWhere('cat.name', 'LIKE', '%' . $name . '%')
									->orWhere('t.name', 'LIKE', '%' . $name . '%');
							}
						);
					}
					$profileCatRel = $profileCat->select('cat.xe_id as id', 'cat.parent_id', 'cat.name', 'cat.sort_order', 'cat.is_disable', 'cat.is_default')->distinct('cat.xe_id');
					$getTotalPerFilters = $profileCatRel->get()->count();
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
						$shapesCategories['records'] = count($profileCategory);
						$shapesCategories['total_records'] = $getTotalPerFilters;
						if (is_array($parentDetails) && count($parentDetails) > 0) {
							$getShapesCategories = array_values($parentDetails);
						}
						$allShapeCategory = array_merge($primaryCategoryArr, $getShapesCategories);
						$shapesCategories['data'] = array_map("unserialize", array_unique(array_map("serialize", $allShapeCategory)));
					}
				// }
			}
		// }
		return $shapesCategories;
	}
}
