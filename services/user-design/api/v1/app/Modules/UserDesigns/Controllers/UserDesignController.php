<?php
/**
 * Manage Carts
 *
 * PHP version 5.6
 *
 * @category  Carts
 * @package   Store
 * @author    Mukesh <mukeshp@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\UserDesigns\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\UserDesigns\Models as UserDesignModel;
use App\Components\Models\DesignStates;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Manage user save design data
 *
 * @category UserDesigns
 * @package  UserSaveDesign
 * @author   Mukesh <mukeshp@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
    
class UserDesignController extends ParentController
{
    /**
     * Initiate Constructer function
     */
    public function __construct()
    {
        DB::enableQueryLog();
    }
    /**
     * Post: User Save Design Data along with capture image (binary file)
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author mukeshp@riaxe.com
     * @date   20 Feb 2020
     * @return json response wheather data is saved or any error occured
     */
    public function saveUserDesign($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        // Initilize json Response
        $jsonResponse = [
            'status' => 0,
            'message' => message('User Design', 'error'),
        ];
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        if (!empty($allPostPutVars['data'])) {
            $designData = json_clean_decode($allPostPutVars['data'], true);
            $productId = to_int($designData['product_info']['product_id']);
            $variantId = to_int($designData['product_info']['variant_id']);
            $saveType = isset($allPostPutVars['save_type']) ? $allPostPutVars['save_type'] : '';
            $designId = isset($allPostPutVars['design_id']) ? $allPostPutVars['design_id'] : '';
            // Prepare array for saving design data
            $designDetails = [
                'store_id' => $getStoreDetails['store_id'],
                'product_setting_id' => null,
                'product_variant_id' => $variantId,
                'product_id' => $productId,
                'type' => "user_slot",
                'custom_price' =>  0.00,
            ];
            if (!empty($allPostPutVars['face_data'])) {
                $designData['face_data'] = $allPostPutVars['face_data'];
            }
            if (!empty($allPostPutVars['layer_data'])) {
                $designData['layer_data'] = $allPostPutVars['layer_data'];
            }
            if (!empty($designDetails)) {
                // Save capture Image
                $captureImage = $this->saveCaptureImage(
                    $request, $response, ['return_type' => 'array', 'save_type' => $saveType, 'design_id' => $designId]
                );
                if (!empty($captureImage)) {
                    $designData['capture_images'] = $captureImage['images'];
                    // save design data and get customDesignId
                    $customDesignId = $this->saveDesignData(
                        $designDetails, json_encode($designData), [
                            'directory' => 'user_designs', 'save_type' => $saveType, 'design_id' => $designId
                        ]
                    );
                    if ($customDesignId > 0) {
                        // Save User Design Data with its dedicated function
                        $userSaveDesignResponse = $this->saveUserDesignData(
                            $request, $response, $customDesignId, $saveType
                        );
                        $jsonResponse = [
                            'status' => 1,
                            'user_design_id' => $userSaveDesignResponse,
                            'message' => message('User Design', 'saved'),
                        ];
                    }
                }
            }
        }
        return response($response, ['data' => $jsonResponse, 'status' => $serverStatusCode]);
    }

    /**
     * Save method for Template data
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $designId Design Id from User Design Table
     * @param $saveType Flag for Save or Update
     *
     * @author mukeshp@riaxe.com
     * @date   20 Feb 2020
     * @return Boolean
     */
    private function saveUserDesignData(
        $request,
        $response,
        $designId,
        $saveType = 'save'
    ) {
        $getPostData = $request->getParsedBody();
        $saveDesignData = [
            'customer_id' => $getPostData['customer_id'],
            'design_id' => $designId,
        ];
        if ($saveType == 'update') {
            $updatedIdInit = new UserDesignModel\UserDesign();
            $designData = $updatedIdInit->where($saveDesignData)->first();
            return !empty($designData->xe_id) ? $designData->xe_id : false;
        } else {
            // Create a new object instance for save
            $saveUserDesignInit = new UserDesignModel\UserDesign($saveDesignData);
            $saveUserDesignInit->save();
            if ($saveUserDesignInit->xe_id) {
                return $saveUserDesignInit->xe_id;
            }
            return false;
        }
    }

    /**
     * GET: Get all Saved Design list By Customer
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author mukeshp@riaxe.com
     * @author tanmayap@riaxe.com
     * @date   20 Feb 2020
     * @return Json Response
     */
    public function getUserDesignList($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $getStoreDetails = get_store_details($request);
        $jsonResponse = [
            'status' => 1,
            'total_records' => 0,
            'data' => [],
            'message' => message('User Design', 'not_found'),
        ];
        $offset = 0;
        $page = $request->getQueryParam('page');
        $perpage = $request->getQueryParam('perpage');
        $customerId = $request->getQueryParam('customer_id');
        $isDebug = $request->getQueryParam('debug');
        $userDesignInit = new UserDesignModel\UserDesign();
        $getUserDesigns = $userDesignInit->where(
            'customer_id', $customerId
        );
        $totalCounts = $getUserDesigns->count();
        // Pagination Data
        $offset = 0;
        if (isset($page) && $page != "") {
            $totalItem = empty($perpage) ? PAGINATION_MAX_ROW : $perpage;
            $offset = $totalItem * ($page - 1);
            $getUserDesigns->skip($offset)->take($totalItem);
        }
        if ($totalCounts > 0) {
            $userDesignsData = $getUserDesigns->orderBy('xe_id', 'desc')
                ->get()
                ->toArray();
            $userDesignData = [];
            foreach ($userDesignsData as $key => $value) {
                $designStateInit = new DesignStates();
                $getDesignState = $designStateInit->where(
                    'xe_id', $value['design_id']
                );
                $productId = 0;
                $variantId = 0;
                $url = "";
                $thumb = "";
                if ($getDesignState->count() > 0) {
                    $desginStateData = $getDesignState->get()
                        ->toArray();
                    $productId = $desginStateData[0]['product_id'];
                    $variantId = $desginStateData[0]['product_variant_id'];
                }
                // Read design state json
                $designJsonData = $this->readDesignJson($value['design_id']);
                $userDesignData[$key]['id'] = $value['xe_id'];
                $userDesignData[$key]['design_id'] = $value['design_id'];
                $userDesignData[$key]['design_name'] = $designJsonData['design_name'];
                $userDesignData[$key]['product_info']['product_id'] = $productId;
                $userDesignData[$key]['product_info']['variant_id'] = $variantId;
                $userDesignData[$key]['capture_images'] = [];
                if (!empty($designJsonData['capture_images'])) {
                    $userDesignData[$key]['capture_images'] = $designJsonData['capture_images'];
                }
            }
            $jsonResponse = [
                'status' => 1,
                'total_records' => $totalCounts,
                'data' => $userDesignData,
            ];
        }
        if (!empty($isDebug)) {
            debug(DB::getQueryLog());
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Get Saved Design Details By Design Id
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @author tanmayap@riaxe.com
     * @date   20 Feb 2020
     * @return Json Response
     */
    public function getUserDesign($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $isDebug = $request->getQueryParam('debug');
        $getStoreDetails = get_store_details($request);
        $jsonResponse = [
            'status' => 1,
            'total_records' => 0,
            'data' => [],
            'message' => message('User Design', 'not_found'),
        ];
        $userDesignInit = new UserDesignModel\UserDesign();
        $getUserDesigns = $userDesignInit->where(
            'xe_id', $args['id']
        )
            ->get()
            ->toArray();
        
        $totalCounts = count($getUserDesigns);//$getUserDesigns->count();
        if ($totalCounts > 0) {
            // $userDesignsData = $getUserDesigns->get()
            //     ->toArray();
            $userDesignData = [];
            foreach ($getUserDesigns as $key => $value) {
                $designStateInit = new DesignStates();
                $getDesignState = $designStateInit->where(
                    'xe_id', $value['design_id']
                );
                $svgDecodedData = [];
                if ($getDesignState->count() > 0) {
                    $desginStateData = $getDesignState->get()
                        ->toArray();
                    $productId = $desginStateData[0]['product_id'];
                    $variantId = $desginStateData[0]['product_variant_id'];
                }
                // Read design state json
                $designJsonData = $this->readDesignJson($value['design_id']);
                $userDesignData[$key]['id'] = $value['xe_id'];
                $userDesignData[$key] += $designJsonData;
            }
            $jsonResponse = [
                'status' => 1,
                'total_records' => $totalCounts,
                'data' => $userDesignData,
            ];
        }
        if (!empty($isDebug)) {
            debug(DB::getQueryLog());
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * Read and arrange the Json file stored in side Design State
     *
     * @param $designId Design ID's Primary key
     *
     * @author tanmayap@riaxe.com
     * @date   26 Feb 2020
     * @return Array
     */
    protected function readDesignJson($designId)
    {
        $designData = [];
        if (!empty($designId)) {
            $svgJsonPath = path('abs', 'design_state') . 'user_designs';
            $readDesignPath = path('read', 'design_preview') . 'user_designs';
            $svgJsonPath .= '/' . $designId . '.json';
            if (file_exists($svgJsonPath)) {
                $svgData = read_file($svgJsonPath);
                $svgDecodedData = json_clean_decode($svgData, true);
                $designData['version'] = $svgDecodedData['version'];
                $designData['product_info'] = $svgDecodedData['product_info'];
                $designData['design_name'] = !empty($svgDecodedData['design_name']) 
                    ? $svgDecodedData['design_name'] : "";
                $designData['sides'] = $svgDecodedData['sides'];
                $designData['face_data'] = $svgDecodedData['face_data'];
                $designData['layer_data'] = $svgDecodedData['layer_data'];
                $designData['capture_images'] = [];
                if (!empty($svgDecodedData['capture_images'])) {
                    foreach ($svgDecodedData['capture_images'] as $cKey => $capture) {
                        $designData['capture_images'][$cKey] = [
                            'file_name' => $capture['file_name'],
                            'url' => $readDesignPath . '/' . $capture['file_name'],
                            'thumb' => $readDesignPath . '/' . 'thumb_' . $capture['file_name'],
                        ];
                    }
                }
            }
        }

        return $designData;
    }

    /**
     * Delete: Delete user datas along with the design files
     *
     * @param $request  Slim's Argument parameters
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   26 Jun 2020
     * @return json
     */
    public function deleteUserDesign($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('User Design', 'error')
        ];
        if (!empty($args)) {
            // Multiple Ids in json format
            $customDesignId = trim($args['id']);
            $svgJsonPath = path('abs', 'design_state') . 'user_designs';
            $absDesignPath = path('abs', 'design_preview') . 'user_designs';
            $svgJsonPath .= '/' . $customDesignId . '.json';
            if (file_exists($svgJsonPath)) {
                $svgData = read_file($svgJsonPath);
                $svgDecodedData = json_clean_decode($svgData, true);
                if (!empty($svgDecodedData['capture_images'])) {
                    foreach ($svgDecodedData['capture_images'] as $cKey => $capture) {
                        delete_file($absDesignPath . '/' . $capture['file_name']);
                        delete_file($absDesignPath . '/thumb_' . $capture['file_name']);
                    }
                }
                delete_file($svgJsonPath);
            }

            try {
                $userDesignInit = new UserDesignModel\UserDesign();
                $getUserDesigns = $userDesignInit->where(
                    'design_id', $customDesignId
                )->delete();
                $designStateInit = new DesignStates();
                $getDesignState = $designStateInit->where(
                    'xe_id', $customDesignId
                )->delete();
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('User Design', 'deleted'),
                ];
            } catch (\Exception $e) {
                $serverStatusCode = EXCEPTION_OCCURED;
                create_log(
                    'User Design', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Deleting User Design',
                        ],
                    ]
                );
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}
