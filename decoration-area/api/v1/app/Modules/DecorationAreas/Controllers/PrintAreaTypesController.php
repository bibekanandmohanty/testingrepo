<?php
/**
 * Manage Print Areas Type
 *
 * PHP version 5.6
 *
 * @category  Print_Area_Type
 * @package   Product
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\DecorationAreas\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\DecorationAreas\Models\PrintAreaType;

/**
 * Print Area Type Class
 *
 * @category Print_Area_Type
 * @package  Product
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintAreaTypesController extends ParentController
{
    /**
     * Post : Save Print Area Type
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Json Response
     */
    public function savePrintAreaType($request, $response)
    {
        $jsonResponse = [
            'status' => 0,
            'message' => message('Print Area Type', 'error'),
        ];
        $serverStatusCode = OPERATION_OKAY;
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        if (isset($allPostPutVars['name']) && $allPostPutVars['name'] != "") {
            // Check if file uploading requested
            $uploadedFileName = do_upload(
                'upload', path('abs', 'print_area_type'), [], 'string'
            );
            if (!empty($uploadedFileName)) {
                $allPostPutVars += ['file_name' => $uploadedFileName];
            }
            $allPostPutVars['store_id'] = $getStoreDetails['store_id'];
            $printAreaTypeInit = new PrintAreaType($allPostPutVars);
            if ($printAreaTypeInit->save()) {
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Print Area Type', 'saved'),
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Put: Update Print Area Type
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Json
     */
    public function updatePrintAreaType($request, $response, $args)
    {
        $jsonResponse = [
            'status' => 0,
            'message' => message('Print Area Type', 'error'),
        ];
        $serverStatusCode = OPERATION_OKAY;
        $allPostPutVars = $request->getParsedBody();
        $getStoreDetails = get_store_details($request);
        $updateId = to_int($args['id']);

        if (!empty($allPostPutVars['name']) && !empty($updateId)) {
            $printAreaTyprInit = new PrintAreaType();
            if ($printAreaTyprInit->where(['xe_id' => $updateId])->count() > 0) {
                $this->deleteOldFile(
                    'print_area_types',
                    'file_name',
                    ['xe_id' => $updateId],
                    path('abs', 'print_area_type')
                );
                $updatedFileName = do_upload(
                    'upload', path('abs', 'print_area_type'), [150], 'string'
                );
                $findIdInit = new PrintAreaType();
                $printAreaInit = $findIdInit->find($updateId);
                $printAreaInit->name = $allPostPutVars['name'];
                if (!empty($updatedFileName)) {
                    $printAreaInit->file_name = $updatedFileName;
                }
                $printAreaInit->store_id = $getStoreDetails['store_id'];
                if ($printAreaInit->save()) {
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
     * Get: Get Print Area Type(s)
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Json
     */
    public function getPrintAreaType($request, $response)
    {
        $jsonResponse = [
            'status' => 0,
            'message' => message('Print Area Type', 'not_found'),
        ];
        $serverStatusCode = OPERATION_OKAY;
        $getStoreId = get_store_details($request);
        $printAreaTypeInit = new PrintAreaType();
        if ($printAreaTypeInit->count() > 0) {
            if (!empty($getStoreId)) {
                $printAreaTypeInit->where($getStoreId);
            }
        }
        $printAreaTypes = $printAreaTypeInit->orderBy('xe_id', 'desc')
            ->get();
        if ($printAreaTypes->count() > 0) {
            $printAreas = $printAreaTypes->toArray();
            $jsonResponse = [
                'status' => 1,
                'data' => $printAreas,
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * Delete: Delete a specific Print Area Type
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return Json
     */
    public function deletePrintAreaType($request, $response, $args)
    {
        $jsonResponse = [
            'status' => 1,
            'message' => message('Print Area Type', 'error'),
        ];
        $serverStatusCode = OPERATION_OKAY;
        $updateId = to_int($args['id']);

        if (!empty($updateId)) {
            $printAreaTypeInit = new PrintAreaType();
            $getCount = $printAreaTypeInit->where(['xe_id' => $updateId])
                ->count();
            if ($getCount > 0) {
                // Delete File
                $this->deleteOldFile(
                    'print_area_types', 
                    'file_name', 
                    ['xe_id' => $updateId], 
                    path('abs', 'print_area_type')
                );
                $findId = new PrintAreaType();
                $printAreaInit = $findId->find($updateId);
                if ($printAreaInit->delete()) {
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Print Area Type', 'deleted'),
                    ];
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}
