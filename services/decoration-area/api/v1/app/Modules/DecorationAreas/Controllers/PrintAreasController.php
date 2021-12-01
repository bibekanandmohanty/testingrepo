<?php
/**
 * Manage Print Areas
 *
 * PHP version 5.6
 *
 * @category  Print_Area
 * @package   Product
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\DecorationAreas\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\DecorationAreas\Models\PrintArea;
use App\Modules\DecorationAreas\Models\PrintAreaType;

/**
 * Print Area Controller
 *
 * @category Class
 * @package  Print_Area
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintAreasController extends ParentController
{
    /**
     * Post : Save New Print Areas
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Save Json Response
     */
    public function savePrintArea($request, $response)
    {
        $jsonResponse = [
            'status' => 0,
            'message' => message('Print Area', 'error'),
        ];
        $serverStatusCode = OPERATION_OKAY;
        $getStoreId = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        if (!empty($allPostPutVars['name'])) {
            $fileName = do_upload(
                'upload', path('abs', 'print_area'), [150], 'string'
            );
            if (!empty($fileName)) {
                $allPostPutVars += ['file_name' => $fileName];
            }
            $allPostPutVars['store_id'] = $getStoreId['store_id'];
            $printAreaInit = new PrintArea($allPostPutVars);
            if ($printAreaInit->save()) {
                $jsonResponse = [
                    'status' => 1,
                    'print_area_insert_id' => $printAreaInit->xe_id,
                    'message' => message('Print Area', 'saved'),
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * Put : Update Existing Print Areas
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Update Json Response
     */
    public function updatePrintArea($request, $response, $args)
    {
        $jsonResponse = [
            'status' => 0,
            'message' => message('Print Area', 'error'),
        ];
        $serverStatusCode = OPERATION_OKAY;
        $allPostPutVars = $request->getParsedBody();
        $getStoreId = get_store_details($request);
        $printAreaUpdateId = to_int($args['id']);
        if (!empty($allPostPutVars['name']) && !empty($printAreaUpdateId)) {
            $printAreaInit = new PrintArea();
            $recordCount = $printAreaInit->where(['xe_id' => $printAreaUpdateId])
                ->count();
            if ($recordCount > 0) {
                // Check if the provided print area type is custom area type or not
                $isCustom = 0;
                $printAreaIdInit = new PrintArea();
                $checkIsCustom = $printAreaIdInit->find(
                    $allPostPutVars['print_area_type_id']
                );
                if (!empty($checkIsCustom->xe_id)) {
                    $isCustom = $checkIsCustom->is_custom;
                }
                $this->deleteOldFile(
                    'print_areas',
                    'file_name',
                    ['xe_id' => $printAreaUpdateId],
                    path('abs', 'print_area')
                );
                $fileName = do_upload(
                    'upload', path('abs', 'print_area'), [150], 'string'
                );

                $printAreaInit = new PrintArea();
                $savePrintArea = $printAreaInit->find($printAreaUpdateId);
                // Build Save array object
                $savePrintArea->name = $allPostPutVars['name'];
                $savePrintArea->print_area_type_id = $allPostPutVars[
                    'print_area_type_id'
                ];
                // Check if Image Exist or not
                if (!empty($fileName)) {
                    $savePrintArea->file_name = $fileName;
                }
                // Implement Bleed and shape area
                if (!empty($allPostPutVars['bleed_width'])) {
                    $savePrintArea->bleed_width = $allPostPutVars['bleed_width'];
                }
                if (!empty($allPostPutVars['bleed_height'])) {
                    $savePrintArea->bleed_height = $allPostPutVars['bleed_height'];
                }
                if (!empty($allPostPutVars['safe_width'])) {
                    $savePrintArea->safe_width = $allPostPutVars['safe_width'];
                }
                if (!empty($allPostPutVars['safe_height'])) {
                    $savePrintArea->safe_height = $allPostPutVars['safe_height'];
                }
                // End
                // If the Print Area type other than Custom then turn file_name null
                if (!empty($isCustom) && $isCustom != 1) {
                    $savePrintArea->file_name = null;
                }
                $savePrintArea->width = $allPostPutVars['width'];
                $savePrintArea->height = $allPostPutVars['height'];
                $savePrintArea->is_user_defined = $allPostPutVars['is_user_defined'];
                $savePrintArea->store_id = $getStoreId['store_id'];
                if ($savePrintArea->save()) {
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Print Area', 'updated'),
                    ];
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * Get: Getting List of All Print Areas
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Json response of all Print Areas
     */
    public function getPrintAreas($request, $response)
    {
        $jsonResponse = [
            'status' => 0,
            'data' => [],
            'message' => message('Print Area', 'not_found'),
        ];
        $serverStatusCode = OPERATION_OKAY;
        $getStoreId = get_store_details($request);
        $printAreaInit = new PrintArea();
        if ($printAreaInit->count() > 0) {
            // Set Filter options
            $sortBy = !empty($request->getQueryParam('sortby')) 
                ? $request->getQueryParam('sortby') : 'xe_id';
            $order = !empty($request->getQueryParam('order')) 
                ? $request->getQueryParam('order') : 'desc';

            $printAreaInit = new PrintArea();
            $getPrintAreas = $printAreaInit->where('xe_id', '>', 0);
            // Sorting All records by column name and sord order parameter
            if (!empty($sortBy) && !empty($order)) {
                $getPrintAreas->orderBy($sortBy, $order);
            }
            $printAreaList = $getPrintAreas->where($getStoreId)->get();
            $jsonResponse = [
                'status' => 1,
                'data' => $printAreaList,
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Delete: Delete requested Print Area by ID
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Delete Json Response
     */
    public function deletePrintArea($request, $response, $args)
    {
        $jsonResponse = [
            'status' => 0,
            'message' => message('Print Area', 'error'),
        ];
        $serverStatusCode = OPERATION_OKAY;
        $updateId = to_int($args['id']);

        if (!empty($updateId)) {
            $getPrintAreaInit = new PrintArea();
            $checkCount = $getPrintAreaInit->where(['xe_id' => $updateId])->count();
            if ($checkCount > 0) {
                $printAreaInit = new PrintArea();
                $deletePrintArea = $printAreaInit->find($updateId);
                // Restrict System Defined Records from deleting
                if (!empty($deletePrintArea->is_user_defined)
                    && (int) $deletePrintArea->is_user_defined === 0
                ) {
                    $jsonResponse = [
                        'status' => 0,
                        'message' => message('Print Area', 'not_allowed'),
                    ];
                } else {
                    // Delete File
                    $this->deleteOldFile(
                        'print_areas',
                        'file_name',
                        ['xe_id' => $updateId],
                        path('abs', 'print_area')
                    );
                    if ($deletePrintArea->delete()) {
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Print Area', 'deleted'),
                        ];
                    }
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}
