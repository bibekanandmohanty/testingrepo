<?php
/**
 * Manage Word Cloud
 *
 * PHP version 5.6
 *
 * @category  Word_Cloud
 * @package   Eloquent
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\WordClouds\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\WordClouds\Models\WordCloud;
use App\Modules\WordClouds\Models\WordCloudCategory as Category;
use App\Modules\WordClouds\Models\WordCloudCategoryRelation;
use App\Modules\WordClouds\Models\WordCloudTagRelation;

/**
 * Word Cloud Controller
 *
 * @category Class
 * @package  Word_Cloud
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class WordCloudController extends ParentController
{

    /**
     * POST: Save Word Cloud
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return json response wheather data is saved or any error occured
     */
    public function saveWordClouds($request, $response)
    {

        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Word Cloud', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $getUploadedFileName = do_upload('upload', path('abs', 'wordcloud'), [150], 'string');
        if (!empty($getUploadedFileName)) {
            $allPostPutVars += ['file_name' => $getUploadedFileName];
        }
        $allPostPutVars['store_id'] = $getStoreDetails['store_id'];

        // Save Word Cloud data
        $wordCloud = new WordCloud($allPostPutVars);
        if ($wordCloud->save()) {
            $lastInsertId = $wordCloud->xe_id;
            /**
             * Save category and subcategory data
             * Category id format: [4,78,3]
             */
            if (isset($allPostPutVars['categories'])
                && $allPostPutVars['categories'] != ""
            ) {
                $categoryIds = $allPostPutVars['categories'];
                $this->saveWordCloudCategories($lastInsertId, $categoryIds);
            }
            /**
             * Save tags
             * Tag Names format : tag1,tag2,tag3
             */
            $tags = !empty($allPostPutVars['tags']) 
                ? $allPostPutVars['tags'] : "";
            $this->saveWordCloudTags(
                $getStoreDetails['store_id'], $lastInsertId, $tags
            );
            $jsonResponse = [
                'status' => 1,
                'message' => message('Word Cloud', 'saved'),
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Save Categories/Sub-categories and Word Cloud-Category Relations
     *
     * @param $wordCloudId Word Cloud ID
     * @param $categoryIds (in  an array with comma separated)
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return boolean
     */
    protected function saveWordCloudCategories($wordCloudId, $categoryIds)
    {
        $getAllCategoryArr = json_clean_decode($categoryIds, true);
        // SYNC Categories to the WordCloud_Category Relationship Table
        $wordCloudInit = new WordCloud();
        $findWordCloud = $wordCloudInit->find($wordCloudId);
        if ($findWordCloud->categories()->sync($getAllCategoryArr)) {
            return true;
        }
        return false;
    }

    /**
     * Save Tags and Word Cloud-Tag Relations
     *
     * @param $storeId     Store ID
     * @param $wordCloudId Word Cloud ID
     * @param $tags        Multiple Tags
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return boolean
     */
    protected function saveWordCloudTags($storeId, $wordCloudId, $tags)
    {
        // Save Word Cloud and tags relation
        if (!empty($tags)) {
            $getTagIds = $this->saveTags($storeId, $tags);
            // SYNC Tags into Relationship Table
            $wordCloudInit = new WordCloud();
            $findWordCloud = $wordCloudInit->find($wordCloudId);
            if ($findWordCloud->tags()->sync($getTagIds)) {
                return true;
            }
        } else {
            // Clean relation in case no tags supplied
            $tagRelInit = new WordCloudTagRelation();
            $wordCloudTags = $tagRelInit->where('word_cloud_id', $wordCloudId);
            if ($wordCloudTags->delete()) {
                return true;
            }
        }
        return false;
    }

    /**
     * GET: List of Word Cloud
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return All/Single Word Cloud List
     */
    public function getWordClouds($request, $response, $args)
    {

        $serverStatusCode = OPERATION_OKAY;
        $wordCloudData = [];
        $jsonResponse = [
            'status' => 0,
            'data' => [],
            'message' => message('Word Cloud', 'not_found'),
        ];
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $wordCloudInit = new WordCloud();
        $getWordClouds = $wordCloudInit->where('xe_id', '>', 0)
            ->where('store_id', '=', $getStoreDetails['store_id']);

        if (!empty($args)) {
            $wordCloudId = $args['id'];
            //For single Word Cloud data
            $wordCloudData = $getWordClouds->where('xe_id', '=', $wordCloudId)
                ->first();
            $getCategories = $this->getCategoriesById(
                'WordClouds', 'WordCloudCategoryRelation',
                'word_cloud_id', $wordCloudId
            );
            $getTags = $this->getTagsById(
                'WordClouds', 'WordCloudTagRelation',
                'word_cloud_id', $wordCloudId
            );
            $wordCloudData['categories'] = $getCategories;
            $wordCloudData['tags'] = $getTags;
            // Unset category_name Key in case of single record fetch
            $wordCloudData = json_clean_decode($wordCloudData, true);
            unset($wordCloudData['category_names']);
            $jsonResponse = [
                'status' => 1,
                'data' => [
                    $wordCloudData,
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
            // For multiple WordCloud data
            $getWordClouds->select('xe_id', 'name', 'file_name');
            // Searching as per name, category name & tag name
            if (isset($name) && $name != "") {
                $name = '\\' . $name;
                $getWordClouds->where(
                    function ($query) use ($name) {
                        $query->where('name', 'LIKE', '%' . $name . '%')
                            ->orWhereHas(
                                'wordCloudTags.tag', function ($q) use ($name) {
                                    return $q->where(
                                        'name', 'LIKE', '%' . $name . '%'
                                    );
                                }
                            )->orWhereHas(
                                'wordCloudCategory.category', function ($q) use ($name) {
                                    return $q->where(
                                        'name', 'LIKE', '%' . $name . '%'
                                    );
                                }
                            );
                    }
                );
            }
            // Filter By Print Profile Id
            if (isset($printProfileKey) && $printProfileKey > 0) {
                $profileCatRelObj = new \App\Modules\PrintProfiles\Models\PrintProfileAssetsCategoryRel();
                $assetTypeArr = $this->assetsTypeId('word-clouds');
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
                $categoryId = json_encode($relCatIds);
            }
            // Filter by Category ID
            if (isset($categoryId) && $categoryId != "") {
                $searchCategories = json_clean_decode($categoryId, true);
                $getWordClouds->whereHas(
                    'wordCloudCategory', function ($q) use ($searchCategories) {
                        return $q->whereIn('category_id', $searchCategories);
                    }
                );
            }
            // Total records including all filters
            $getTotalPerFilters = $getWordClouds->count();
            // Get pagination data
            if (isset($page) && $page != "") {
                $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
                $offset = $totalItem * ($page - 1);
                $getWordClouds->skip($offset)->take($totalItem);
            }
            // Sorting by column name and sord order parameter
            if (isset($sortBy) && $sortBy != "" && isset($order) && $order != "") {
                $getWordClouds->orderBy($sortBy, $order);
            }
            $wordCloudData = $getWordClouds->get();
            $jsonResponse = [
                'status' => 1,
                'records' => count($wordCloudData),
                'total_records' => $getTotalPerFilters,
                'data' => $wordCloudData,
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * PUT: Update a single Word Cloud
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return json response wheather data is updated or not
     */
    public function updateWordCloud($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Word Cloud', 'error'),
        ];
        $allPostPutVars = $updateData = $request->getParsedBody();
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);

        if (!empty($args)) {
            $wordCloudId = $args['id'];
            $wordCloudInit = new WordCloud();
            $getOldWordCloud = $wordCloudInit->where('xe_id', '=', $wordCloudId);
            if ($getOldWordCloud->count() > 0) {
                unset(
                    $updateData['id'], $updateData['tags'],
                    $updateData['categories'], $updateData['upload'],
                    $updateData['wordCloudId']
                );
                // Delete old file if exist
                $this->deleteOldFile(
                    "word_clouds", "file_name", [
                        'xe_id' => $wordCloudId,
                    ], path('abs', 'wordcloud')
                );
                $getUploadedFileName = do_upload('upload', path('abs', 'wordcloud'), [150], 'string');
                if (!empty($getUploadedFileName)) {
                    $updateData += ['file_name' => $getUploadedFileName];
                }
                $updateData += ['store_id' => $getStoreDetails['store_id']];
                // Update record into the database
                try {
                    $wordCloudInit = new WordCloud();
                    $wordCloudInit->where('xe_id', '=', $wordCloudId)
                        ->update($updateData);
                    /**
                     * Save category and subcategory data
                     * Category id format: [4,78,3]
                     */
                    if (isset($allPostPutVars['categories'])
                        && $allPostPutVars['categories'] != ""
                    ) {
                        $categoryIds = $allPostPutVars['categories'];
                        $this->saveWordCloudCategories($wordCloudId, $categoryIds);
                    }

                    /**
                     * Save tags
                     * Tag Names format : tag1,tag2,tag3
                     */
                    $tags = !empty($allPostPutVars['tags']) 
                        ? $allPostPutVars['tags'] : "";
                    $this->saveWordCloudTags(
                        $getStoreDetails['store_id'], $wordCloudId, $tags
                    );
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Word Cloud', 'updated'),
                    ];
                } catch (\Exception $e) {
                    // Store exception in logs
                    create_log(
                        'Assets', 'error',
                        [
                            'message' => $e->getMessage(),
                            'extra' => [
                                'module' => 'Word Clouds',
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
     * DELETE: Delete single/multiple Word Cloud
     *
     * @param $request  Slim's Argument parameters
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return json response wheather data is deleted or not
     */
    public function deleteWordClouds($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Word Cloud', 'not_found'),
        ];
        if (isset($args) && $args['id'] != '') {
            $getDeleteIds = $args['id'];
            $getDeleteIdsToArray = json_clean_decode($getDeleteIds, true);
            $totalCount = count($getDeleteIdsToArray);
            if (is_array($getDeleteIdsToArray) && $totalCount > 0) {
                $wordCloudInit = new WordCloud();
                $recordCount = $wordCloudInit->whereIn('xe_id', $getDeleteIdsToArray)
                    ->count();
                if ($recordCount > 0) {
                    try {
                        $success = 0;
                        foreach ($getDeleteIdsToArray as $wordCloudId) {
                            // Delete file from database
                            $this->deleteOldFile(
                                "word_clouds", "file_name", [
                                    'xe_id' => $wordCloudId
                                ], path('abs', 'wordcloud')
                            );
                            $wordCloudInit->where('xe_id', $wordCloudId)
                                ->delete();
                            $success++;
                        }
                        if ($success > 0) {
                            $jsonResponse = [
                                'status' => 1,
                                'message' => $success . ' out of ' . $totalCount 
                                    . ' Word Cloud(s) deleted successfully',
                            ];
                        }
                    } catch (\Exception $e) {
                        // Store exception in logs
                        create_log(
                            'Assets', 'error',
                            [
                                'message' => $e->getMessage(),
                                'extra' => [
                                    'module' => 'Word Clouds',
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
        if (!empty($args) && $args['id'] > 0) {
            $categoryId = $args['id'];
            $jsonResponse = $this->deleteCat(
                'word-clouds', $categoryId, 'WordClouds', 'WordCloudCategoryRelation'
            );
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}
