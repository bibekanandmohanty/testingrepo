<?php
/**
 * Manage Fonts
 *
 * PHP version 5.6
 *
 * @category  Fonts
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Fonts\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Dependencies\FontMeta as FontMeta;
use App\Modules\Fonts\Models\Font;
use App\Modules\Fonts\Models\FontCategory as Category;
use App\Modules\Fonts\Models\FontCategoryRelation;
use App\Modules\Fonts\Models\FontTagRelation;

/**
 * Fonts Controller
 *
 * @category Fonts
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class FontController extends ParentController
{
    /**
     * POST: Save Font
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author satyabratap@riaxe.com
     * @date   23 oct 2019
     * @return json response wheather data is saved or any error occured
     */
    public function saveFonts($request, $response)
    {
        $getStoreDetails = get_store_details($request);
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Font', 'error'),
        ];
        $success = 0;
        $allPostPutVars = $request->getParsedBody();
        $saveRecords = [];
        $getFontNames = do_upload(
            'upload', path('abs', 'font'), [150], 'array'
        );
        $uploadingFilesNo = count($getFontNames);
        if (!empty($getFontNames)) {
            foreach ($getFontNames as $key => $font) {
                $fontFullPath = path('abs', 'font') . '/' . $font;
                $ttfInfo = new FontMeta();
                $ttfInfo->setFontFile($fontFullPath);
                $getFontInfo = $ttfInfo->getFontInfo();

                $saveRecords[$key] = [
                    'store_id' => $getStoreDetails['store_id'],
                    'name' => $allPostPutVars['name'],
                    'price' => to_decimal($allPostPutVars['price']),
                    'file_name' => $font,
                    'font_family' => $getFontInfo[1]
                ];
                $fontInit = new Font($saveRecords[$key]);
                if ($fontInit->save()) {
                    $fontId = $fontInit->xe_id;
                    /**
                     * Save category and subcategory data
                     * Category id format: [4,78,3]
                     */
                    if (isset($allPostPutVars['categories'])
                        && $allPostPutVars['categories'] != ""
                    ) {
                        $categoryIds = $allPostPutVars['categories'];
                        $this->_saveFontCategories($fontId, $categoryIds);
                    }
                    /**
                     * Save tags
                     * Tag Names format : tag1,tag2,tag3
                     */
                    $tags = !empty($allPostPutVars['tags']) 
                        ? $allPostPutVars['tags'] : "";
                    $this->saveFontTags(
                        $getStoreDetails['store_id'], $fontId, $tags
                    );
                    $success++;
                }
            }
            if (!empty($success)) {
                $jsonResponse = [
                    'status' => 1,
                    'message' => $success . ' out of ' . $uploadingFilesNo
                        . ' Font(s) uploaded successfully',
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Save categories w.r.t font
     *
     * @param $fontId      Font ID
     * @param $categoryIds (in  an array with comma separated)
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return boolean
     */
    private function _saveFontCategories($fontId, $categoryIds)
    {
        $getAllCategoryArr = json_clean_decode($categoryIds, true);
        // SYNC Categories to the Font_Category Relationship Table
        $fontsInit = new Font();
        $findFont = $fontsInit->find($fontId);
        if ($findFont->categories()->sync($getAllCategoryArr)) {
            return true;
        }
        return false;
    }

    /**
     * Save tags w.r.t font
     *
     * @param $storeId Store ID
     * @param $fontId  Font ID
     * @param $tags    Multiple Tags
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return boolean
     */
    protected function saveFontTags($storeId, $fontId, $tags)
    {
        // Save Font and tags relation
        if (!empty($tags)) {
            $getTagIds = $this->saveTags($storeId, $tags);
            // SYNC Tags into Relationship Table
            $fontsInit = new Font();
            $findFont = $fontsInit->find($fontId);
            if ($findFont->tags()->sync($getTagIds)) {
                return true;
            }
        } else {
            // Clean relation in case no tags supplied
            $tagRelInit = new FontTagRelation();
            $fontTags = $tagRelInit->where('font_id', $fontId);
            if ($fontTags->delete()) {
                return true;
            }
        }
        return false;
    }

    /**
     * GET: List of fonts
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   23 oct 2019
     * @return All/Single Fonts List
     */
    public function getFonts($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $offset = 0;
        $jsonResponse = [
            'status' => 1,
            'data' => [],
            'message' => message('Font', 'not_found'),
        ];
        $getStoreDetails = get_store_details($request);
        $fontsInit = new Font();
        $getFonts = $fontsInit->where('xe_id', '>', 0)
            ->where('store_id', '=', $getStoreDetails['store_id']);
        // total records irrespectable of filters
        $totalCounts = $getFonts->count();
        if ($totalCounts > 0) {
            if (!empty($args['id'])) {
                //For single Font data
                $getFonts->where('xe_id', '=', $args['id'])
                    ->select('xe_id', 'name', 'price', 'font_family', 'file_name');
                $fontsData = $getFonts->orderBy('xe_id', 'DESC')
                    ->first();

                // Get Category Ids
                $getCategories = $this->getCategoriesById(
                    'Fonts', 'FontCategoryRelation',
                    'font_id', $args['id']
                );
                $fontsData['categories'] = !empty($getCategories) 
                    ? $getCategories : [];
                // Get Tag names
                $getTags = $this->getTagsById(
                    'Fonts', 'FontTagRelation',
                    'font_id', $args['id']
                );
                $fontsData['tags'] = !empty($getTags) ? $getTags : [];

                // Unset category_name Key in case of single record fetch
                $fontsData = json_clean_decode($fontsData, true);
                unset($fontsData['category_names']);

                $jsonResponse = [
                    'status' => 1,
                    'records' => 1,
                    'data' => [
                        $fontsData,
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
                $printProfileKey = $request->getQueryParam('print_profile_id');

                // For multiple Font data
                $getFonts->select(
                    'xe_id', 'name', 'price', 'font_family', 'file_name'
                );
                // Searching as per name, category name & tag name
                if (!empty($name)) {
                    $name = '\\' . $name;
                    $getFonts->where(
                        function ($query) use ($name) {
                            $query->where('name', 'LIKE', '%' . $name . '%')
                                ->orWhereHas(
                                    'fontTags.tag', function ($q) use ($name) {
                                        return $q->where(
                                            'name', 'LIKE', '%' . $name . '%'
                                        );
                                    }
                                )->orWhereHas(
                                    'fontCategory.category', function ($q) use ($name) {
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
                    $assetTypeArr = $this->assetsTypeId('fonts');
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
                    $getFonts->whereHas(
                        'fontCategory', function ($q) use ($searchCategories) {
                            return $q->whereIn('category_id', $searchCategories);
                        }
                    );
                }
                // Total records including all filters
                $getTotalPerFilters = $getFonts->count();
                // Get pagination data
                if (!empty($page)) {
                    $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
                    $offset = $totalItem * ($page - 1);
                    $getFonts->skip($offset)->take($totalItem);
                }
                // Sorting by column name and sord order parameter
                if (!empty($sortBy) && !empty($order)) {
                    $getFonts->orderBy($sortBy, $order);
                }
                $fontsData = $getFonts->orderBy('xe_id', 'DESC')->get();

                $jsonResponse = [
                    'status' => 1,
                    'records' => count($fontsData),
                    'total_records' => $getTotalPerFilters,
                    'data' => $fontsData,
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * PUT: Update a single font
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   23 oct 2019
     * @return json response wheather data is updated or not
     */
    public function updateFont($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Category', 'error'),
        ];

        $allPostPutVars = $updateData = $request->getParsedBody();
        $getStoreDetails = get_store_details($request);

        if (!empty($args['id'])) {
            $fontsInit = new Font();
            $getOldFont = $fontsInit->where('xe_id', '=', $args['id']);
            if ($getOldFont->count() > 0) {
                unset(
                    $updateData['id'], $updateData['tags'],
                    $updateData['categories'], $updateData['upload'],
                    $updateData['fontId']
                );
                // Update record
                $this->deleteOldFile(
                    "fonts", "file_name", [
                        'xe_id' => $args['id'],
                    ], path('abs', 'font')
                );
                $getUploadedFileName = do_upload(
                    'upload', path('abs', 'font'), [150], 'string'
                );
                if (!empty($getUploadedFileName)) {
                    $updateData += ['file_name' => $getUploadedFileName];
                }
                // Update record
                try {

                    $fontsInit->where('xe_id', '=', $args['id'])
                        ->update($updateData);
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Font', 'updated'),
                    ];
                    /**
                     * Save category
                     * Parameter: categories
                     */
                    if (!empty($allPostPutVars['categories'])) {
                        $categoryIds = $allPostPutVars['categories'];
                        // Save Categories
                        $this->_saveFontCategories($args['id'], $categoryIds);
                    }
                    /**
                     * Save tags
                     * Tag Names format : tag1,tag2,tag3
                     */
                    $tags = !empty($allPostPutVars['tags']) 
                    ? $allPostPutVars['tags'] : "";
                    $this->saveFontTags(
                        $getStoreDetails['store_id'], $args['id'], $tags
                    );
                } catch (\Exception $e) {
                    // Store exception in logs
                    create_log(
                        'Assets', 'error',
                        [
                            'message' => $e->getMessage(),
                            'extra' => [
                                'module' => 'Fonts',
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
     * DELETE: Delete single/multiple font
     *
     * @param $request  Slim's Argument parameters
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   23 oct 2019
     * @return json response wheather data is deleted or not
     */
    public function deleteFont($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Font', 'error'),
        ];
        if (!empty($args)) {
            $getDeleteIdsToArray = json_clean_decode($args['id'], true);
            $totalCount = count($getDeleteIdsToArray);
            if (!empty($getDeleteIdsToArray) && $totalCount > 0) {
                $fontsInit = new Font();
                if ($fontsInit->whereIn('xe_id', $getDeleteIdsToArray)->count() > 0) {
                    // Fetch Font details
                    $getFontDetails = $fontsInit->whereIn(
                        'xe_id', $getDeleteIdsToArray
                    )
                        ->select('xe_id')->get();

                    try {
                        $success = 0;
                        if (!empty($getFontDetails)) {
                            foreach ($getFontDetails as $fontFile) {
                                if (isset($fontFile['xe_id'])
                                    && $fontFile['xe_id'] != ""
                                ) {
                                    $this->deleteOldFile(
                                        "fonts", "file_name", [
                                            'xe_id' => $fontFile['xe_id'],
                                        ], path('abs', 'font')
                                    );
                                }
                                $fontDelInit = new Font();
                                $fontDelInit->where('xe_id', $fontFile['xe_id'])->delete();
                                $success++;
                            }
                        }
                        if ($success > 0) {
                            $jsonResponse = [
                                'status' => 1,
                                'message' => $success . ' out of ' . $totalCount 
                                . ' Font(s) deleted successfully',
                            ];
                        }
                    } catch (\Exception $e) {
                        // Store exception in logs
                        create_log(
                            'Assets', 'error',
                            [
                                'message' => $e->getMessage(),
                                'extra' => [
                                    'module' => 'Fonts',
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
        if (!empty($args)) {
            $categoryId = $args['id'];
            $jsonResponse = $this->deleteCat(
                'fonts', $categoryId, 'Fonts', 'FontCategoryRelation'
            );
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Get most used fonts
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   30 Jan 2020
     * @return A JSON Response
     */
    public function mostUsedFonts($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Fonts', 'error')
        ];
        $getStoreDetails = get_store_details($request);
        $page = $request->getQueryParam('page');
        $perpage = $request->getQueryParam('perpage');
        $fontsInit = new Font();
        $getFonts = $fontsInit->where(['store_id' => $getStoreDetails['store_id']])
            ->select('xe_id', 'name', 'font_family', 'file_name');
        $totalCounts = $getFonts->count();
        if ($totalCounts > 0) {
            // Get pagination data
            $offset = 0;
            if (!empty($page)) {
                $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
                $offset = $totalItem * ($page - 1);
                $getFonts->skip($offset)->take($totalItem);
            }
            $fontsData = $getFonts->orderBy('total_used', 'DESC')
                ->get();
            $jsonResponse = [
                'status' => 1,
                'total_records' => $totalCounts,
                'records' => count($fontsData),
                'data' => $fontsData,
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}
