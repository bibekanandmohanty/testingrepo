<?php
/**
 * Manage Background Things to change
 *
 * PHP version 5.6
 *
 * @category  Backgrounds
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxex.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Backgrounds\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\Backgrounds\Models\Background;
use App\Modules\Backgrounds\Models\BackgroundCategory as Category;
use App\Modules\Backgrounds\Models\BackgroundTagRelation;

/**
 * Backgrounds Controller
 *
 * @category Backgrounds
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class BackgroundController extends ParentController
{
    /**
     * POST: Save Background
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return json response wheather data is saved or any error occured
     */
    public function saveBackgrounds($request, $response)
    {
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $allPostPutVars = $request->getParsedBody();
        $allFileNames = $saveBackgroundList = $recordBackgroundIds = [];
        $jsonResponse = [
            'status' => 0,
            'message' => message('Backgrounds', 'error'),
        ];
        $success = 0;
        if ($allPostPutVars['type'] == 1) {
            $uploadedFiles = $request->getUploadedFiles();
            $uploadingFilesNo = count($uploadedFiles['upload']);
            // Save file if request contain files
            $allFileNames = do_upload(
                'upload', path('abs', 'background'), [150], 'array'
            );
            $uploadingFilesNo = count($allFileNames);
            if (!empty($allFileNames)) {
                foreach ($allFileNames as $eachFileKey => $eachFile) {
                    $backgroundLastId = 0;
                    if (!empty($eachFile)) {
                        $saveBackgroundList[$eachFileKey] = [
                            'store_id' => $getStoreDetails['store_id'],
                            'name' => $allPostPutVars['name'],
                            'price' => $allPostPutVars['price'],
                            'type' => $allPostPutVars['type'],
                            'value' => $eachFile,
                        ];

                        $saveEachBackground = new Background(
                            $saveBackgroundList[$eachFileKey]
                        );
                        if ($saveEachBackground->save()) {
                            $backgroundLastId = $saveEachBackground->xe_id;
                            $recordBackgroundIds[] = $backgroundLastId;
                            /**
                             * Save category and subcategory data
                             * Category id format. [4,78,3]
                             */
                            if (!empty($allPostPutVars['categories'])) {
                                $categoryIds = $allPostPutVars['categories'];
                                // Save Background Categories
                                $this->saveBackgroundCategories(
                                    $backgroundLastId, $categoryIds
                                );
                            }
                            /**
                             * - Save tags with respect to the Backgrounds
                             * - Tag Names format.: tag1,tag2,tag3
                             */
                            if (!empty($allPostPutVars['tags'])) {
                                $tags = $allPostPutVars['tags'];
                                $this->saveBgTags(
                                    $getStoreDetails['store_id'],
                                    $backgroundLastId,
                                    $tags
                                );
                            }
                            $success++;
                        }
                    }
                }
            }
        } else {
            $allPostPutVars += [
                'value' => $allPostPutVars['upload'],
                'store_id' => $getStoreDetails['store_id'],
            ];
            $saveBackgroundColor = new Background($allPostPutVars);
            if ($saveBackgroundColor->save()) {
                $backgroundLastId = $saveBackgroundColor->xe_id;
                /**
                 * Save category and subcategory
                 * Category id format: [4,78,3]
                 */
                if (!empty($allPostPutVars['categories'])) {
                    $categoryIds = $allPostPutVars['categories'];
                    // Save Background Categories
                    $this->saveBackgroundCategories(
                        $backgroundLastId, $categoryIds
                    );
                }
                /**
                 * - Save tags
                 * - Tag Names format: tag1,tag2,tag3
                 */
                if (isset($allPostPutVars['tags'])
                    && $allPostPutVars['tags'] != ""
                ) {
                    $tags = $allPostPutVars['tags'];
                    $this->saveBgTags(
                        $getStoreDetails['store_id'], $backgroundLastId, $tags
                    );
                }
                $success++;
                $uploadingFilesNo = 1;
            }
        }
        if (!empty($success)) {
            $jsonResponse = [
                'status' => 1,
                'message' => $success . ' out of ' . $uploadingFilesNo
                . ' Background(s) uploaded successfully',
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Save Categories/Sub-categories w.r.t Background
     *
     * @param $backgroundId Background ID
     * @param $categoryIds  (in  an array with comma separated)
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return boolean
     */
    protected function saveBackgroundCategories($backgroundId, $categoryIds)
    {
        $getAllCategoryArr = json_clean_decode($categoryIds, true);
        //SYNC Categories to the Background_Category Relationship Table
        $backgroundInit = new Background();
        $findBackground = $backgroundInit->find($backgroundId);
        if ($findBackground->categories()->sync($getAllCategoryArr)) {
            return true;
        }
        return false;
    }

    /**
     * Save tags w.r.t Background
     *
     * @param $storeId      Store Id
     * @param $backgroundId Background ID
     * @param $tags         Multiple Tags
     *
     * @author satyabratap@riaxe.com
     * @author tanmayap@riaxe.com
     * @date   4th Nov 2019
     * @return boolean
     */
    protected function saveBgTags($storeId, $backgroundId, $tags)
    {
        // Save Backgrounds and tags relation
        if (!empty($tags)) {
            $getTagIds = $this->saveTags($storeId, $tags);
            // SYNC Tags into Backgrounds Tag Relationship Table
            $backgroundInit = new Background();
            $findBackground = $backgroundInit->find($backgroundId);
            if ($findBackground->tags()->sync($getTagIds)) {
                return true;
            }
        } else {
            // Clean relation in case no tags supplied
            $tagRelInit = new BackgroundTagRelation();
            $backgroundTags = $tagRelInit->where('background_id', $backgroundId);
            if ($backgroundTags->delete()) {
                return true;
            }
        }

        return false;
    }

    /**
     * GET: List of Backgrounds and single Background
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return All/Single Background List
     */
    public function getBackgrounds($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $offset = 0;
        $backgroundData = [];
        $getStoreDetails = get_store_details($request);
        $jsonResponse = [
            'status' => 1,
            'data' => [],
            'message' => message('Background', 'not_found'),
        ];
        $backgroundId = to_int($args['id']);
        $backgroundInit = new Background();
        $getBackgrounds = $backgroundInit->where('xe_id', '>', 0);
        $getBackgrounds->where('store_id', '=', $getStoreDetails['store_id']);
        // Total records irrespectable of filters
        $totalCounts = $getBackgrounds->count();
        if ($totalCounts > 0) {
            if (!empty($backgroundId)) {
                //For single Background data
                $getBackgrounds->where('xe_id', '=', $backgroundId)
                    ->select('xe_id', 'name', 'value', 'price', 'type');
                $backgroundData = $getBackgrounds->orderBy('xe_id', 'DESC')
                    ->first();

                // Get Category Ids
                if (!empty($backgroundData)) {
                    $getCategories = $this->getCategoriesById(
                        'Backgrounds', 'BackgroundCategoryRelation',
                        'background_id', $backgroundId
                    );
                    $getTags = $this->getTagsById(
                        'Backgrounds', 'BackgroundTagRelation',
                        'background_id', $backgroundId
                    );
                    $backgroundData['categories'] = !empty($getCategories)
                    ? $getCategories : [];
                    $backgroundData['tags'] = !empty($getTags) ? $getTags : [];
                }
                // Unset category_name Key in case of single record fetch
                $backgroundData = json_clean_decode($backgroundData, true);
                unset($backgroundData['category_names']);
                $jsonResponse = [
                    'status' => 1,
                    'records' => 1,
                    'data' => [
                        $backgroundData,
                    ],
                ];
            } else {
                // Collect all Filter columns from url
                $page = $request->getQueryParam('page');
                $perpage = $request->getQueryParam('perpage');
                $categoryId = $request->getQueryParam('category');
                $sortBy = $request->getQueryParam('sortby');
                $order = $request->getQueryParam('order');
                $name = $request->getQueryParam('name');
                $type = $request->getQueryParam('type');
                $printProfileKey = $request->getQueryParam('print_profile_id');

                // For multiple Background data
                $getBackgrounds->select('xe_id', 'name', 'value', 'price', 'type');

                // Filter Search as per type
                if (isset($type) && $type != "") {
                    $getBackgrounds->where(
                        function ($query) use ($type) {
                            $query->where('type', $type);
                        }
                    );
                }

                // Multiple Table search for name attribute
                if (isset($name) && $name != "") {
                    $name = '\\' . $name;
                    $getBackgrounds->where(
                        function ($query) use ($name) {
                            $query->where('name', 'LIKE', '%' . $name . '%')
                                ->orWhereHas(
                                    'backgroundTags.tag', function ($q) use ($name) {
                                        return $q->where(
                                            'name', 'LIKE', '%' . $name . '%'
                                        );
                                    }
                                )->orWhereHas(
                                    'backgroundCategory.category', function ($q) use ($name) {
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
                    $assetTypeArr = $this->assetsTypeId('backgrounds');
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
                if (isset($categoryId) && $categoryId != "") {
                    $searchCategories = json_clean_decode($categoryId, true);
                    $getBackgrounds->whereHas(
                        'backgroundCategory', function ($q) use ($searchCategories) {
                            return $q->whereIn('category_id', $searchCategories);
                        }
                    );
                }
                // Total records including all filters
                $getTotalPerFilters = $getBackgrounds->count();
                // Get pagination data
                if (isset($page) && $page != "") {
                    $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
                    $offset = $totalItem * ($page - 1);
                    $getBackgrounds->skip($offset)->take($totalItem);
                }
                // Sorting by column name and sord order parameter
                if (!empty($sortBy) && !empty($order)) {
                    $getBackgrounds->orderBy($sortBy, $order);
                }
                $backgroundData = $getBackgrounds->get();

                $jsonResponse = [
                    'status' => 1,
                    'records' => count($backgroundData),
                    'total_records' => $getTotalPerFilters,
                    'data' => $backgroundData,
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * PUT: Update a single background
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return json response wheather data is updated or not
     */
    public function updateBackground($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Background', 'error'),
        ];
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $updateData = $request->getParsedBody();
        $backgroundId = to_int($args['id']);
        if (!empty($backgroundId)) {
            $backgroundInit = new Background();
            $getOldbackground = $backgroundInit->where('xe_id', $backgroundId);
            if ($getOldbackground->count() > 0) {
                unset(
                    $updateData['id'], $updateData['tags'],
                    $updateData['categories'], $updateData['upload'],
                    $updateData['value'], $updateData['backgroundId']
                );

                // Delete old file
                $this->deleteOldFile(
                    "backgrounds", "value", [
                        'xe_id' => $backgroundId,
                    ], path('abs', 'background')
                );
                $getUploadedFileName = do_upload(
                    'upload', path('abs', 'background'), [100], 'string'
                );
                if ($getUploadedFileName != '') {
                    $updateData += ['value' => $getUploadedFileName];
                } elseif (isset($allPostPutVars['upload']) 
                    && $allPostPutVars['upload'] != ""
                ) {
                    $updateData += ['value' => $allPostPutVars['upload']];
                }

                $updateData += ['store_id' => $getStoreDetails['store_id']];
                // Update record
                try {
                    $backgroundInit->where('xe_id', '=', $backgroundId)
                        ->update($updateData);
                    // Save category
                    if (isset($allPostPutVars['categories'])
                        && $allPostPutVars['categories'] != ""
                    ) {
                        $categoryIds = $allPostPutVars['categories'];
                        $this->saveBackgroundCategories($backgroundId, $categoryIds);
                    }
                    // Save tags
                    $tags = !empty($allPostPutVars['tags']) 
                        ? $allPostPutVars['tags'] : "";
                    $this->saveBgTags(
                        $getStoreDetails['store_id'], $backgroundId, $tags
                    );
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Background', 'updated'),
                    ];
                } catch (\Exception $e) {
                    // Store exception in logs
                    create_log(
                        'Assets', 'error',
                        [
                            'message' => $e->getMessage(),
                            'extra' => [
                                'module' => 'Backgrounds',
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
     * DELETE: Delete single/multiple background
     *
     * @param $request  Slim's Argument parameters
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return json response wheather data is deleted or not
     */
    public function deleteBackground($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Background', 'error'),
        ];
        if (!empty($args['id'])) {
            $getDeleteIdsToArray = json_clean_decode($args['id'], true);
            $totalCount = count($getDeleteIdsToArray);
            if (!empty($getDeleteIdsToArray) && $totalCount > 0) {
                $backgroundInit = new Background();
                $backgrounds = $backgroundInit->whereIn('xe_id', $getDeleteIdsToArray);
                if ($backgrounds->count() > 0) {
                    // Fetch Background details
                    $getBackgroundDetails = $backgrounds->select('xe_id')
                        ->get();
                    try {
                        $success = 0;
                        if (!empty($getBackgroundDetails)) {
                            foreach ($getBackgroundDetails as $backgroundFile) {
                                if (!empty($backgroundFile['xe_id'])) {
                                    $this->deleteOldFile(
                                        "backgrounds", "value", [
                                            'xe_id' => $backgroundFile['xe_id'],
                                        ], path('abs', 'background')
                                    );
                                }
                                $backgroundDelInit = new Background();
                                $backgroundDelInit->where('xe_id', $backgroundFile['xe_id'])->delete();
                                $success++;
                            }
                        }
                        if ($success > 0) {
                            $jsonResponse = [
                                'status' => 1,
                                'message' => $success . ' out of ' . $totalCount 
                                . ' Background(s) deleted successfully',
                            ];
                        }
                    } catch (\Exception $e) {
                        // Store exception in logs
                        create_log(
                            'Assets', 'error',
                            [
                                'message' => $e->getMessage(),
                                'extra' => [
                                    'module' => 'Backgrounds',
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
     * @date   20 Jan 2020 Satyabrata
     * @return Delete Json Status
     */
    public function deleteCategory($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Category', 'error'),
        ];
        if (!empty($args)) {
            $categoryId = $args['id'];
            $jsonResponse = $this->deleteCat(
                'backgrounds', $categoryId,
                'Backgrounds', 'BackgroundCategoryRelation'
            );
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}
