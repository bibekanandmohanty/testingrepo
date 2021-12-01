<?php
/**
 * Manage Graphic Fonts
 *
 * PHP version 5.6
 *
 * @category  Graphic_Font
 * @package   Eloquent
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\GraphicFonts\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\GraphicFonts\Models\GraphicFont;
use App\Modules\GraphicFonts\Models\GraphicFontLetter;
use App\Modules\GraphicFonts\Models\GraphicFontTagRelation;

/**
 * Graphic Font Controller
 *
 * @category Class
 * @package  Graphic_Fonts
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class GraphicFontController extends ParentController
{

    /**
     * Html dom object
     */
    public $domObj;

    /**
     * Initialize Constructor
     */
    public function __construct()
    {
        $this->domHtmlPathInclue();
    }
    
    /**
     * POST: Save Graphic Fonts
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author satyabratap@riaxe.com
     * @date   31st Jan 2019
     * @return json response wheather data is saved or any error occured
     */
    public function saveGraphicFonts($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Graphic Fonts', 'error'),
        ];
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $graphicFontsJson = $allPostPutVars['data'];
        $graphicFontsArray = json_clean_decode($graphicFontsJson, true);
        $graphicFontInit = new GraphicFont();
        $nameCheck = $graphicFontInit->where(['name' => $graphicFontsArray['name']])
            ->count();
        if ($nameCheck > 0) {
            $jsonResponse = [
                'status' => 0,
                'message' => message('Graphic Fonts', 'exist'),
            ];
        }
        if (!empty($graphicFontsArray['name']) && $nameCheck === 0) {
            $fontData = [
                'name' => $graphicFontsArray['name'],
                'price' => $graphicFontsArray['price'],
                'store_id' => $getStoreDetails['store_id'],
                'is_letter_style' => $graphicFontsArray['is_letter_style'],
                'is_number_style' => $graphicFontsArray['is_number_style'],
                'is_special_character_style' => $graphicFontsArray[
                    'is_special_character_style'
                ],
            ];
            $saveGraphicFont = new GraphicFont($fontData);
            $saveGraphicFont->save();
            $graphicFontInsertId = $saveGraphicFont->xe_id;

            /**
             * Save tags
             * Tag Names format : tag1,tag2,tag3
             */
            $tags = !empty($graphicFontsArray['tags'])
                ? $graphicFontsArray['tags'] : "";
            $this->saveGraphicFontTags(
                $getStoreDetails['store_id'], $graphicFontInsertId, $tags
            );

            if (!empty($graphicFontsArray['letter_style'])) {
                $this->_saveFontDesigns(
                    $graphicFontsArray['letter_style'],
                    $graphicFontInsertId, 'letter'
                );
            }
            if (!empty($graphicFontsArray['number_style'])) {
                $this->_saveFontDesigns(
                    $graphicFontsArray['number_style'],
                    $graphicFontInsertId, 'number'
                );
            }
            if (!empty($graphicFontsArray['special_character_style'])) {
                $this->_saveFontDesigns(
                    $graphicFontsArray['special_character_style'],
                    $graphicFontInsertId, 'special_character'
                );
            }
            $jsonResponse = [
                'status' => 1,
                'message' => message('Graphic Font', 'saved'),
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Save Graphic-Font-Designs
     *
     * @param $fontdetails Font Details Array
     * @param $fontId      Graphic Font Id
     * @param $slug        Type of graphic font
     *
     * @author satyabratap@riaxe.com
     * @date   31st Jan 2019
     * @return boolean
     */
    private function _saveFontDesigns($fontdetails, $fontId, $slug)
    {
        if (!empty($fontdetails)) {
            foreach ($fontdetails as $fontData) {
                $getUploadedFileName = do_upload(
                    $fontData['image_upload_data'],
                    path('abs', 'graphicfont'),
                    [],
                    'string'
                );
                $designData = [
                    'graphic_font_id' => $fontId,
                    'name' => $fontData['name'],
                    'font_type' => $slug,
                ];
                if (!empty($getUploadedFileName)) {
                    $designData['file_name'] = $getUploadedFileName;
                }
                $saveGraphicFonts = new GraphicFontLetter($designData);
                $saveGraphicFonts->save();
                
                //Update SVG file height and width
                $imageUrl = path('abs', 'graphicfont').$getUploadedFileName;
                if (file_exists($imageUrl)) {
                    $ext = pathinfo($imageUrl, PATHINFO_EXTENSION);
                    if ($ext =='SVG' || $ext =='svg') {
                        $this->updateSvgHeightWidth($imageUrl);
                    }
                }
            }
        }
        return false;
    }

    /**
     * Save Tags and Graphic-Font-Tag Relations
     *
     * @param $storeId      Store ID
     * @param $fontInsertId Graphic Font ID
     * @param $tags         Multiple Tags
     *
     * @author satyabratap@riaxe.com
     * @date   31st Jan 2019
     * @return boolean
     */
    protected function saveGraphicFontTags($storeId, $fontInsertId, $tags)
    {
        // Save Graphic Font and tags relation
        if (!empty($tags)) {
            $getTagIds = $this->saveTags($storeId, $tags);
            // SYNC Tags into Relationship Table
            $graphicFontInit = new GraphicFont();
            $findGraphicFont = $graphicFontInit->find($fontInsertId);
            if ($findGraphicFont->tags()->sync($getTagIds)) {
                return true;
            }
        } else {
            // Clean relation in case no tags supplied
            $tagRelInit = new GraphicFontTagRelation();
            $graphicFontTags = $tagRelInit->where('graphic_font_id', $fontInsertId);
            if ($graphicFontTags->delete()) {
                return true;
            }
        }
        return false;
    }

    /**
     * GET: List of Graphic Font
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   31st Jan 2019
     * @return All/Single Graphic Font List
     */
    public function getGraphicFonts($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $graphicFontData = [];
        $jsonResponse = [
            'status' => 0,
            'data' => [],
            'message' => message('Graphic Font', 'not_found'),
        ];
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $offset = 0;
        $graphicFontsInit = new GraphicFont();
        $graphicFontId = $args['id'];
        if (!empty($graphicFontId)) {
            $getGraphicFonts = $graphicFontsInit->with(
                'letter_style', 'number_style', 'special_character_style'
            )
                ->select(
                    'xe_id', 'name', 'price', 'is_letter_style',
                    'is_number_style', 'is_special_character_style'
                )
                ->where('xe_id', '>', 0);
            
            //For single Graphic Font data
            $graphicFontData = $getGraphicFonts->where('xe_id', '=', $graphicFontId)
                ->first();

            $getTags = $this->getTagsById(
                'GraphicFonts', 'GraphicFontTagRelation',
                'graphic_font_id', $graphicFontId
            );
            $graphicFontData['tags'] = $getTags;
            // Unset file_name Key in case of single record fetch
            $graphicFontData = json_clean_decode($graphicFontData, true);
            unset($graphicFontData['file_name']);
            $jsonResponse = [
                'status' => 1,
                'data' => [
                    $graphicFontData,
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
            // For multiple Graphic Font data
            $getGraphicFonts = $graphicFontsInit->where('xe_id', '>', 0);
            $getGraphicFonts->select('xe_id', 'name', 'price');
            $getGraphicFonts->where('store_id', '=', $getStoreDetails['store_id']);

            // Searching as per name, category name & tag name
            if (isset($name) && $name != "") {
                $name = '\\' . $name;
                $getGraphicFonts->where(
                    function ($query) use ($name) {
                        $query->where('name', 'LIKE', '%' . $name . '%')
                            ->orWhereHas(
                                'graphicFontTags.tag', function ($q) use ($name) {
                                    return $q->where(
                                        'name', 'LIKE', '%' . $name . '%'
                                    );
                                }
                            );
                    }
                );
            }
            // Total records including all filters
            $getTotalPerFilters = $getGraphicFonts->count();
            // Pagination Data
            if (!empty($page)) {
                $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
                $offset = $totalItem * ($page - 1);
                $getGraphicFonts->skip($offset)->take($totalItem);
            }
            // Sorting All records by column name and sord order parameter
            if (!empty($sortBy) && !empty($order)) {
                $getGraphicFonts->orderBy($sortBy, $order);
            }
            $graphicFontData = $getGraphicFonts->get();
            $jsonResponse = [
                'status' => 1,
                'records' => count($graphicFontData),
                'total_records' => $getTotalPerFilters,
                'data' => $graphicFontData,
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * PUT: Update a Single Graphic Font
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   31st Jan 2019
     * @return json response wheather data is updated or not
     */
    public function updateGraphicFonts($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Graphic Fonts', 'not_found'),
        ];
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $graphicFontJson = !empty($allPostPutVars['data'])
            ? $allPostPutVars['data'] : '{}';
        $graphicFontArray = json_clean_decode($graphicFontJson, true);
        $graphicFontId = to_int($args['id']);
        $graphicFontInit = new GraphicFont();
        $nameCheck = $graphicFontInit->where(['name' => $graphicFontArray['name']])
            ->where('xe_id', '<>', $graphicFontId)
            ->count();
        if ($nameCheck > 0) {
            $jsonResponse = [
                'status' => 0,
                'message' => message('Graphic Fonts', 'exist'),
            ];
        }
        // Save Graphic Font Details
        $graphicFontInit = new GraphicFont();
        $graphicFonts = $graphicFontInit->where('xe_id', $graphicFontId);

        if (isset($graphicFontArray['name']) && $graphicFontArray['name'] != ""
            && $graphicFonts->count() > 0 && $nameCheck === 0
        ) {
            // Update record
            try {
                $updateDetail = [
                    'name' => $graphicFontArray['name'],
                    'price' => $graphicFontArray['price'],
                    'store_id' => $getStoreDetails['store_id'],
                    'is_letter_style' => $graphicFontArray['is_letter_style'],
                    'is_number_style' => $graphicFontArray['is_number_style'],
                    'is_special_character_style' => $graphicFontArray[
                        'is_special_character_style'
                    ],
                ];
                $graphicFonts->update($updateDetail);
                // Update Graphic Font Designs
                if (!empty($graphicFontArray['letter_style'])) {
                    $this->_updateFontDesigns(
                        $graphicFontArray['letter_style'], $graphicFontId, 'letter'
                    );
                }
                if (!empty($graphicFontArray['number_style'])) {
                    $this->_updateFontDesigns(
                        $graphicFontArray['number_style'], $graphicFontId, 'number'
                    );
                }
                if (!empty($graphicFontArray['special_character_style'])) {
                    $this->_updateFontDesigns(
                        $graphicFontArray['special_character_style'],
                        $graphicFontId, 'special_character'
                    );
                }
                /**
                 * Update tags
                 * Tag Names format : tag1,tag2,tag3
                 */
                $tags = !empty($graphicFontArray['tags'])
                    ? $graphicFontArray['tags'] : "";
                $this->saveGraphicFontTags(
                    $getStoreDetails['store_id'], $graphicFontId, $tags
                );
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Graphic Fonts', 'updated'),
                ];
            } catch (\Exception $e) {
                // Store exception in logs
                create_log(
                    'Assets', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Graphic Fonts',
                        ],
                    ]
                );
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Update Graphic-Font-Designs
     *
     * @param $fontdetails Font Details Array
     * @param $fontId      Graphic Font Id
     * @param $slug        Type of graphic font
     *
     * @author satyabratap@riaxe.com
     * @date   31st Jan 2019
     * @return boolean
     */
    private function _updateFontDesigns($fontdetails, $fontId, $slug)
    {
        if (!empty($fontdetails)) {
            foreach ($fontdetails as $fontData) {
                if (!empty($fontData['image_upload_data'])) {
                    // Case #1: If New File uploading requested
                    $requestedFileKey = $fontData['image_upload_data'];
                    $getUploadedFileName = do_upload(
                        $requestedFileKey,
                        path('abs', 'graphicfont'),
                        [],
                        'string'
                    );
                    // Case #1 : If New file added, then again 2 cases will
                    // arrise. 1. Save new record and 2. Update existing
                    $designData = [
                        'graphic_font_id' => $fontId,
                        'name' => $fontData['name'],
                        'font_type' => $slug,
                    ];
                    if (!empty($getUploadedFileName)) {
                        $designData['file_name'] = $getUploadedFileName;
                    }
                    $saveGraphicFonts = new GraphicFontLetter();
                    $checkIdDataExist = $saveGraphicFonts->where(
                        'xe_id', $fontData['xe_id']
                    );
                    if ($checkIdDataExist->count() > 0) {
                        // Update Record
                        $checkIdDataExist->update($designData);
                    } else {
                        // Save New
                        $saveDesign = new GraphicFontLetter($designData);
                        $saveDesign->save();
                    }
                    //Update SVG file height and width
                    $imageUrl = path('abs', 'graphicfont').$getUploadedFileName;
                    if (file_exists($imageUrl)) {
                        $ext = pathinfo($imageUrl, PATHINFO_EXTENSION);
                        if ($ext =='SVG' || $ext =='svg') {
                            $this->updateSvgHeightWidth($imageUrl);
                        }
                    }
                } elseif (!empty($fontData['is_trash']) && $fontData['is_trash'] == 1) {
                    // Case #2: Design will be deleted from the database
                    $graphicFontInit = new GraphicFontLetter();
                    // Delete from Database
                    $this->deleteOldFile(
                        "graphic_font_letters", "file_name", [
                            'xe_id' => $fontData['xe_id'],
                        ], path('abs', 'graphicfont')
                    );
                    $trashDesign = $graphicFontInit->where(
                        [
                            'xe_id' => $fontData['xe_id'],
                            'graphic_font_id' => $fontId,
                        ]
                    );
                    $trashDesign->delete();
                } else {
                    // Case #3: Existing Image Side will be Updated
                    $designData = [
                        'name' => $fontData['name'],
                    ];
                    $graphicFontInit = new GraphicFontLetter();
                    $updateFont = $graphicFontInit->where(
                        ['xe_id' => $fontData['xe_id'], 'graphic_font_id' => $fontId]
                    );
                    $updateFont->update($designData);
                }
            }
            return true;
        }
        return false;
    }

    /**
     * DELETE: Delete single/multiple Graphic Font(s)
     *
     * @param $request  Slim's Argument parameters
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   31st Jan 2019
     * @return json response wheather data is deleted or not
     */
    public function deleteGraphicFonts($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Graphic Fonts', 'not_found'),
        ];
        if (!empty($args['id'])) {
            $getDeleteIds = $args['id'];
            $getDeleteIdsToArray = json_clean_decode($getDeleteIds, true);
            $totalCount = count($getDeleteIdsToArray);
            if (is_array($getDeleteIdsToArray) && count($getDeleteIdsToArray) > 0) {
                $graphicFontInit = new GraphicFont();
                $count = $graphicFontInit->whereIn('xe_id', $getDeleteIdsToArray)
                    ->count();
                if ($count > 0) {
                    $success = 0;
                    try {
                        foreach ($getDeleteIdsToArray as $graphicFontId) {
                            // Delete from Database
                            $letterInit = new GraphicFontLetter();
                            $letterIds = $letterInit->select('xe_id')
                                ->where('graphic_font_id', $graphicFontId)
                                ->get();
                            foreach ($letterIds as $deleteId) {
                                $this->deleteOldFile(
                                    "graphic_font_letters", "file_name", [
                                        'xe_id' => $deleteId['xe_id'],
                                    ], path('abs', 'graphicfont')
                                );
                            }
                            $letterRelInit = new GraphicFontLetter();
                            $letterRelInit->where('graphic_font_id', $graphicFontId)
                                ->delete();
                            $tagRelInit = new GraphicFontTagRelation();
                            $tagRelInit->where('graphic_font_id', $graphicFontId)
                                ->delete();
                            $graphicFontDelInit = new GraphicFont();
                            $graphicFontDelInit->where('xe_id', $graphicFontId)
                                ->delete();
                            $success++;
                        }
                        $jsonResponse = [
                            'status' => 1,
                            'message' => $success . ' out of ' . $totalCount
                            . ' Graphic Fonts(es) deleted successfully',
                        ];
                    } catch (\Exception $e) {
                        // Store exception in logs
                        create_log(
                            'Assets', 'error',
                            [
                                'message' => $e->getMessage(),
                                'extra' => [
                                    'module' => 'Graphic Fonts',
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
     * GET: List of Graphic Font
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author satyabratap@riaxe.com
     * @date   31st Jan 2019
     * @return All Graphic Font List
     */
    public function getAllGraphicFonts($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $graphicFontData = [];
        $jsonResponse = [
            'status' => 0,
            'data' => [],
            'message' => message('Graphic Font', 'not_found'),
        ];
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $graphicFontsInit = new GraphicFont();
        $getGraphicFonts = $graphicFontsInit->with('characters')
            ->select(
                'xe_id', 'name', 'price', 'is_letter_style',
                'is_number_style', 'is_special_character_style'
            )
            ->where('xe_id', '>', 0)
            ->where('store_id', $getStoreDetails['store_id']);
        $graphicFontData = $getGraphicFonts->get();
        if (!empty($graphicFontData) && $graphicFontData->count() > 0) {
            // Unset file_name Key in case of single record fetch
            $graphicFontData = json_clean_decode($graphicFontData, true);
            unset($graphicFontData['file_name']);
            $jsonResponse = [
                'status' => 1,
                'records' => count($graphicFontData),
                'data' => $graphicFontData,
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: include dom html file
     *
     * @author robert@imprintnext.com
     * @date   17 Nov 2020
     * @return nothing
     */
    private function domHtmlPathInclue()
    {
        include_once dirname(__FILE__) . '/../../../Dependencies/simple_html_dom.php';
    }

    /**
     * Update SVG height and width
     *
     * @param $imageUrl SVG file URL
     *
     * @author robert@imprintnext.com
     * @date   17 Nov 2020
     * @return boolean
     */
    private function updateSvgHeightWidth($imageUrl)
    {
        $fileContent = read_file($imageUrl);
        $html = new \simple_html_dom();
        $html->load($fileContent, false);
        $viewBoxSvg = $html->find('svg[viewBox]', 0);
        if (isset($viewBoxSvg) && !empty($viewBoxSvg)) {
            $viewBox = $viewBoxSvg->viewBox;
            if (isset($viewBox) && !empty($viewBox)) {
                $viewBox = explode(' ', $viewBox);
                $vBwidth = $viewBox[2];
                $vBheight = $viewBox[3];
                if ($vBwidth &&  $vBheight) {
                    $viewBoxSvg->height = $vBheight;
                    $viewBoxSvg->width =  $vBwidth;
                }
                $svgFileStatus = write_file(
                    $imageUrl, $viewBoxSvg
                );
            }
        } 
    }
}
