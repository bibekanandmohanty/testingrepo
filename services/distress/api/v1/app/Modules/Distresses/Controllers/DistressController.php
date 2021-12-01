<?php
/**
 * Manage Distresses
 *
 * PHP version 5.6
 *
 * @category  Distress
 * @package   Eloquent
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Distresses\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\Distresses\Models\Distress;
use App\Modules\Distresses\Models\DistressCategoryRelation;
use App\Modules\Distresses\Models\DistressTagRelation;

/**
 * Distress Controller
 *
 * @category Class
 * @package  Distress
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class DistressController extends ParentController
{

    /**
     * POST: Save Distress
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return json response wheather data is saved or any error occured
     */
    public function saveDistresses($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $success = 0;
        $saveDistressList = [];
        $allFileNames = [];
        $jsonResponse = [
            'status' => 0,
            'message' => message('Distresses', 'error'),
        ];
        // Get Store Specific Details
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        // Save file if request contain files
        $allFileNames = do_upload(
            'upload', path('abs', 'distress'), [150], 'array'
        );
        $uploadingFilesNo = count($allFileNames);
        if (!empty($allFileNames)) {
            foreach ($allFileNames as $eachFile) {
                $lastInsertId = 0;
                $saveDistressList = [];
                if (!empty($eachFile)) {
                    $saveDistressList = [
                        'store_id' => $getStoreDetails['store_id'],
                        'name' => $allPostPutVars['name'],
                        'file_name' => $eachFile,
                    ];
                    $saveEachDistress = new Distress($saveDistressList);
                    if ($saveEachDistress->save()) {
                        $lastInsertId = $saveEachDistress->xe_id;
                        /**
                         * Save category and subcategory data
                         * Category id format: [4,78,3]
                         */
                        if (!empty($allPostPutVars['categories'])) {
                            $categoryIds = $allPostPutVars['categories'];
                            $this->saveDistressCategories(
                                $lastInsertId, $categoryIds
                            );
                        }
                        /**
                         * Save tags
                         * Tag Names format : tag1,tag2,tag3
                         */
                        $tags = !empty($allPostPutVars['tags']) 
                            ? $allPostPutVars['tags'] : "";
                        $this->saveDistressTags(
                            $getStoreDetails['store_id'], $lastInsertId, $tags
                        );
                        $success++;
                    }
                }
            }
            if (!empty($success)) {
                $jsonResponse = [
                    'status' => 1,
                    'message' => $success . ' out of ' . $uploadingFilesNo
                        . ' Distress(es) uploaded successfully',
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Save Categories/Sub-categories and Distress-Category Relations
     *
     * @param $distressId  Distress ID
     * @param $categoryIds (in  an array with comma separated)
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return boolean
     */
    protected function saveDistressCategories($distressId, $categoryIds)
    {
        $getAllCategoryArr = json_clean_decode($categoryIds, true);
        // SYNC Categories to the Distress_Category Relationship Table
        $distressInit = new Distress();
        $findDistress = $distressInit->find($distressId);
        if ($findDistress->categories()->sync($getAllCategoryArr)) {
            return true;
        }
        return false;
    }

    /**
     * Save Tags and Distress-Tag Relations
     *
     * @param $storeId    Store ID
     * @param $distressId Distress ID
     * @param $tags       Multiple Tags
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return boolean
     */
    protected function saveDistressTags($storeId, $distressId, $tags)
    {
        // Save Distress and tags relation
        if (!empty($tags)) {
            $getTagIds = $this->saveTags($storeId, $tags);
            // SYNC Tags into Relationship Table
            $distressInit = new Distress();
            $findDistress = $distressInit->find($distressId);
            if ($findDistress->tags()->sync($getTagIds)) {
                return true;
            }
        } else {
            // Clean relation in case no tags supplied
            $tagRelInit = new DistressTagRelation();
            $distressTags = $tagRelInit->where('distress_id', $distressId);
            if ($distressTags->delete()) {
                return true;
            }
        }
        return false;
    }

    /**
     * GET: List of Distress
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return All/Single Distress List
     */
    public function getDistresses($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $distressData = [];
        $jsonResponse = [
            'status' => 0,
            'data' => [],
            'message' => message('Distress', 'not_found'),
        ];
        // Get Store Specific Details
        $getStoreDetails = get_store_details($request);
        $distressInit = new Distress();
        $getDistresses = $distressInit->where('xe_id', '>', 0)
            ->where('store_id', '=', $getStoreDetails['store_id']);
        $distressId = to_int($args['id']);
        // Total records irrespectable of filters
        $totalCounts = $getDistresses->count();
        if ($totalCounts > 0) {
            if (!empty($distressId)) {
                //For single Distress data
                $distressData = $getDistresses->where('xe_id', '=', $distressId)
                    ->first();
                $getCategories = $this->getCategoriesById(
                    'Distresses', 'DistressCategoryRelation', 'distress_id', $distressId
                );
                $getTags = $this->getTagsById(
                    'Distresses', 'DistressTagRelation', 'distress_id', $distressId
                );
                $distressData['categories'] = $getCategories;
                $distressData['tags'] = $getTags;
                $distressData = json_clean_decode($distressData, true);
                unset($distressData['category_names']);
                $jsonResponse = [
                    'status' => 1,
                    'data' => [
                        $distressData,
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
                
                $offset = 0;
                // For multiple Distress data
                $getDistresses->select('xe_id', 'name', 'file_name');
                // Searching as per name, category name & tag name
                if (!empty($name)) {
                    $name = '\\' . $name;
                    $getDistresses->where(
                        function ($query) use ($name) {
                            $query->where('name', 'LIKE', '%' . $name . '%')
                                ->orWhereHas(
                                    'distressTags.tag', function ($q) use ($name) {
                                        return $q->where(
                                            'name', 'LIKE', '%' . $name . '%'
                                        );
                                    }
                                )->orWhereHas(
                                    'distressCategory.category', function ($q) use ($name) {
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
                    $assetTypeArr = $this->assetsTypeId('distresses');
                    $profileCatRelDetails = $profileCatRelObj->where(
                        ['asset_type_id' => $assetTypeArr['asset_type_id']]
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
                    $getDistresses->whereHas(
                        'distressCategory', function ($q) use ($searchCategories) {
                            return $q->whereIn('category_id', $searchCategories);
                        }
                    );
                }
                // Total records including all filters
                $getTotalPerFilters = $getDistresses->count();
                // Pagination Data
                if (!empty($page)) {
                    $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
                    $offset = $totalItem * ($page - 1);
                    $getDistresses->skip($offset)->take($totalItem);
                }
                // Sorting All records by column name and sord order parameter
                if (!empty($sortBy) && !empty($order)) {
                    $getDistresses->orderBy($sortBy, $order);
                }
                $distressData = $getDistresses->get();
                $jsonResponse = [
                    'status' => 1,
                    'records' => count($distressData),
                    'total_records' => $getTotalPerFilters,
                    'data' => $distressData,
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * PUT: Update a single distress
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return json response wheather data is updated or not
     */
    public function updateDistress($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Distress', 'not_found'),
        ];
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $updateData = $request->getParsedBody();
        $distressId = to_int($args['id']);
        if (!empty($distressId)) {
            $distressInit = new Distress();
            $getOldDistress = $distressInit->where('xe_id', '=', $distressId);
            if ($getOldDistress->count() > 0) {
                unset(
                    $updateData['id'], $updateData['tags'],
                    $updateData['categories'], $updateData['upload'],
                    $updateData['distressId']
                );
                // Delete old file if exist
                $this->deleteOldFile(
                    "distresses", "file_name", [
                        'xe_id' => $distressId,
                    ], path('abs', 'distress')
                );
                $getUploadedFileName = do_upload(
                    'upload', path('abs', 'distress'), [150], 'string'
                );
                if (!empty($getUploadedFileName)) {
                    $updateData += ['file_name' => $getUploadedFileName];
                }
                $updateData += ['store_id' => $getStoreDetails['store_id']];
                // Update record into the database
                try {
                    $distressInit = new Distress();
                    $distressInit->where('xe_id', '=', $distressId)
                        ->update($updateData);
                    /**
                     * Save category and subcategory data
                     * Category id format: [4,78,3]
                     */
                    if (!empty($allPostPutVars['categories'])) {
                        $categoryIds = $allPostPutVars['categories'];
                        $this->saveDistressCategories($distressId, $categoryIds);
                    }
                    /**
                     * Save tags
                     * Tag Names format : tag1,tag2,tag3
                     */
                    $tags = !empty($allPostPutVars['tags']) 
                        ? $allPostPutVars['tags'] : "";
                    $this->saveDistressTags(
                        $getStoreDetails['store_id'], $distressId, $tags
                    );

                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Distress', 'updated'),
                    ];
                } catch (\Exception $e) {
                    // Store exception in logs
                    create_log(
                        'Assets', 'error',
                        [
                            'message' => $e->getMessage(),
                            'extra' => [
                                'module' => 'Distress',
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
     * DELETE: Delete single/multiple distress
     *
     * @param $request  Slim's Argument parameters
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return json response wheather data is deleted or not
     */
    public function deleteDistress($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Distress', 'not_found'),
        ];
        if (!empty($args['id'])) {
            $getDeleteIds = $args['id'];
            $getDeleteIdsToArray = json_clean_decode($getDeleteIds, true);
            $totalCount = count($getDeleteIdsToArray);
            if (is_array($getDeleteIdsToArray) && $totalCount > 0) {
                $distressInit = new Distress();
                if ($distressInit->whereIn('xe_id', $getDeleteIdsToArray)->count() > 0) {
                    try {
                        $success = 0;
                        foreach ($getDeleteIdsToArray as $distressId) {
                            // Delete from Database
                            $this->deleteOldFile(
                                "distresses", "file_name", [
                                    'xe_id' => $distressId,
                                ], path('abs', 'distress')
                            );
                            $distressDelInit = new Distress();
                            $distressDelInit->where('xe_id', $distressId)->delete();
                            $success++;
                        }
                        if ($success > 0) {
                            $jsonResponse = [
                                'status' => 1,
                                'message' => $success . ' out of ' . $totalCount 
                                    . ' Distress(es) deleted successfully',
                            ];
                        }
                    } catch (\Exception $e) {
                        // Store exception in logs
                        create_log(
                            'Assets', 'error',
                            [
                                'message' => $e->getMessage(),
                                'extra' => [
                                    'module' => 'Distress',
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
    public function deleteCategory($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Category', 'error'),
        ];
        if (!empty($args['id'])) {
            $categoryId = $args['id'];
            $jsonResponse = $this->deleteCat(
                'distresses', $categoryId, 'Distresses', 'DistressCategoryRelation'
            );
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}
