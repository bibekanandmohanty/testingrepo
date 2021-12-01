<?php
/**
 * Download order details on various endpoints
 *
 * PHP version 5.6
 *
 * @category  Download_Order
 * @package   OrderDownload
 * @author    Radhanatha Mohapatra <radhanatham@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Orders\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Dependencies\Zipper as Zipper;
use ProductStoreSpace\Controllers\StoreProductsController;

/**
 * Order Download Controller
 *
 * @category Class
 * @package  OrderDownload
 * @author   Radhanatha Mohapatra <radhanatham@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class OrderDownloadController extends ParentController
{
    /**
     * Integer 96 dpi is default dpi for illustrator
     */
    public $dpi = 96;

    /**
     * String The path to the current order zip file
     */
    public $orderPath;

    /**
     * String The path to the current order zip file
     */
    public $sidePath;

    /**
     * String The path to the current svg save file
     */
    public $svgSavePath;

    /**
     * Array empty print color array
     */
    public $printColorsArr = array();

    /**
     * String svg image tag
     */
    public $productImageTag;

    /**
     * String print unit for current order
     */
    public $printUnit;

    /**
     * Html dom object
     */
    public $domObj;

    /**
     * Initial filter variable
     */
    public $filter;

    /**
     * Array SVG file format enabled print method
     * Remove latter
     */
    public $svgFileArr = array();

    /**
     * Define order path
     **/
    public function __construct()
    {
        $this->orderPath = path('abs', 'order');
        $this->domHtmlPathInclue();
    }

    /**
     * GET: Download order file by order id
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return boolean
     */
    public function downloadOrder($request, $response, $args)
    {
        $itemId = $request->getQueryParam('item_id') ?
        $request->getQueryParam('item_id') : 0;
        $orderId = $request->getQueryParam('order_id') ?
        $request->getQueryParam('order_id') : 0;
        $isDownload = $request->getQueryParam('is_download') ?
        $request->getQueryParam('is_download') : false;
        $isDownloadStore = $request->getQueryParam('is_download_store') ?
        $request->getQueryParam('is_download_store') : false;
        $orderIncrementId = $request->getQueryParam('order_increment_id') ?
        $request->getQueryParam('order_increment_id') : 0;
        $storeDetails = get_store_details($request);
        $storeID = $storeDetails['store_id'] ? $storeDetails['store_id'] : 1;
        $status = false;
        $msg = 'error';
        $serverStatusCode = OPERATION_OKAY;
        if ((isset($orderId) && $orderId) && ($itemId == 0)) {
            $orderIdList = $orderIncrementId;
            $orderIncIdArray = explode(',', $orderIdList);
            $orderIncIdList = $orderId;
            $orderArray = explode(',', $orderIncIdList);
            $orderListArr = [];
            foreach ($orderIncIdArray as $key => $orderId) {
                $orderAssetPath = $this->orderPath . $orderId;
                if (is_dir($orderAssetPath)) {
                    array_push($orderListArr, $orderId);
                } else {
                    array_push($orderListArr, $orderArray[$key]);
                }
            }
            if (count($orderListArr) > 0) {
                $status = $this->downloadOrderByOrderId($orderListArr, $storeID);
                if ($status) {
                    $orderZipName = '';
                    $zipNameStr = count($orderListArr) > 1 ? 'orders' : 'order';
                    foreach ($orderListArr as $orderIds) {
                        $orderZipName .= "_" . $orderIds;
                    }
                    $zipName = $zipNameStr . $orderZipName . '.zip';
                    if (!file_exists($this->orderPath . '/' . $zipName)) {
                        $returnStatus = $this->createOrderZipFileByOrderId(
                            $orderListArr
                        );
                    } else {
                        $returnStatus = true;
                    }
                    if ($returnStatus) {
                        if ($isDownloadStore) {
                            $status = $this->zipDownload($isDownloadStore);
                            if ($status) {
                                $status = true;
                            }

                        } else {
                            $status = true;
                        }
                    }
                }
            }
        } elseif ((isset($orderId) && $orderId) && (isset($itemId) && $itemId)) {
            $orderAssetPath = $this->orderPath . $orderIncrementId;
            if (is_dir($orderAssetPath)) {
                $orderId = $orderIncrementId;
            }
            $status = $this->downloadOrderByItemId($orderId, $itemId, false);
            if ($status) {
                $zipName = 'order_' . $orderId . '_item_' . $itemId . '.zip';
                if (!file_exists($this->orderPath . '/' . $zipName)) {
                    $returnStatus = $this->createOrderZipFileByItemId($orderId, $itemId);
                    if ($returnStatus) {
                        $status = true;
                    }
                } else {
                    $status = true;
                }

            }
        } elseif ($orderId == 0 && $itemId == 0 && $isDownload) {
            $status = $this->zipDownload($isDownload);
        }

        $msg = $status ? 'done' : 'error';
        $jsonResponse = [
            'status' => $status,
            'message' => message('order download', $msg),
        ];
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Download order file
     *
     * @param $orderId The is current request order Id
     * @param $itemId  The is current request item Id
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return boolean
     */
    private function downloadOrderByItemId($orderId, $itemId, $artworkStatus = false, $createSingleFile = false)
    {
        $status = false;
        $orderIdPath = $this->orderPath . $orderId;
        if (file_exists($orderIdPath) && is_dir($orderIdPath . "/" . $itemId)) {
            $designStateAbsDir = $orderIdPath . "/" . $itemId . "/designState.json";
            $checkUploadFileDir = $orderIdPath . "/" . $itemId;
            if (file_exists($designStateAbsDir)) {
                $designStateStr = read_file($designStateAbsDir);
                $designData = json_clean_decode($designStateStr, true);
                $sidePath = $orderIdPath . "/" . $itemId;
                $scanSideDir = scandir($sidePath);
                $sidePath = $orderIdPath . "/" . $itemId;
                if (file_exists($sidePath) && is_dir($sidePath)) {
                    $scanSideDir = scandir($sidePath);
                    if (is_valid_array($scanSideDir)) {
                        foreach ($scanSideDir as $dir) {
                            $absPath = $orderIdPath . "/" . $itemId . "/" . $dir;
                            if ($dir != '.' && $dir != '..' && is_dir($absPath)) {
                                $i = str_replace("side_", "", $dir);
                                $sideDir = $orderIdPath . "/" . $itemId . "/" . $dir;
                                $svgPath = $sideDir . "/preview_0" . $i . ".svg";
                                $file = $dir . "_" . $itemId . "_" . $orderId;
                                $svgPathChk = $sideDir . "/" . $file . ".svg";
                                if (file_exists($svgPath)
                                    && !file_exists($svgPathChk)
                                ) {
                                    $status = $artworkArr = $this->generateSvgFile(
                                        $svgPath, $orderId,
                                        $itemId, $designData, $artworkStatus, $createSingleFile
                                    );
                                } else {
                                    $status = true;
                                }
                            }
                        }
                    }
                }
            } else if (file_exists($checkUploadFileDir)) {
                $status = true;
                $artworkArr = true;
            }
        }
        if ($artworkStatus) {
            return $artworkArr;
        } else {
            return $status;
        }
    }

    /**
     * GET: Create order zip file
     *
     * @param $orderNo     The is current request order Id
     * @param $orderItemId The is current request item Id
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return boolean
     */
    private function createOrderZipFileByItemId($orderNo, $orderItemId)
    {
        $fileExtention = 'svg,png,pdf';
        $status = false;
        $zipName = 'order_' . $orderNo . '_item_' . $orderItemId . '.zip';
        if (file_exists($this->orderPath . '/' . $zipName)) {
            unlink($this->orderPath . '/' . $zipName);
        }
        $assetFileExt = 'svg,pdf,png,jpeg,jpg,gif,bmp,ai,psd,eps,cdr,dxf,tif';
        $assetFileExt .= ',' . strtoupper($assetFileExt);
        $zip = new Zipper();
        $zipStatus = $zip->make($this->orderPath . '/' . $zipName);
        if ($zipStatus) {
            $orderFolderPath = $this->orderPath . $orderNo;
            $orderJsonPath = $orderFolderPath . '/order.json';
            if (file_exists($orderFolderPath)) {
                if (file_exists($orderJsonPath)) {
                    $orderJson = read_file($orderJsonPath);
                    $jsonContent = json_clean_decode($orderJson, true);
                    $itemList = $jsonContent['order_details']['order_items'];
                    $noOfRefIds = count($itemList);
                    if ($noOfRefIds > 0) {
                        $zip->addEmptyDir($orderNo);
                        //$zip->add($orderJsonPath, $orderNo . '/order.json');
                        foreach ($itemList as $itemDetails) {
                            $itemId = (int) $itemDetails['item_id'];
                            $refId = (int) $itemDetails['ref_id'];
                            $itemPath = $orderFolderPath . "/" . $itemId;
                            $ordeItemSide = $orderNo . "/" . $itemId . "/side_";
                            $designPath = $itemPath . "/designState.json";
                            if (($itemId > 0 && $refId > 0 && $refId != '-1')
                                && $itemDetails['item_id'] == $orderItemId
                            ) {
                                //Fetch the design state json details //
                                $designState = read_file($designPath);
                                $designData = json_clean_decode($designState, true);
                                if (is_array($designData['sides'])) {
                                    $sidesCount = count($designData['sides']);
                                    for ($flag = 1; $flag <= $sidesCount; $flag++) {
                                        if (is_dir($itemPath . "/side_" . $flag)) {
                                            $zip->addEmptyDir($ordeItemSide . $flag);
                                        }
                                        $sidePath = $ordeItemSide . $flag;

                                        //Add name and number csv file in zip
                                        $nameNumPath = $itemPath . "/nameNumber.csv";
                                        $addItemPath = $orderNo . "/" . $itemId;
                                        if (file_exists($nameNumPath)) {
                                            $optionsPath = array(
                                                'add_path' => $addItemPath . "/",
                                                'remove_path' => $itemPath,
                                            );
                                            $zip->addGlob(
                                                $itemPath . '/*{csv}',
                                                $optionsPath
                                            );
                                        }

                                        //Add side folder to zip file //
                                        $fromUrlSide = $itemPath . "/side_" . $flag;
                                        $optionsSide = array(
                                            'add_path' => $sidePath . "/",
                                            'remove_path' => $fromUrlSide,
                                        );
                                        $zip->addGlob(
                                            $fromUrlSide . '/*{' . $fileExtention . '}',
                                            $optionsSide
                                        );

                                        //Add asset folder to zip file//
                                        if (is_dir($fromUrlSide . "/assets")) {
                                            $zip->addEmptyDir(
                                                $sidePath . "/original_image"
                                            );
                                            $urlAsset = $fromUrlSide . "/assets";
                                            $addPath = $sidePath . "/original_image/";
                                            $optionsAsset = array(
                                                'add_path' => $addPath,
                                                'remove_path' => $urlAsset,
                                            );
                                            $zip->addGlob(
                                                $urlAsset . '/*{' . $assetFileExt . '}',
                                                $optionsAsset
                                            );
                                            $flagKey = $flag - 1;
                                            if (!empty($designData['sides'][$flagKey]['original_image_path'])) {
                                                $imageFormatFiles = $designData['sides'][$flagKey]['original_image_path'];
                                                foreach ($imageFormatFiles as $thisOrigFile) {
                                                    $fileToMove = ASSETS_PATH_W.ltrim($thisOrigFile, "/assets");
                                                    $fileNameOrig = array_pop(explode('/', $fileToMove));
                                                    $zip->add($fileToMove, $addPath.$fileNameOrig);
                                                }
                                            }
                                        }

                                        //Add preview folder to zip file//
                                        if (is_dir($fromUrlSide . "/preview")) {
                                            $zip->addEmptyDir($sidePath . "/preview");
                                            $fromUrlPrvw = $fromUrlSide . "/preview";
                                            $optionsPreview = array(
                                                'add_path' => $sidePath . "/preview/",
                                                'remove_path' => $fromUrlPrvw,
                                            );
                                            $zip->addGlob(
                                                $fromUrlPrvw . '/*{png,PNG}',
                                                $optionsPreview
                                            );
                                        }

                                        //remove svg preview file from zip file
                                        $zip->removeFile(
                                            $sidePath . "/preview_0" . $flag . ".svg"
                                        );

                                        //remove svg file as per print profile
                                        if (!empty($this->svgFileArr)) {
                                            foreach ($this->svgFileArr as $svgFile) {
                                                $svgRemovePath = $sidePath . '/' . $svgFile;
                                                $zip->removeFile($svgRemovePath);
                                            }
                                        }
                                    }
                                }
                            } else if (($itemId > 0 && $refId == '-1')
                                && $itemDetails['item_id'] == $orderItemId) {
                                $scanItemDir = scandir($itemPath);
                                if (is_valid_array($scanItemDir)) {
                                    foreach ($scanItemDir as $itemsDir) {
                                        if ($itemsDir != '.' && $itemsDir != '..') {
                                            $scanSideDir = scandir($itemPath . '/' . $itemsDir);
                                            if (is_valid_array($scanSideDir)) {
                                                foreach ($scanSideDir as $sideDir) {
                                                    if ($sideDir != '.' && $sideDir != '..' && $sideDir != 'preview') {
                                                        $zip->add($itemPath . '/' . $itemsDir . '/' . $sideDir, $orderNo . '/' . $itemId . '/' . $itemsDir . '/' . $sideDir);
                                                    } else if ($sideDir == 'preview') {
                                                        $scanItemPreviewDir = scandir($itemPath . '/' . $itemsDir . '/' . $sideDir);
                                                        if (is_valid_array($scanItemPreviewDir)) {
                                                            foreach ($scanItemPreviewDir as $itemPreviewDir) {
                                                                if ($itemPreviewDir != '.' && $itemPreviewDir != '..') {
                                                                    $zip->add($itemPath . '/' . $itemsDir . '/' . $sideDir . '/' . $itemPreviewDir, $orderNo . '/' . $itemId . '/' . $itemsDir . '/' . $sideDir . '/' . $itemPreviewDir);
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $status = true;
            }
            $zip->close();
        }
        return $status;
    }

    /**
     * GET: Download order file by order id
     *
     * @param $ordrIdArr The is current request order Id list
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return boolean
     */
    private function downloadOrderByOrderId($ordrIdArr = array(), $storeID = 1)
    {
        $status = false;
        $prvw = 'preview_0';
        $isS3Enabled = $this->checkS3Settings($storeID);
        if (!empty($ordrIdArr)) {
            foreach ($ordrIdArr as $orderId) {
                $orderAssetPath = $this->orderPath . $orderId;
                if ($isS3Enabled) {
                    $s3Download = $this->downloadS3Content("/assets/orders/".$orderId, $orderAssetPath, $storeID);
                }
                if (($orderId != "" && $orderId != 0)
                    && (file_exists($orderAssetPath) && is_dir($orderAssetPath))
                ) {
                    $scanProductDir = scandir($orderAssetPath);
                    if (is_valid_array($scanProductDir)) {
                        foreach ($scanProductDir as $itemId) {
                            if ($itemId != '.' && $itemId != '..'
                                && is_dir($orderAssetPath . "/" . $itemId)
                            ) {
                                $sidePath = $orderAssetPath . "/" . $itemId;
                                $designAbsDir = $sidePath . "/designState.json";
                                if (file_exists($designAbsDir)) {
                                    $designStr = read_file($designAbsDir);
                                    $designArr = json_clean_decode(
                                        $designStr, true
                                    );
                                    $scanSideDir = scandir($sidePath);
                                    if (file_exists($sidePath)
                                        && is_dir($sidePath)
                                        && is_valid_array($scanSideDir)
                                    ) {
                                        foreach ($scanSideDir as $side) {
                                            if ($side != '.'
                                                && $side != '..'
                                                && is_dir(
                                                    $sidePath . "/" . $side
                                                )
                                            ) {
                                                $i = str_replace(
                                                    "side_", "", $side
                                                );
                                                //Order side path
                                                $sDir = $sidePath . "/" . $side;
                                                //Order item path
                                                $iPath = $sDir . "/" . $side . "_";
                                                //Order SVG directory
                                                $svgDir = $iPath . $itemId . "_";
                                                //Order preview path
                                                $prvDir = $sDir . "/" . $prvw;
                                                //Order preview SVG file path
                                                $svgFile = $prvDir . $i . ".svg";
                                                if (file_exists($svgFile)
                                                    && !file_exists(
                                                        $svgDir . $orderId . ".svg"
                                                    )
                                                ) {
                                                    $status = $this->generateSvgFile(
                                                        $svgFile,
                                                        $orderId,
                                                        $itemId,
                                                        $designArr, false
                                                    );
                                                } else {
                                                    $status = true;
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    $status = true;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $status;
    }

    /**
     * GET: Create order zip file by order id
     *
     * @param $orderIdArr The is current request order id list
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return boolean
     */
    private function createOrderZipFileByOrderId($orderIdArr = array())
    {
        $fileExtention = 'svg,png,pdf';
        $assetFileExt = 'svg,pdf,png,jpeg,jpg,gif,bmp,ai,psd,eps,cdr,dxf,tif';
        $assetFileExt .= ',' . strtoupper($assetFileExt);
        $status = false;
        $orderZipName = '';
        $zipNameStr = count($orderIdArr) > 1 ? 'orders' : 'order';
        foreach ($orderIdArr as $orderId) {
            $orderZipName .= "_" . $orderId;
        }
        $zipName = $zipNameStr . $orderZipName . '.zip';
        if (file_exists($this->orderPath . '/' . $zipName)) {
            unlink($this->orderPath . '/' . $zipName);
        }
        $zip = new Zipper();
        $zipStatus = $zip->make($this->orderPath . '/' . $zipName);
        if (true) {
            foreach ($orderIdArr as $orderNo) {
                $orderFolderDir = $this->orderPath . $orderNo;
                $orderJsonPath = $orderFolderDir . '/order.json';
                if (file_exists($orderFolderDir) && file_exists($orderJsonPath)) {
                    $orderJson = read_file($orderJsonPath);
                    $jsonContent = json_clean_decode($orderJson, true);
                    $itemList = $jsonContent['order_details']['order_items'];
                    if (count($itemList) > 0) {
                        $zip->addEmptyDir($orderNo);
                        //$zip->add($orderJsonPath, $orderNo . '/order.json');
                        foreach ($itemList as $itemDetails) {
                            $itemId = $itemDetails['item_id'];
                            $refId = $itemDetails['ref_id'];
                            if ($itemId != null && $itemId > 0
                                && $refId != null && $refId > 0
                                && $refId != '-1'
                            ) {
                                $orderItemDir = $orderFolderDir . "/" . $itemId;
                                //Fetch the design state json details //
                                $designStr = read_file(
                                    $orderItemDir . "/designState.json"
                                );

                                //Add name and number csv file in zip
                                $nameNumberpath = $orderItemDir . "/nameNumber.csv";
                                if (file_exists($nameNumberpath)) {
                                    $optionsSides = array(
                                        'add_path' => $orderNo . "/" . $itemId . "/",
                                        'remove_path' => $orderItemDir,
                                    );
                                    $zip->addGlob(
                                        $orderItemDir . '/*{csv}',
                                        $optionsSides
                                    );
                                }

                                //Add name and number image folder
                                $nameNumberImagePath = $orderItemDir . "/nameNumber";
                                if (is_dir($nameNumberImagePath)) {
                                    $zip->addEmptyDir(
                                        $orderNo . "/" . $itemId . "/nameNumber"
                                    );
                                    $optionsPreview = array(
                                        'add_path' => $orderNo . "/" . $itemId . "/nameNumber/",
                                        'remove_path' => $nameNumberImagePath,
                                    );
                                    $zip->addGlob(
                                        $nameNumberImagePath . '/*{png,PNG,jpeg,JPEG,jpg,JPG}',
                                        $optionsPreview
                                    );
                                }

                                $resultDesign = json_clean_decode($designStr, true);
                                if (is_array($resultDesign['sides'])) {
                                    $sidesCount = count($resultDesign['sides']);
                                    $zipOrderIdDir = $orderNo . "/" . $itemId . "/side_";
                                    for ($flag = 1; $flag <= $sidesCount; $flag++) {
                                        if (is_dir($orderItemDir . "/side_" . $flag)
                                        ) {
                                            $zip->addEmptyDir(
                                                $zipOrderIdDir . $flag
                                            );
                                        }
                                        $sidePath = $zipOrderIdDir . $flag;
                                        //Add side folder to zip file //
                                        $fromUrlSide = $orderItemDir . "/side_" . $flag;
                                        $optionsSide = array(
                                            'add_path' => $sidePath . "/",
                                            'remove_path' => $fromUrlSide,
                                        );
                                        $zip->addGlob(
                                            $fromUrlSide . '/*{' . $fileExtention . '}',
                                            $optionsSide
                                        );

                                        //Add asset folder to zip file//
                                        if (is_dir($fromUrlSide . "/assets")) {
                                            $zip->addEmptyDir(
                                                $sidePath . "/original_image"
                                            );
                                            $urlAsset = $fromUrlSide . "/assets";
                                            $addPath = $sidePath . "/original_image/";
                                            $optionsAsset = array(
                                                'add_path' => $addPath,
                                                'remove_path' => $urlAsset,
                                            );
                                            $zip->addGlob(
                                                $urlAsset . '/*{' . $assetFileExt . '}',
                                                $optionsAsset
                                            );
                                            $flagKey = $flag - 1;
                                            if (!empty($resultDesign['sides'][$flagKey]['original_image_path'])) {
                                                $imageFormatFiles = $resultDesign['sides'][$flagKey]['original_image_path'];
                                                foreach ($imageFormatFiles as $thisOrigFile) {
                                                    $fileToMove = ASSETS_PATH_W.ltrim($thisOrigFile, "/assets");
                                                    $fileNameOrig = array_pop(explode('/', $fileToMove));
                                                    $zip->add($fileToMove, $addPath.$fileNameOrig);
                                                }
                                            }
                                        }

                                        //Add preview folder to zip file//
                                        if (is_dir($fromUrlSide . "/preview")) {
                                            $zip->addEmptyDir(
                                                $sidePath . "/preview"
                                            );
                                            $fromUrlPrvw = $fromUrlSide . "/preview";
                                            $optionsPreview = array(
                                                'add_path' => $sidePath . "/preview/",
                                                'remove_path' => $fromUrlPrvw,
                                            );
                                            $zip->addGlob(
                                                $fromUrlPrvw . '/*{png,PNG}',
                                                $optionsPreview
                                            );
                                        }
                                        //remove svg preview file from zip file
                                        $zip->removeFile(
                                            $sidePath . "/preview_0" . $flag . ".svg"
                                        );

                                        //remove svg file as per print profile
                                        if (!empty($this->svgFileArr)) {
                                            foreach ($this->svgFileArr as $svgFile) {
                                                $svgRemovePath = $sidePath . '/' . $svgFile;
                                                $zip->removeFile($svgRemovePath);
                                            }
                                        }
                                    }
                                }
                            } else {
                                $orderItemDir = $orderFolderDir . '/' . $itemId;
                                $scanItemDir = scandir($orderItemDir);
                                if (is_valid_array($scanItemDir)) {
                                    foreach ($scanItemDir as $itemsDir) {
                                        if ($itemsDir != '.' && $itemsDir != '..') {
                                            $scanSideDir = scandir($orderItemDir . '/' . $itemsDir);
                                            if (is_valid_array($scanSideDir)) {
                                                foreach ($scanSideDir as $sideDir) {
                                                    if ($sideDir != '.' && $sideDir != '..' && $sideDir != 'preview') {
                                                        $zip->add($orderItemDir . '/' . $itemsDir . '/' . $sideDir, $orderNo . '/' . $itemId . '/' . $itemsDir . '/' . $sideDir);
                                                    } else if ($sideDir == 'preview') {
                                                        $scanItemPreviewDir = scandir($orderItemDir . '/' . $itemsDir . '/' . $sideDir);
                                                        if (is_valid_array($scanItemPreviewDir)) {
                                                            foreach ($scanItemPreviewDir as $itemPreviewDir) {
                                                                if ($itemPreviewDir != '.' && $itemPreviewDir != '..') {
                                                                    $zip->add($orderItemDir . '/' . $itemsDir . '/' . $sideDir . '/' . $itemPreviewDir, $orderNo . '/' . $itemId . '/' . $itemsDir . '/' . $sideDir . '/' . $itemPreviewDir);
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                    $status = true;
                }
            }
            $zip->close();
        }
        return $status;
    }

    /**
     * GET: Create svg file according to print area dimension
     *
     * @param $reqSvgFile   The is current request SVG string
     * @param $orderId      The is current request order ID
     * @param $itemId       The is current request Item ID
     * @param $resultDesign The is current request design data
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return boolean
     */
    private function generateSvgFile($reqSvgFile, $orderId, $itemId, $resultDesign, $artworkStatus = false, $createSingleFile = false)
    {
        $svgStatus = false;
        $status = false;
        //Filter tag for invert color
        $filterS = '<filter xmlns="http://www.w3.org/2000/svg" id="invertcolor" ';
        $filterM = 'color-interpolation-filters="sRGB" x="0" y="0" height="100%" ';
        $filterE = 'width="100%">';
        $feColorMatrixS = '<feColorMatrix in="SourceGraphic" type="matrix" ';
        $feColorMatrixE = 'values="-1 0 0 0
            1 0 -1 0 0
            1 0 0 -1 0
            1 0 0 0 1 0"/></filter>';
        $this->filter = $filterS . $filterM . $filterE . $feColorMatrixS . $feColorMatrixE;

        if ($reqSvgFile != '') {
            $svgFileStr = read_file($reqSvgFile);
            $oldReplaceStr = array(
                'data: png',
                'data: jpg',
                'data: jpeg',
                'data:png',
                'data:jpg',
                'data:jpeg',
                'data:image/jpg',
            );
            $newReplaceStr = array(
                'data:image/png',
                'data:image/jpeg',
                'data:image/jpeg',
                'data:image/png',
                'data:image/jpeg',
                'data:image/jpeg',
                'data:image/jpeg',
            );
            $svgFileStr = str_replace($oldReplaceStr, $newReplaceStr, $svgFileStr);
            $svgFileExtention = basename($reqSvgFile);
            $sideNo = str_replace(
                "preview_0", "",
                str_replace(".svg", "", $svgFileExtention)
            );

            $this->sidePath = "side_" . $sideNo . "_" . $itemId . "_" . $orderId;
            $svgFileName = "single_".$this->sidePath . ".svg";
            $pngFileName = $this->sidePath . ".png";
            $rgbPdfFileName = $this->sidePath . "_rgb.pdf";
            $cmykPdfFileName = $this->sidePath . ".pdf";
            $multiPrintFileName = $this->sidePath . ".svg";
            $itemPath = $orderId . '/' . $itemId . '/side_' . $sideNo;
            $this->svgSavePath = $this->orderPath . $itemPath . '/';
            $svgAbsPath = $this->svgSavePath . $svgFileName;
            $pngAbsPath = $this->svgSavePath . $pngFileName;
            $rgbPdfAbsPath = $this->svgSavePath . $rgbPdfFileName;
            $cmykPdfAbsPath = $this->svgSavePath . $cmykPdfFileName;
            $sideIdIndex = $sideNo - 1;

            $sidePrintSvg = $resultDesign['sides'][$sideIdIndex];
            $this->printUnit = $resultDesign['sides'][$sideIdIndex]['print_unit']
            ? $resultDesign['sides'][$sideIdIndex]['print_unit'] : 'inch';
            $stickerInfo = $resultDesign['sides'][$sideIdIndex]['stickerInfo']
            ? $resultDesign['sides'][$sideIdIndex]['stickerInfo'] : array();
            $htmlStr = new \simple_html_dom();
            $htmlStr->load($svgFileStr, false);
            $svg = $htmlStr->find('image#svg_1', 0);
            $countLayer = substr_count($htmlStr, 'layer');
            $mainLayer = $htmlStr->find("g[xe_id^=Layer_]", 0); 
            $isSinglePrintFileEnabled = 0;
            $fileFormat = [];
            foreach($sidePrintSvg['print_area'] as $k => $printArea){
                if($printArea['is_single_printfile_enabled'] == 1)
                {
                    $isSinglePrintFileEnabled = 1;
                    $fileFormat = $printArea['allowed_order_formats'];
                }
            }
            $multiPrintStatus = false;
            if ($svg) {
                if (!is_dir($this->svgSavePath)) {
                    mkdir($this->svgSavePath, 0777, true);
                    chmod($this->svgSavePath, 0777);
                }
                if ($countLayer >= 1 || (isset($mainLayer) && $mainLayer != '')) {
                    $htmlStr->save();
                    if($isSinglePrintFileEnabled && !$createSingleFile) { 
                        //For single svg file for multiple boundary
                         $svgStatus = $this->generateSingleSvgFile(
                        $htmlStr, $this->svgSavePath, $fileFormat, $this->sidePath);
                    } else {
                        //For multiple svg files for multiple boundary
                        $svgStatus = $this->generateMultipleSvgFile(
                            $htmlStr, $multiPrintFileName,
                            $sidePrintSvg, $artworkStatus, $stickerInfo
                        );
                    }
                    if ($artworkStatus) {
                        return $svgStatus;
                    }
                    $multiPrintStatus = true;
                }
                $status = $svgStatus;
            }
        }
        return $status;
    }

    /**
     * GET: To create separate svg file for every print area
     *
     * @param $reqStr             The is current request SVG string
     * @param $multiPrintFileName The is current request SVG file name
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return boolean
     */
    private function generateMultipleSvgFile($reqStr,
        $multiPrintFileName, $sidePrintSvg, $artworkStatus = false, $stickerInfo
    ) {
        $stickerPath = '';
        $isStickerEnable = $isContourSvg = 0;
        if (!empty($stickerInfo)) {
            $stickerAHeight = $stickerInfo['height'];
            $stickerAWidht = $stickerInfo['width'];
            if ($stickerInfo['cutline'] == 'Small' || $stickerInfo['cutline'] == 'Medium' || $stickerInfo['cutline'] == 'Large' && $stickerInfo['contourSvg'] != '') {
                $contourSvg = $stickerInfo['contourSvg'];
                if ($contourSvg != '') {
                    $isContourSvg = 1;
                    $htmlSticker = new \simple_html_dom();
                    $htmlSticker->load($contourSvg, false);
                    $stickerPath = $htmlSticker->find('path', 0);
                    $stickerGroup = $htmlSticker->find('g', 0);
                    $stickerGroupHeight = $stickerGroup->height;
                    $stickerGroupWidth = $stickerGroup->width;
                    $stickerGroupX = $stickerGroup->x;
                    $stickerGroupY = $stickerGroup->y;
                }
            }
            if ($stickerInfo['cutline'] == 'Circle' || $stickerInfo['cutline'] == 'Rectangle' || $stickerInfo['cutline'] == 'Heart' || $stickerInfo['cutline'] == 'Star' || $stickerInfo['cutline'] == 'Round corner') {
                $isStickerEnable = 1;
            }
        }
        $printArea = $sidePrintSvg['print_area'];
        $fileStr = chop($multiPrintFileName, '.svg');
        $svgStartTag = '<svg xmlns="http://www.w3.org/2000/svg"';
        $svgXlink = ' id="svgroot" xmlns:xlink="http://www.w3.org/1999/xlink"';
        $svgEndTag = '</g></svg>';
        $svgTagStr = $svgStartTag . $svgXlink;
        $html = new \simple_html_dom();
        $html->load($reqStr, false);
        $svg = $html->find('image#svg_1', 0);
        $borderGStr = '';
        $borderG = $html->find('g#borderG', 0);
        if (isset($borderG) && $borderG != '') {
            $borderGStr = $borderG;
        }
        $svgFileStatus = false;
        if ($svg) {
            $mainLayer = '';
            $mainLayer = $html->find("g[class^=layer]");
            $defs = $html->find('defs', 0);
            $bounds = $defs->nextSibling();
            $bId = $bounds->getAttribute('id');
            $clipPath = $defs->find('clipPath[id^=mask_xe_]');
            if (isset($clipPath) && $clipPath != '') {
                foreach ($clipPath as $ck => $cv) {
                    $clipTransform = $clipPath[$ck]->transform;
                    unset($clipPath[$ck]->transform);
                    $firstChild = $clipPath[$ck]->first_child();
                    $firstChild->transform = $clipTransform;
                }
            }
            $feImage = $defs->find('feImage', 0);
            $envgFilter = $defs->find('filter', 0);
            $isEngrave = 0;
            $fillColor = '';
            if (isset($envgFilter) && $envgFilter != '') {
                $floodColor = 'flood-color';
                $envgFilterId = $envgFilter->id;
                $feFlood = $envgFilter->find('feFlood', 0);
                if (isset($feFlood) && $feFlood != '') {
                    $fillColor = $feFlood->$floodColor;
                }
                if (strpos($envgFilterId, 'engrave_') !== false) {
                    $isEngrave = 1;
                }
            }
            if (isset($feImage) && $feImage != '') {
                $isEngrave = 1;
                $feImage->outertext = '';
            }
            $vAlignWidthInch = $cropVal = $cropValPx = $bleedMarkMaxValue = 0;
            if (isset($mainLayer)) {
                $bounds = $gbleedM = '';
                $gbleedM = $html->find('g#bleedM', 0);
                if (isset($gbleedM) && $gbleedM !== '') {
                    $vAlignBright = $gbleedM->find('rect#vAlignBRight', 0);
                    $vAlignHeight = $vAlignBright->height;
                    $vAlignWidth = $vAlignBright->width;
                    $vAlignHeight = $vAlignBright->height;
                    $bleedMarkMaxValue = max($vAlignWidth, $vAlignHeight);
                    $vAlignWidthInch = (2 * $bleedMarkMaxValue) / $this->dpi;
                }
                $bounds = $html->find('g#' .$bId, 0);               
                if ($isStickerEnable) {
                    $bounds->display = 'block';
                } else {
                    $bounds->display = 'none';
                }

                foreach ($mainLayer as $k => $v) {
                    $clipPathUrl = $v->getAttribute('clip-path');
                    $boundId = substr(str_replace('url(#', '', $clipPathUrl), 0, -1); 
                    $printAreaId = 'bound_' . $k;
                    $path = $bounds->find('path#' . $boundId, 0);                     
                    if ($isStickerEnable) {
                        $path->style = "display:block";
                    } else {
                        $path->style = "display:none";
                    }
                    if (!isset($path) || $path == '') {
                        $path = $bounds->find('path#' . $boundId, 0);
                    }
                    $id = $mainLayer[$k]->id;
                    $isBleed = $path->isBleed;
                    if (isset($isBleed) && $isBleed) {
                        if ($this->printUnit == 'Feet') {
                            $this->printUnit = 'Inch';
                        }
                        $cropVal = $path->cropVal;
                        if ($this->printUnit == 'Pixel') {
                            $cropValPx = $cropVal;
                        } else {
                            $cropVal = $this->unitConvertionToInch($cropVal);
                            $cropValPx = $cropVal * $this->dpi;
                        }
                        $path->aHeight = $path->aHeight + ($cropVal * 2);
                        $path->aWidth = $path->aWidth + ($cropVal * 2);
                    }
                    if ($isContourSvg) {
                        $height = $stickerGroupHeight;
                        $width = $stickerGroupWidth;
                        $aHeight = $stickerAHeight;
                        $aWidth = $stickerAWidht;
                    } else {
                        $height = $path->height;
                        $width = $path->width;
                        $aHeight = $path->aHeight ? $path->aHeight : 0;
                        $aWidth = $path->aWidth ? $path->aWidth : 0;
                    }
                    //Print area dimension swapping height and width
                    if ((intval($aWidth) > intval($aHeight)) && (intval($height) > intval($width))) {
                        $temp = 0;
                        $temp = $aHeight;
                        $aHeight = $aWidth;
                        $aWidth = $temp;
                    }
                    if ((intval($aHeight) > intval($aWidth)) && (intval($width) > intval($height))) {
                        $temp = 0;
                        $temp = $aWidth;
                        $aWidth = $aHeight;
                        $aHeight = $temp;
                    }
                    if ($isContourSvg) {
                        $x = ($stickerGroupX) - ($bleedMarkMaxValue);
                        $y = ($stickerGroupY) - ($bleedMarkMaxValue);
                    } else {
                        $x = ($path->x) - ($bleedMarkMaxValue);
                        $y = ($path->y) - ($bleedMarkMaxValue);
                    }
                    if ($this->printUnit == 'Pixel') {
                        $acWidth = $aWidth;
                        $acHeight = $aHeight;
                    } else {
                        $aWidth = $this->unitConvertionToInch($aWidth);
                        $aHeight = $this->unitConvertionToInch($aHeight);
                        $acWidth = $aWidth * $this->dpi;
                        $acHeight = $aHeight * $this->dpi;
                    }
                    $acHeight = $acHeight / $height;
                    $acWidth = $acWidth / $width; 

                    if (strpos($mainLayer[$k], "layer") !== false) {
                        $xeProps = '';
                        $xeProps = $mainLayer[$k]->find(
                            'g[xe-props]', 0
                        );
                        //Start engrave mode diabled for color
                        $defFilter = $defs->find("filter#engrave_" . $k . "", 0);
                        if (isset($defFilter) && $defFilter != '') {
                            $defFilter->outertext = '';
                        }
                        //Start engrave mode diabled for image
                        if ($isEngrave) {
                            unset($mainLayer[$k]->filter);
                            $pathEvg = $mainLayer[$k]->find('path');
                            if (isset($pathEvg) && $pathEvg != '') {
                                foreach ($pathEvg as $gkd => $gid) {
                                    if (isset($fillColor) && $fillColor != '') {
                                        $pathEvg[$gkd]->fill = $fillColor;
                                    }
                                }
                            }
                        }
                        //End
                        $this->productImageTag = '';
                        //Check product with desin enable or not
                        if (isset($printArea[$printAreaId]['is_include_product_image'])
                            && $printArea[$printAreaId]['is_include_product_image'] == 'include'
                        ) {
                            $oldReplaceStr = array(
                                'data: png',
                                'data: jpg',
                                'data: jpeg',
                                'data:image/png',
                                'data:image/jpg',
                            );
                            $newReplaceStr = array(
                                'data:image/png',
                                'data:image/jpeg',
                                'data:image/jpeg',
                                'data:image/jpeg',
                                'data:image/jpeg',
                            );
                            $clippath = 'clip-path';
                            $svg->$clippath = 'url(#' . $printAreaId . ')';
                            $svgFileStr = str_replace($oldReplaceStr, $newReplaceStr, $svg);
                            $this->productImageTag = $svgFileStr;
                        }
                        //Prepared SVG
                        $aWidth = $aWidth * $this->dpi;
                        $aWidth = $aWidth + (2 * $cropValPx) + $vAlignWidthInch;
                        $aHeight = $aHeight * $this->dpi;
                        $aHeight = $aHeight + (2 * $cropValPx) + $vAlignWidthInch;
                        $svgWidth = ' width="' . $aWidth . '"';
                        $svgHeight = ' height="' . $aHeight . '"';
                        $svgXY = ' x="0" y="0" overflow="visible">';
                        //Check horizontal enabled or not
                        $hFilp = $printArea[$printAreaId]['is_horizontally_flip'];
                        if ($hFilp) {
                            $filpY = $aHeight + (($y) * 0);
                            $fTransform = '<g transform="translate(-0';
                            $transformS = $fTransform . ',' . $filpY . ') scale(';
                            if ($acHeight != $acWidth) {
                                $sDimension = $acWidth . ',' . $acHeight . ')';
                                $scaleValue = $transformS . $sDimension;

                            } else {
                                $scaleValue = $transformS . $acHeight . ')';
                            }
                            $filpSacle = ' scale(1,-1) translate(';
                            $xyValue = '-' . $x . ',-' . $y . ')">' . $stickerPath;
                            $transForm = $scaleValue . $filpSacle . $xyValue;
                        } else {
                            $scaleStr = '<g transform="scale(';
                            $scale = $scaleStr . $acWidth . ',' . $acHeight;
                            $translateX = ' translate(-' . $x . '';
                            $translateY = ',-' . $y . ')">' . $stickerPath;
                            $translate = $translateX . $translateY;
                            $transForm = $scale . ')' . $translate;
                        }
                        //Start engrave mode diabled for image
                        $feImages = $mainLayer[$k]->find('feImage');
                        if (isset($feImages) && !empty($feImages)) {
                            $imageFilter = $mainLayer[$k]->find('image');
                            if (isset($imageFilter) && $imageFilter != '') {
                                foreach ($imageFilter as $imgk => $imgid) {
                                    unset($imageFilter[$imgk]->filter);
                                }
                            }
                            foreach ($feImages as $keys => $img) {
                                $feImages[$keys]->outertext = '';
                            }
                        }
                        //Start engrave mode diabled for color
                        $filterG = $mainLayer[$k]->find("g");
                        if (isset($filterG) && $filterG != '') {
                            foreach ($filterG as $fg => $fgv) {
                                $filterValue = $filterG[$fg]->filter;
                                if ($filterValue == "url(#engrave_" . $k . ")") {
                                    unset($filterG[$fg]->filter);
                                }
                            }
                        }
                        //End

                        $svgTag = $svgTagStr . $svgWidth . $svgHeight;
                        $svgTagXY = $svgTag . $svgXY . $transForm . $this->productImageTag;
                        $svgMiddleTag = $svgTagXY . $defs . $bounds . $gbleedM;
                        $finalSvg = $svgMiddleTag . $mainLayer[$k] . $borderGStr . $svgEndTag;
                        $htmlSvg = $svgFinalString = '';
                        //Check invert color option enabled or not
                        $invertColor = $printArea[$printAreaId]['is_invert_color_enabled'];
                        if ($invertColor) {
                            $htmlSvg = new \simple_html_dom();
                            $htmlSvg->load($finalSvg, false);
                            $def = $htmlSvg->find('defs', 0);
                            $firstTag = $def->first_child();
                            $firstTag->outertext = $this->filter;
                            $groupLayer = $htmlSvg->find('g.layer');
                            foreach ($groupLayer as $key => $gl) {
                                $groupLayer[$key]->filter = "url(#invertcolor)";
                            }
                        } else {
                            $htmlSvg = $finalSvg;
                        }
                        $svgPath = $this->svgSavePath . 'Layer_'.$k;
                        $svgFilePath = $svgPath . '_' . $multiPrintFileName;
                        //png and pdf file name
                        $pngAbsPath = $svgPath . '_' . $fileStr . '.png';
                        $rgbPdfPath = $svgPath . '_' . $fileStr . '_rgb.pdf';
                        $cmykPdfAbsPath = $svgPath . '_' . $fileStr . '.pdf';
                        //Check bleed mark enabled or not per print profile
                        $bleedMarkEnabled = $printArea[$printAreaId]['is_bleed_mark_enabled'];
                        if ($bleedMarkEnabled && (isset($isBleed) && $isBleed)) {
                            $bleedMark = $printArea[$printAreaId]['bleed_mark'];
                            if ($bleedMark['cut_mark']) {
                                $htmlDom = new \simple_html_dom();
                                $htmlDom->load($htmlSvg, false);
                                $domBleedG = $htmlDom->find('g#bleedM', 0);
                                $domBleedG->display = 'block';
                                $svgFinalString = $htmlDom;
                            }
                        } else {
                            $svgFinalString = $htmlSvg;
                        }
                        //Check file format
                        $fileFormat = $printArea[$printAreaId]['allowed_order_formats'];
                        //Check used color for every individual product side
                        $isColorSeparation = $printArea[$printAreaId]['is_color_separation_enabled'];
                        if ($isColorSeparation && !$artworkStatus) {
                            //Used color list
                            $this->printColorsArr = $printArea[$printAreaId]['used_colors'];
                            $this->generateSvgFileByColor($svgFinalString, $id, $fileFormat);
                        }
                        if (!in_array('svg', $fileFormat)) {
                            array_push($this->svgFileArr, $id . '_' . $multiPrintFileName);
                        }
                        if($stickerInfo['stickerOption'] == 'sheet'){  
                            $this->generateStickerSheet($svgFinalString, $stickerInfo, $svgPath, $fileStr, $artworkStatus, $fileFormat);
                        }
                        if (!file_exists($svgFilePath)) {
                            $svgFileStatus = $artworkReturnStatus = write_file(
                                $svgFilePath, $svgFinalString
                            );
                        } else {
                            $svgFileStatus = $artworkReturnStatus = true;
                        }
                        if (!$artworkStatus) {
                            if (in_array('png', $fileFormat)) {
                                if (!file_exists($pngAbsPath)) {
                                    $this->svgConvertToPng(
                                        $pngAbsPath, $svgFilePath
                                    );
                                }
                            }
                            if (in_array('pdf', $fileFormat)) {
                                if (!file_exists($rgbPdfPath)) {
                                    $this->svgConvertToRGBPdf(
                                        $rgbPdfPath, $svgFilePath
                                    );
                                }
                                if (!file_exists($cmykPdfAbsPath)) {
                                    $this->rgbPdfConvertToCMYKPdf(
                                        $cmykPdfAbsPath, $rgbPdfPath
                                    );
                                }
                            }
                        }
                    }
                }
            }
        }
        if ($artworkStatus) {
            return $artworkReturnStatus;
        } else {
            return $svgFileStatus;
        }
    }

    /**
     * GET: To create output files for multiple boundary products
     *
     * @param $reqStr     The is current request SVG string
     * @param $svgAbsPath The is current request SVG path
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return boolean
     */
    private function generateSingleSvgFile($reqStr, $svgSavePath, $fileFormat, $sidePath)
    {
        $svgFileStatus = false;
        $html = new \simple_html_dom();
        $html->load($reqStr, false);
        $svg = $html->find('image#svg_1', 0);
        $svgroot = $html->find('svg#svgroot', 0);
        $defs = $html->find('defs', 0);
        if ($svg) {
            $mainContent = true;
            //$mainContent = $html->find('g.mainContent', 0);
            $aHeighArr = array();
            $aWidthArr = array();
            if ($mainContent) {
                $main = $html->find("g[class^=layer]");
                $defs = $html->find('defs', 0);
                $bounds = $defs->nextSibling();
                $bId = $bounds->getAttribute('id');
                $bounds = $html->find('g#' .$bId, 0);                
                foreach ($main as $k => $g) {
                    $id = $main[$k]->id;
                    $clipPathUrl = $g->getAttribute('clip-path');
                    $boundId = substr(str_replace('url(#', '', $clipPathUrl), 0, -1); 
                    $path = $bounds->find('path#' . $boundId, 0);                    
                    $height = $path->height;
                    $width = $path->width;
                    $aHeight = $path->aHeight ? $path->aHeight : 0;
                    $aWidth = $path->aWidth ? $path->aWidth : 0;
                    $x = $path->x;
                    $y = $path->y;
                    $aWidth = $this->unitConvertionToInch($aWidth);
                    $aHeight = $this->unitConvertionToInch($aHeight);
                    $acHeight = $aHeight * $this->dpi;
                    $acHeight = $acHeight / $height;
                    $acWidth = $aWidth * $this->dpi;
                    $acWidth = $acWidth / $width;
                    array_push($aHeighArr, $acHeight);
                    array_push($aWidthArr, $acWidth);
                    $path->style = "display:none";
                    /*if ($id == "Layer_" . $k . "") {
                        if (strpos($main[$k], "layer") !== false) {
                            $xeProps = '';
                            $xeProps = $main[$k]->find(
                                'g[xe-props]', 0
                            );
                            if (isset($xeProps) && $xeProps != '') {
                                $scale = $acWidth . ',' . $acHeight;
                                $main[$k]->transform = 'scale(' . $scale . ')';
                            }
                        }
                    }*/
                }
                foreach($main as $k => $g){
                    $id = $main[$k]->id;
                    $xe_id = $main[$k]->xe_id;                    
                    if (strpos($main[$k], "layer") !== false) {
                        $xeProps = '';
                        $xeProps = $main[$k]->find(
                            'g[xe-props]', 0
                        );                        
                        $scale = max($aWidthArr) . ',' .  max($aHeighArr);
                        $main[$k]->transform = 'scale(' . $scale . ')';
                    }
                }
                $maxWidth = max($aWidthArr);
                $maxHeight = max($aHeighArr);
                $svgHeigh = $maxHeight * 600;
                $svgWidth = $maxWidth * 600;
                $svgroot->width = $svgWidth;
                $svgroot->height = $svgHeigh;
                $svg->outertext = '';
                $html->save();
                $html = str_replace('</svg>', '', $html);
                $html = $html . '</svg>';
                $svgAbsPath = $svgSavePath.'single_'.$sidePath.'.svg';
                $svgFileStatus = write_file($svgAbsPath, $html);
                $rgbPdfPath = $svgSavePath.'single_'.$sidePath.'_rgb.pdf';
                $cmykPdfAbsPath = $svgSavePath.'single_'.$sidePath.'.pdf';
                $pngAbsPath = $svgSavePath.'single_'.$sidePath.'.png';
                if(file_exists($svgAbsPath)){
                    if (in_array('png', $fileFormat)) {
                        if (!file_exists($pngAbsPath)) {
                            $this->svgConvertToPng(
                                $pngAbsPath, $svgAbsPath
                            );
                        }
                    }
                    if (in_array('pdf', $fileFormat)) {
                        if (!file_exists($rgbPdfPath)) {
                            $this->svgConvertToRGBPdf(
                                $rgbPdfPath, $svgAbsPath
                            );
                        }
                        if (!file_exists($cmykPdfAbsPath)) {
                            $this->rgbPdfConvertToCMYKPdf(
                                $cmykPdfAbsPath, $rgbPdfPath
                            );
                        }
                    }
                }
            }
        }
        return $svgFileStatus;
    }

    /**
     * GET: Create separate svg file by print color for reactngle print
     *
     * @param $svgStr The is current request SVG string
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return boolean
     */
    private function generateSvgFileByColor($svgStr = null, $groupId = null, $fileFormat = array())
    {
        $itemSidePath = $this->svgSavePath . $groupId . '_' . $this->sidePath;
        $svgFileStatus = false;
        $html = new \simple_html_dom();
        $html->load($svgStr, false);
        $pattern = $html->find("pattern[id^=p]");
        $main = $html->find("g#" . $groupId . "", 0);
        $pathStyle = $main->find('path'); //Get all path from svg string
        if (isset($pathStyle) && $pathStyle) {
            foreach ($pathStyle as $k => $v) {
                if ($pathStyle[$k]->id != 'boundMask') {
                    $pathStyle[$k]->style = "display:none"; // Hide all path
                }
            }
        }

        $polygonStyle = $main->find('polygon'); //Get all polygon from svg string
        if (isset($polygonStyle) && $polygonStyle) {
            foreach ($polygonStyle as $k => $v) {
                $polygonStyle[$k]->style = "display:none"; // Hide all polygon
            }
        }
        $html->save();
        $htmlStr = new \simple_html_dom();
        $htmlStr->load($html, false);
        $background = $htmlStr->find('g#background', 0);
        if (isset($background) && $background != '') {
            $image = $background->find('image', 0);
            if (isset($image) && $image != '') {
                $background->style = "display:block";
            } else {
                $background->style = "display:none";
            }
        }
        $path = [];
        if (!empty($this->printColorsArr)) {
            foreach ($this->printColorsArr as $k => $color) {
                if ($color[0] == "#") {
                    $path = $htmlStr->find('path[fill^=' . $color . ']');
                    $pathId = '';
                    if (!empty($path)) {
                        foreach ($path as $key => $fill) {
                            $pathId = $path[$key]->id;
                            $pathTxt = $htmlStr->find("path[id^=" . $pathId . "]", 0);
                            $pathTxt->style = "display:block";
                            $strokeColor = $pathTxt->stroke;
                            $fillColor = $pathTxt->fill;
                            if (isset($strokeColor) && $strokeColor != '') {
                                $pathTxt->stroke = $color;
                                $pathTxt->fillid = $fillColor;
                                $pathTxt->strokeid = $strokeColor;
                            }
                        }
                    } else {
                        $rect = $background->find('rect[fill^=' . $color . ']', 0);
                        if (isset($rect) && $rect != '') {
                            $background->style = "display:block";
                        }
                    }

                    /*$pathStroke = $htmlStr->find('path[strokeid^=' . $color . ']');
                    if (isset($pathStroke) && $pathStroke != '') {
                    $pathStrokeId = '';
                    $strokeWidthStr = 'stroke-width';
                    $strokeWidth = 0;
                    foreach ($pathStroke as $k => $stroke) {
                    $pathStrokeId = $pathStroke[$k]->id;
                    $pathStrokeTxt = $htmlStr->find("path[id^=" . $pathStrokeId . "]", 0);
                    $strokeWidth = $pathStrokeTxt->$strokeWidthStr;
                    if ($strokeWidth == 0) {
                    $pathStrokeTxt->style = "display:none";
                    } else {
                    $pathStrokeTxt->style = "display:block";
                    }
                    $pathStrokeTxt->fill = $color;
                    $pathStrokeTxt->stroke = $color;
                    }

                    }*/
                    $polygon = $htmlStr->find('polygon[fill^=' . $color . ']');
                    $polygonId = '';
                    foreach ($polygon as $key => $value) {
                        $polygonId = $polygon[$key]->id;
                        $polygonTxt = $htmlStr->find(
                            "polygon[id^=" . $polygonId . "]", 0
                        );
                        $polygonTxt->style = "display:block";
                    }

                    //png and pdf file name
                    $pngAbsPath = $itemSidePath . '_' . $color . '.png';
                    $rgbPdfPath = $itemSidePath . '_' . $color . '_rgb.pdf';
                    $cmykPdfAbsPath = $itemSidePath . '_' . $color . '.pdf';
                    //svg file
                    $svgPath = $itemSidePath . "_" . $color . '.svg';
                    $svgFileName = $groupId . '_' . $this->sidePath . "_" . $color . '.svg';
                    $svgFileStatus = write_file($svgPath, $htmlStr);
                    if (!in_array('svg', $fileFormat)) {
                        array_push($this->svgFileArr, $svgFileName);
                    }

                    //get all group path
                    $pathSvg = $htmlStr->find('path');
                    foreach ($pathSvg as $k => $v) {
                        if ($pathSvg[$k]->id != 'boundMask') {
                            $pathSvg[$k]->style = "display:none";
                        }
                    }

                    //get all clip path
                    $clipPathArray = $htmlStr->find('clipPath');
                    if (isset($clipPathArray) && $clipPathArray) {
                        foreach ($clipPathArray as $k => $v) {
                            $clipPath = $clipPathArray[$k]->find('path', 0);
                            if (isset($clipPath) && $clipPath) {
                                $clipPath->style = "display:block";
                            }
                        }
                    }

                    //get all polygon group path
                    $polygonSvg = $htmlStr->find('polygon');
                    foreach ($polygonSvg as $k => $v) {
                        $polygonSvg[$k]->style = "display:none";
                    }
                } else {
                    if ($color != '' && (filter_var($color, FILTER_VALIDATE_URL))) {
                        $patternId = '';
                        $baseFileName = basename($color);
                        $fileNameArr = explode(".", $baseFileName);
                        $patternId = $fileNameArr[0];
                        if (isset($pattern) && $pattern) {
                            $pathPattern = $htmlStr->find(
                                'path[fill^=url(#' . $patternId . ')]'
                            );
                            $pathIdPattern = '';
                            foreach ($pathPattern as $kk => $ppv) {
                                $pathIdPattern = $pathPattern[$kk]->id;
                                $pathTxtPattern = $htmlStr->find(
                                    "path[id^=" . $pathIdPattern . "]", 0
                                );
                                $pathTxtPattern->style = "display:block";
                            }
                        }
                        //png and pdf file name
                        $pngAbsPath = $itemSidePath . '_' . $patternId . '.png';
                        $rgbPdfPath = $itemSidePath . '_' . $patternId . '_rgb.pdf';
                        $cmykPdfAbsPath = $itemSidePath . '_' . $patternId . '.pdf';
                        //SVg file
                        $svgFileName = $this->sidePath . "_" . $patternId . '.svg';
                        $svgPath = $this->svgSavePath . $svgFileName;
                        if (!file_exists($svgPath)) {
                            $svgFileStatus = write_file($svgPath, $htmlStr);
                        } else {
                            $svgFileStatus = true;
                        }
                        //Get all pattern path from svg string
                        $pathStylePattern = $htmlStr->find('path');
                        foreach ($pathStylePattern as $kkk => $pspv) {
                            if ($pathStylePattern[$kkk]->id != 'boundMask') {
                                // Hide all path
                                $pathStylePattern[$kkk]->style = "display:none";
                            }
                        }
                    }
                }
                if ($svgFileStatus) {
                    if (in_array('png', $fileFormat)) {
                        if (!file_exists($pngAbsPath)) {
                            $this->svgConvertToPng($pngAbsPath, $svgPath);
                        }
                    }
                    if (in_array('pdf', $fileFormat)) {
                        if (!file_exists($rgbPdfPath)) {
                            $this->svgConvertToRGBPdf(
                                $rgbPdfPath, $svgPath
                            );
                        }
                        if (!file_exists($cmykPdfAbsPath)) {
                            $this->rgbPdfConvertToCMYKPdf(
                                $cmykPdfAbsPath, $rgbPdfPath
                            );
                        }
                    }
                }
            }
        }
        return $svgFileStatus;
    }

    /**
     * GET: SVG file convert to PNG file through imagick
     *
     * @param $pngAbsPath The is current request PNG path
     * @param $svgAbsPath The is current request SVG path
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return nothing
     */
    public function svgConvertToPng($pngAbsPath, $svgAbsPath)
    {
        $returnResult = $this->checkInkscape();
        if ($returnResult['status'] && file_exists($svgAbsPath)) {
            $shellFun = $returnResult['value'];
            $cmdPng = "inkscape " . escapeshellarg(
                $svgAbsPath
            ) . " --export-png=" . escapeshellarg(
                $pngAbsPath
            ) . " --without-gui";
            $shellFun($cmdPng);
        }
    }

    /**
     * GET: SVG file convert to RGB pdf file through Inkscape
     *
     * @param $rgbPdfAbsPath The is current request RGB  PDF path
     * @param $svgAbsPath    The is current request SVG path
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return nothing
     */
    private function svgConvertToRGBPdf($rgbPdfAbsPath, $svgAbsPath)
    {
        $returnResult = $this->checkInkscape();
        if ($returnResult['status'] && file_exists($svgAbsPath)) {
            $shellFun = $returnResult['value'];
            $cmdPdf = "inkscape " . escapeshellarg(
                $svgAbsPath
            ) . " --export-pdf=" . escapeshellarg(
                $rgbPdfAbsPath
            );
            $shellFun($cmdPdf);
        }
    }

    /**
     * GET: RGB pdf file convert to CMYK pdf file through Ghostscript
     *
     * @param $cmykPdfAbsPath The is current request CYMK  PDF path
     * @param $rgbPdfAbsPath  The is current request RGB  PDF path
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return nothing
     */
    private function rgbPdfConvertToCMYKPdf($cmykPdfAbsPath, $rgbPdfAbsPath)
    {
        $returnResult = $this->checkGhostScript();
        if ($returnResult['status'] && file_exists($rgbPdfAbsPath)) {
            $cmykPdfAbsPath = escapeshellarg($cmykPdfAbsPath);
            $fromRgbPdfAbsPath = escapeshellarg($rgbPdfAbsPath);
            $shellFun = $returnResult['value'];
            $cmdPdfCmyk = "gs -dSAFER -dBATCH \-dNOPAUSE -dNOCACHE -sDEVICE=pdfwrite -dAutoRotatePages=/None \-sColorConversionStrategy=CMYK \-dProcessColorModel=/DeviceCMYK \ -sOutputFile=" . $cmykPdfAbsPath . " \ " . $fromRgbPdfAbsPath;
            $shellFun($cmdPdfCmyk);
            if (file_exists($rgbPdfAbsPath)) {
                unlink($rgbPdfAbsPath); //remove rgb pdf file
            }
        }
    }

    /**
     * GET: Check Inkscape is avialable or not in server
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return array of shell enabled function
     */
    private function checkInkscape()
    {
        $result['status'] = true;
        $result['value'] = 'shell_exec';
        $retrunResult = $this->getShellEnabledFunction();
        if ($retrunResult['status']) {
            $shell_function = $retrunResult['value'];
            system("inkscape --version > /dev/null", $retvalInk);
            if ($retvalInk == 0) {
                $result['status'] = true;
                $result['value'] = $shell_function;
            } else {
                if ($shell_function == 'exec' && empty($retvalInk)) {
                    $result['status'] = true;
                    $result['value'] = $shell_function;
                } else {
                    $result['status'] = false;
                    $result['value'] = '';
                }
            }
        }
        return $result;
    }

    /**
     * GET: Check Ghostscript is avialable or not in server
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return array of shell enabled function
     */
    private function checkGhostScript()
    {
        $result['status'] = true;
        $result['value'] = 'shell_exec';
        $retrunResult = $this->getShellEnabledFunction();
        if ($retrunResult['status']) {
            $shell_function = $retrunResult['value'];
            system("gs --version > /dev/null", $retvalInk);
            if ($retvalInk == 0) {
                $result['status'] = true;
                $result['value'] = $shell_function;
            } else {
                if ($shell_function == 'exec' && empty($retvalInk)) {
                    $result['status'] = true;
                    $result['value'] = $shell_function;
                } else {
                    $result['status'] = false;
                }
            }
        }
        return $result;
    }

    /**
     * GET: Check enabled/disabled function in server(php.ini file)
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return array of shell enabled function
     */
    private function getShellEnabledFunction()
    {
        $result = array();
        //default function
        $disableFunctions = ini_get("disable_functions");
        if ($disableFunctions != '') {
            $disableFunctionsArr = explode(',', rtrim($disableFunctions, ','));
        } else {
            $result['status'] = true;
            $result['value'] = 'shell_exec';
        }
        //all default function for run shell command
        $deafaultShell = array(
            "passthru",
            "exec",
            "system",
            "shell_exec",
        );
        if (!empty($disableFunctionsArr)) {
            foreach ($deafaultShell as $value) {
                if (!in_array($value, $disableFunctionsArr)) {
                    $result['value'] = $value;
                    $result['status'] = true;
                } else {
                    $result['value'] = '';
                    $result['status'] = false;
                }
            }
        }
        return $result;
    }

    /**
     * GET: Check enabled/disabled function in server(php.ini file)
     *
     * @param $value unit value in string
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return float or Integer
     */
    private function unitConvertionToInch($value)
    {
        $result = 0;
        switch ($this->printUnit) {
            case 'Centimeter':
                return $result = ($value / 2.54);
                break;
            case 'Millimeter':
                return $result = ($value / 25.4);
                break;
            case 'Feet':
                return $result = ($value * 12);
                break;
            default:
                return $value;
                break;
        }
    }

    /**
     * GET: Download order zip
     *
     * @param $isDownload check for boolean value
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return float or Integer
     */
    private function zipDownload($isDownload)
    {
        //Travarse zip file name in order folder
        $fileArr = glob($this->orderPath . "*.zip");
        foreach ($fileArr as $file) {
            $zipName = $file;
        }
        if ($isDownload) {
            return $this->zipFileDownload($zipName);
        } else {
            return false;
        }
    }

    /**
     * GET: Download Zip file
     *
     * @param $dir This is for zip download directory
     *
     * @author radhanatham@riaxe.com
     * @date   03 Jan 2020
     * @return boolean
     */
    private function zipFileDownload($dir)
    {
        if (file_exists($dir)) {
            header('Content-Description: File Transfer');
            header("Content-type: application/x-msdownload", true, 200);
            header('Content-Disposition: attachment; filename=' . basename($dir));
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header("Pragma: no-cache");
            header('Content-Length: ' . filesize($dir));
            readfile($dir);
            if (file_exists($dir)) {
                unlink($dir);
            }
            $status = true;
            exit();
        } else {
            $status = false;
        }
        return $status;
    }

    /**
     * GET: Conver JSOn file to CSV file and save corresponding directory
     *
     * @param $data Array data
     * @param $cfilename CSV file name
     * @param $headerData CSV header row data
     * @param $orderPath This is for directory for save CSV file
     *
     * @author radhanatham@riaxe.com
     * @date   27 May 2020
     * @return boolean
     */
    private function jsonToCSV($data, $cfilename, $headerData, $orderPath)
    {
        $templateImagePath = path('abs', 'product');
        $deisgnPreviewPath = path('abs', 'design_preview') . 'carts';
        $templatesDesignPrvPath = path('abs', 'design_preview') . 'templates';
        $header = [];
        if (!empty($headerData)) {
            foreach ($headerData as $k => $v) {
                if ($v['type'] != 'remove') {
                    $header[] = $v['name'];
                }
            }
            $rows = [];
            $rows[0] = $header;
            foreach ($data as $key => $value) {
                foreach ($value as $k => $row) {
                    if ($row['type'] != 'remove') {
                        if ($row['type'] == 'image') {
                            $fileName = basename($row['value']);
                            if (strpos($row['value'], "products") !== false
                            ) {
                                $pngFilePath = $templateImagePath . $fileName;
                            } else if (strpos($row['value'], "templates") !== false) {
                                $pngFilePath = $templatesDesignPrvPath . '/' . $fileName;
                            } else {
                                $pngFilePath = $deisgnPreviewPath . '/' . $fileName;
                            }
                            $customizeImage = read_file($pngFilePath);
                            $nameNumberItemPath = $orderPath . '/nameNumber/';
                            if (!file_exists($nameNumberItemPath)) {
                                mkdir($nameNumberItemPath, 0755);
                            }
                            if (is_dir($nameNumberItemPath)) {
                                $nameNumberItemPathFile = $nameNumberItemPath . $fileName;
                                if (!file_exists($nameNumberItemPathFile)) {
                                    write_file($nameNumberItemPathFile, $customizeImage);
                                }
                            }
                            $rows[$key + 1][] = basename($row['value']);
                        } else {
                            $rows[$key + 1][] = $row['value'];
                        }
                    }
                }
            }
            if (!empty($rows)) {
                $cfilename = $orderPath . '/' . $cfilename;
                if (is_dir($orderPath)) {
                    $fp = fopen($cfilename, 'w');
                    foreach ($rows as $fields) {
                        fputcsv($fp, $fields);
                    }
                }
                fclose($fp);
            }
        }
        return true;
    }

    /**
     * GET: Create Assets per order item by order id
     *
     * @param $args         Slim's Request object
     * @param $orderDetails order details array
     *
     * @author radhanatham@riaxe.com
     * @date   05 March 2020
     * @return boolean
     */
    public function createOrderAssetFile($args, $orderDetails, $quotation = false)
    {
        $status = 0;
        $jsonStr = json_encode($orderDetails);
        if (isset($args['id']) && $args['id'] > 0) {
            $orderId = $args['id'];
            $orderAbsPath = path('abs', 'order');
            $orderPath = $orderAbsPath . $orderId;
            $templateImagePath = path('abs', 'product');
            $deisgnStatePath = path('abs', 'design_state') . 'carts';
            $deisgnPreviewPath = path('abs', 'design_preview') . 'carts';
            $deisgnStatePredecoPath = path('abs', 'design_state') . 'predecorators';
            $templatesDesignPrvPath = path('abs', 'design_preview') . 'templates';
            $quoteDeisgnStatePath = path('abs', 'design_state') . 'artworks';
            $orderJson = $orderPath . "/order.json";
            if (!is_dir($orderPath) || $quotation) {
                if (!is_dir($orderPath)) {
                    mkdir($orderPath, 0755, true);
                }
                if (is_dir($orderPath)) {
                    $status = write_file($orderJson, $jsonStr);
                }
                foreach ($orderDetails['order_details']['order_items'] as $items) {
                    if ($items['ref_id'] != '' && $items['ref_id'] != 0 && $items['ref_id'] != '-1') {
                        $variantId = $items['variant_id'];
                        if (strtolower(STORE_NAME) == "shopify") {
                            $storeProductInit = new StoreProductsController();
                            $parentProductID = $storeProductInit->getOriginalVarID($variantId);
                            $variantId = $parentProductID;
                        }
                        $itemId = $items['item_id'];
                        $itemPath = $orderPath . "/" . $itemId;

                        if (!is_dir($itemPath)) {
                            mkdir($itemPath, 0755, true);
                        }
                        $refId = $items['ref_id'];
                        $isPredecoFlag = false;
                        if (file_exists($deisgnStatePath . '/' . $refId . '.json')) {
                            $isPredecoFlag = false;
                            $designStateJson = read_file(
                                $deisgnStatePath . '/' . $refId . '.json'
                            );
                        } else if (isset($items['is_quote_order']) && $items['is_quote_order'] == 1) {
                            $isPredecoFlag = true;
                            $designStateJson = read_file(
                                $quoteDeisgnStatePath . '/' . $refId . '.json'
                            );
                        } else {
                            $isPredecoFlag = true;
                            $designStateJson = read_file(
                                $deisgnStatePredecoPath . '/' . $refId . '.json'
                            );
                        }
                        $jsonContent = json_clean_decode($designStateJson, true);
                        //If order created from quotation for pre-deco product
                        if (empty($jsonContent)) {
                            $isPredecoFlag = true;
                            $designStateJson = read_file(
                                $deisgnStatePredecoPath . '/' . $refId . '.json'
                            );
                            $jsonContent = json_clean_decode($designStateJson, true);
                        }
                        $captureUrls = [];
                        if (!empty($jsonContent)) {
                            if (!empty($jsonContent['design_product_data'])) {
                                foreach (
                                    $jsonContent['design_product_data'] as $deisgnUrl
                                ) {
                                    if ($isPredecoFlag) {
                                        if (!empty($deisgnUrl['design_urls'])) {
                                            $captureUrls = $deisgnUrl['design_urls'];
                                        }
                                    } else {
                                        if (in_array($variantId, $deisgnUrl['variant_id'])) {
                                            if (!empty($deisgnUrl['design_urls'])) {
                                                $captureUrls = $deisgnUrl['design_urls'];
                                            }
                                        }
                                    }
                                }
                            }
                            if (is_dir($itemPath)) {
                                $status = write_file(
                                    $itemPath . '/designState.json', $designStateJson
                                );
                            }
                            //For name and number
                            if (isset($jsonContent['name_number']) && !empty($jsonContent['name_number'])) {

                                $headerData = $jsonContent['name_number'][0];
                                $rowData = $jsonContent['name_number'];
                                $csvFilename = 'nameNumber.csv';
                                $this->jsonToCSV($rowData, $csvFilename, $headerData, $itemPath);
                            }
                            //For preview folder image file
                            foreach ($jsonContent['sides'] as $k => $v) {

                                if (isset($v['svg']) && !empty($v['svg'])) {
                                    $sideNo = $k + 1;
                                    $itemSidePath = $itemPath . "/side_" . $sideNo . "";
                                    //for preview folder image file
                                    $customizeImage = '';
                                    if (isset($captureUrls[$k])
                                        && !empty($captureUrls[$k])
                                    ) {
                                        $fileName = basename($captureUrls[$k]);
                                        if (strpos($captureUrls[$k], "products") !== false
                                        ) {
                                            $pngFilePath = $templateImagePath . $fileName;
                                        } else if (strpos($captureUrls[$k], "templates") !== false) {
                                            $pngFilePath = $templatesDesignPrvPath . '/' . $fileName;
                                        } else {
                                            $pngFilePath = $deisgnPreviewPath . '/' . $fileName;
                                        }
                                        $customizeImage = read_file($pngFilePath);
                                    }
                                    $previewItemPath = $itemSidePath . "/preview/";
                                    $sidePrvw = $itemSidePath . "/preview/side_" . $sideNo;
                                    $itemPrvw = $sidePrvw . "_" . $itemId . "_";
                                    $pngFile = $itemPrvw . $orderId . "_preview.png";
                                    if (!is_dir($previewItemPath)) {
                                        if (!file_exists($previewItemPath)) {
                                            mkdir($previewItemPath, 0755, true);
                                        }
                                    }

                                    if (is_dir($previewItemPath)) {
                                        if (!file_exists($pngFile)) {
                                            write_file($pngFile, $customizeImage);
                                        }
                                    }

                                    if (!is_dir($itemSidePath)) {
                                        if (!file_exists($itemSidePath)) {
                                            mkdir($itemSidePath, 0755, true);
                                        }
                                    }
                                    $svgPrvwPath = $itemSidePath . "/preview_0";
                                    $svgSidePath = $svgPrvwPath . $sideNo . ".svg";
                                    $parameter = array(
                                        'key' => $k, 'ref_id' => $refId,
                                        'item_path' => $itemSidePath,
                                        'svg_preview_path' => $svgSidePath,
                                        'value' => $v,
                                    );
                                    $status = $this->createSideSvgByOrderId($parameter);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $status;
    }

    /**
     * GET: Create SVG preview file by order item
     *
     * @param $parameter SVG details array
     *
     * @author radhanatham@riaxe.com
     * @date   05 March 2020
     * @return boolean
     */
    public function createSideSvgByOrderId($parameter)
    {
        $domainUrl = (isset($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] === 'on'
            ? "https" : "http"
        );
        $domainUrl .= "://" . $_SERVER['HTTP_HOST'];
        $relativePath = $_SERVER['DOCUMENT_ROOT'];
        $userAssetPath = path('abs', 'user');
        $refId = $parameter['ref_id'];
        $itemPath = $parameter['item_path'];
        $svgPreviewPath = $parameter['svg_preview_path'];
        $jsonData = $parameter['value'];
        $isDesign = $parameter['is_design'] ? $parameter['is_design'] : 0;
        $imageData = (object) $jsonData;
        if (!empty($jsonData) && $refId && !empty($imageData)) {
            try {
                $svgData = $imageData->svg;
                $productURL = $imageData->url;
                if (strtolower(STORE_NAME) == "shopify") {
                    $productImgContent = @file_get_contents($productURL);
                } else {
                    $productURLBasePath = str_replace(
                        $domainUrl, $relativePath, $productURL
                    );
                    $productImgContent = read_file($productURLBasePath);
                }
                $base64ProductImgData = base64_encode($productImgContent);
                $svgPreviewDatas = $this->parseSVGString($svgData);
                if (!empty($imageData->assets_doc_path)) {
                    foreach ($imageData->assets_doc_path as $key => $image) {
                        if (!empty($image)) {
                            $fileName = basename($image);
                            $imageArr = explode('/assets/', $image);
                            $userImagepath = $itemPath . '/assets/';
                            $imagePath = ASSETS_PATH_W . $imageArr['1'];
                            $userImageFileName = $userImagepath . $fileName;
                            if (file_exists($imagePath)) {
                                if (!is_dir($userImagepath)) {
                                    mkdir($userImagepath, 0777, true);
                                }
                                copy($imagePath, $userImageFileName);
                            }
                        }
                    }
                }
                $svgPreviewData = str_ireplace(
                    array(
                        'data: png', 'data: jpg', '<svg', '</svg>',
                    ), array(
                        'data:image/png', 'data:image/jpg', '<g', '</g>',
                    ), $svgPreviewDatas['svgStringwithImageURL']
                );
                $html = new \simple_html_dom();
                $html->load($svgPreviewData, false);
                preg_match_all(
                    '/(https?:\/\/\S+\.(?:svg))/', $svgPreviewData, $svgMatch
                );
                if (!empty($svgMatch) && !empty($svgMatch[0])) {
                    $imageXlink = path('read', 'user');
                    $imageXlink = str_replace('/user', '', $imageXlink);
                    $main = $html->find(
                        'image[xlink:href^=' . $imageXlink . ']'
                    );
                    $ImgX = $ImgY = 0;
                    foreach ($main as $k => $v) {
                        $imgData = $main[$k]->attr;
                        $Imgwidth = $imgData['width'];
                        $Imgheight = $imgData['height'];
                        $ImgX = $imgData['x'];
                        $ImgY = $imgData['y'];
                        $id[$k] = $imgData['id'];
                        $imageUrl = str_replace(
                            $domainUrl, $relativePath, $imgData['xlink:href']
                        );
                        $fileContent = read_file($imageUrl);
                        $html1 = new \simple_html_dom();
                        $html1->load($fileContent, false);
                        $viewBox = $html1->find('svg[viewBox]', 0);
                        if (isset($viewBox) && !empty($viewBox)) {
                            $viewBox = $viewBox->viewBox;
                            $viewBox = explode(' ', $viewBox);
                            $vBwidth = $viewBox[2];
                            $vBheight = $viewBox[3];
                            $width = $Imgwidth / $vBwidth;
                            $height = $Imgheight / $vBheight;
                            if ($width == $height) {
                                $width = $Imgwidth / $vBwidth;
                                $height = $Imgheight / $vBheight;
                            } else if ($width < $height) {
                                $width = $width;
                                $height = $width;
                            } else {
                                $width = $height;
                                $height = $height;
                            }
                        } else {
                            $svgDimension = $html1->find('svg#svg', 0);
                            if (!empty($svgDimension)) {
                                $svgWidth = $svgDimension->width;
                                $svgHeight = $svgDimension->height;
                                $width = $Imgwidth / $svgWidth;
                                $height = $Imgheight / $svgHeight;
                            } else {
                                $width = $Imgwidth;
                                $height = $Imgheight;
                            }
                        }

                        $rstr = stripos($fileContent, '<svg');
                        $fileContent = substr($fileContent, $rstr);
                        preg_match_all('/id="([^"]+)"/', $fileContent, $idMatch);
                        if (!empty($idMatch)) {
                            $idMatchArr[$k] = $idMatch[1];
                        }
                        foreach ($idMatchArr[$k] as $key => $idVal) {
                            if (strpos(
                                $imgData['xlink:href'], "user"
                            ) == false
                            ) {
                                $fileContent = str_replace(
                                    $idVal, uniqid(
                                        $k . '_xe_', true
                                    ), $fileContent
                                );
                            }
                        }
                        if (strpos(
                            $imgData['xlink:href'], "user"
                        ) !== false
                        ) {
                            preg_match_all(
                                '/style="([^"]+)"/',
                                $fileContent, $styleMatch
                            );
                            if (!empty($styleMatch)) {
                                $styleMatch[$k] = $styleMatch[1];
                            }
                            foreach ($styleMatch[$k] as $k1 => $vStyle) {
                                if (strpos(
                                    $vStyle, 'display: none;'
                                ) !== false
                                ) {
                                    $fileContent = str_replace(
                                        'display: none;',
                                        'display: block;', $fileContent
                                    );
                                }
                            }
                        }
                        $fileContent = str_ireplace(
                            array(
                                '<svg', '/svg>'), array('<g', '/g>',
                            ), $fileContent
                        );
                        $translate = '<g  transform="translate';
                        $xyValue = '(' . $ImgX . ', ' . $ImgY . ')';
                        $scale = ' scale(' . $width . ', ' . $height . ')">';
                        $endTag = $fileContent . '</g>';
                        $transform = $translate . $xyValue . $scale . $endTag;
                        $html2 = new \simple_html_dom();
                        $html2->load($transform, false);
                        if ($html->getElementById($id[$k])) {
                            $html->getElementById($id[$k])->outertext = $html2;
                        }
                    }
                    $html->save();
                    $svgPreviewData = $html;
                }
                $svgTag = $imageTag = '';
                $svgTag .= '<svg xmlns="http://www.w3.org/2000/svg" id="svgroot" ';
                $svgTag .= 'xlinkns="http://www.w3.org/1999/xlink" width="600" ';
                $svgTag .= 'height="600" x="0" y="0" overflow="visible">';
                if (!$isDesign) {
                    $imageTag .= '<image x="0" y="0" width="600" height="600" ';
                    $imageTag .= 'xmlns:xlink="http://www.w3.org/1999/xlink" ';
                    $imageTag .= 'id="svg_1" xlink:href="data:image/png;base64,';
                    $imageTag .= $base64ProductImgData . '"></image>';
                }
                $productPreviewData = $svgTag . $imageTag . $svgPreviewData . '</svg>';
                $svgFileStatus = write_file(
                    $svgPreviewPath, $productPreviewData
                );
                $result = $svgFileStatus ? $svgFileStatus : 1;
            } catch (Exception $e) {
                $result = array('Exception:' => $e->getMessage());
            }
        } else {
            $result = 0;
        }
        return $result;
    }

    /**
     * GET: Parse SVG string and get all image file, then convert to base64 image
     *
     * @param $svgStringwithImageURL SVG string with image file
     *
     * @author radhanatham@riaxe.com
     * @date   05 March 2020
     * @return boolean
     */
    public function parseSVGString($svgStringwithImageURL)
    {
        $domainUrl = (isset($_SERVER['HTTPS'])
            && $_SERVER['HTTPS'] === 'on'
            ? "https" : "http"
        );
        $domainUrl .= "://" . $_SERVER['HTTP_HOST'];
        $relativePath = $_SERVER['DOCUMENT_ROOT'];
        $svgStringWithBase64 = '';
        try {
            $userimage = array();
            preg_match_all(
                '/(https?:\/\/\S+\.(?:jpg|png|gif|jpeg|bmp|JPG|PNG|GIF|JPEG|BMP))/',
                $svgStringwithImageURL, $match
            );
            for ($i = 0; $i < count($match[0]); ++$i) {
                $b64image = "";
                $userImgURLBasePath = "";
                $userImgURLBasePath = str_replace(
                    $domainUrl, $relativePath, $match[0][$i]
                );
                $b64image = base64_encode(
                    read_file($userImgURLBasePath)
                );
                if (strpos($match[0][$i], "user") !== false) {
                    $userimage['url'][] = $match[0][$i];
                }
                $info = pathinfo($match[0][$i]);
                $ext = $info['extension'];
                $src = 'data: ' . $ext . ';base64,';
                $svgStringwithImageURL = str_ireplace(
                    $match[0][$i], $src . $b64image, $svgStringwithImageURL
                );
            }
            $userimage['svgStringwithImageURL'] = $svgStringwithImageURL;
            return $userimage;
        } catch (Exception $e) {
            $result = array('Exception:' => $e->getMessage());
            return $result;
        }
    }

    /**
     * GET: include dom html file
     *
     * @author radhanatham@riaxe.com
     * @date   04 April 2020
     * @return nothing
     */
    private function domHtmlPathInclue()
    {
        include_once dirname(__FILE__) . '/../../../Dependencies/simple_html_dom.php';
    }
    /**
     * POST: Download order line item file by order id & item id
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author soumyas@riaxe.com
     * @date   08 June 2020
     * @return json
     */
    public function downloadOrderArtworkFile($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Download order artwork files ', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $orderId = $allPostPutVars['orderId'];
        $orderItemId = $allPostPutVars['orderItemId'];
        $fileType = $allPostPutVars['fileType'];
        $side = $allPostPutVars['side'];
        $layer = $allPostPutVars['layer'] ? $allPostPutVars['layer'] : 0;
        $file = (isset($allPostPutVars['file']) && $allPostPutVars['file'] != '')
        ? $allPostPutVars['file'] : '';
        if ($orderId && $orderItemId) {
            $svgAbsPath = $this->orderPath . $orderId . '/' . $orderItemId . '/side_' . $side . '/Layer_' . $layer . '_side_' . $side . '_' . $orderItemId . '_' . $orderId . '.svg';
            $fileUrl = '';
            if ($fileType == 'svg') {
                $fileUrl = ASSETS_PATH_R . 'orders/' . $orderId . '/' . $orderItemId . '/side_' . $side . '/Layer_' . $layer . '_side_' . $side . '_' . $orderItemId . '_' . $orderId . '.' . $fileType;
            } else if ($fileType == "png") {
                $pngAbsPath = $this->orderPath . $orderId . '/' . $orderItemId . '/side_' . $side . '/Layer_' . $layer . '_side_' . $side . '_' . $orderItemId . '_' . $orderId . '.' . $fileType;
                if (!file_exists($pngAbsPath)) {
                    $this->svgConvertToPng($pngAbsPath, $svgAbsPath);
                }
                $fileUrl = ASSETS_PATH_R . 'orders/' . $orderId . '/' . $orderItemId . '/side_' . $side . '/Layer_' . $layer . '_side_' . $side . '_' . $orderItemId . '_' . $orderId . '.' . $fileType;
            } else if ($fileType == "pdf") {
                $pdfAbsPath = $this->orderPath . $orderId . '/' . $orderItemId . '/side_' . $side . '/Layer_' . $layer . '_side_' . $side . '_' . $orderItemId . '_' . $orderId . '.' . $fileType;
                if (!file_exists($pdfAbsPath)) {
                    $this->svgConvertToRGBPdf($pdfAbsPath, $svgAbsPath);
                }
                $fileUrl = ASSETS_PATH_R . 'orders/' . $orderId . '/' . $orderItemId . '/side_' . $side . '/Layer_' . $layer . '_side_' . $side . '_' . $orderItemId . '_' . $orderId . '.' . $fileType;
            } else if ($fileType == "") {
                if ($file != '') {
                    $filePath = $this->orderPath . $orderId . '/' . $orderItemId . '/side_' . $side . '/' . $file;
                    if (file_exists($filePath)) {
                        $fileUrl = ASSETS_PATH_R . 'orders/' . $orderId . '/' . $orderItemId . '/side_' . $side . '/' . $file;
                    }
                }
            }
            $jsonResponse = [
                'status' => 1,
                'file_url' => $fileUrl,
            ];
        } else {
            $jsonResponse = [
                'status' => 0,
                'message' => 'Order / item id empty ',
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * POST: Create order artwork file
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author soumyas@riaxe.com
     * @date   08 June 2020
     * @return json
     */
    public function createdOrderArtworkFile($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Create order artwork files ', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $designArray = array();
        $orderId = $allPostPutVars['order_id'];
        $orderItemId = $allPostPutVars['order_item_id'];
        $artworkStatus = true;
        $createSingleFile = true;
        if ($orderId && $orderItemId) {
            $status = $this->downloadOrderByItemId($orderId, $orderItemId, $artworkStatus, $createSingleFile);
            if ($status) {
                $orderFolderDir = $this->orderPath . $orderId . '/order.json';
                $orderJson = read_file($orderFolderDir);
                $jsonContent = json_clean_decode($orderJson, true);
                if (!empty($jsonContent['order_details']['order_items'])) {
                    foreach ($jsonContent['order_details']['order_items'] as $key => $value) {
                        $itemId = $value['item_id'];
                        $refId = $value['ref_id'];
                        if ($itemId != null && $itemId > 0 && $refId != null && $refId > 0 && $refId != '-1') {
                            if ($orderItemId == $itemId) {
                                $orderItemDir = $this->orderPath . $orderId . "/" . $itemId;
                                //Fetch the design state json details //
                                $designStr = read_file(
                                    $orderItemDir . "/designState.json"
                                );
                                $resultDesign = json_clean_decode($designStr, true);

                                if (is_array($resultDesign['sides'])) {
                                    $i = 1;
                                    foreach ($resultDesign['sides'] as $sideDetailsKey => $sideDetails) {
                                        $designArray[$sideDetailsKey]['is_design'] = $sideDetails['is_designed'];
                                        $designArray[$sideDetailsKey]['name'] = $sideDetails['side_name'];
                                        //$designArray[$sideDetailsKey]['side'] = $i;
                                        $decorationData = [];
                                        if (!empty($sideDetails['print_area'])) {
                                            $j = 0;
                                            foreach ($sideDetails['print_area'] as $profile) {
                                                $svgUrl = ASSETS_PATH_R . 'orders/' . $orderId . '/' . $itemId . '/side_' . $i . '/Layer_' . $j . '_side_' . $i . '_' . $itemId . '_' . $orderId . '.svg';
                                                $svgDocPath = $this->orderPath . $orderId . '/' . $itemId . '/side_' . $i . '/Layer_' . $j . '_side_' . $i . '_' . $itemId . '_' . $orderId . '.svg';
                                                if ($profile['isDesigned'] > 0 && file_exists($svgDocPath)) {
                                                    $decorationData[] = [
                                                        'decoration_name' => isset($profile['name']) ? $profile['name'] : $profile['name'],
                                                        'print_area_id' => $profile['print_area']['id'],
                                                        'print_area_name' => $profile['print_area']['name'],
                                                        'print_profile_id' => $profile['print_method_id'],
                                                        'print_profile_name' => $profile['print_method_name'],
                                                        'print_unit' => $sideDetails['print_unit'],
                                                        'print_area_height' => $profile['print_area']['height'],
                                                        'print_area_width' => $profile['print_area']['width'],
                                                        'design_width' => isset($profile['design_width']) ? $profile['design_width'] : "",
                                                        'design_height' => isset($profile['design_height']) ? $profile['design_height'] : "",
                                                        'svg_url' => $svgUrl,
                                                        'file' => '',
                                                        'layer' => $j,
                                                        'side' => $i,
                                                        'x_location' => isset($profile['design_x']) ? $profile['design_x'] : "",
                                                        'y_location' => isset($profile['design_y']) ? $profile['design_y'] : "",

                                                    ];
                                                }
                                                $j++;
                                            }
                                            $designArray[$sideDetailsKey]['decoration_data'] = $decorationData;

                                        }
                                        $i++;
                                    }
                                }
                            }

                        } else if ($itemId != null && $itemId > 0 && $refId == '-1') {
                            $orderFolderDir = path('abs', 'order') . $orderId . '/order.json';
                            $orderJson = read_file($orderFolderDir);
                            $jsonContent = json_clean_decode($orderJson, true);
                            $orderItemArr = $jsonContent['order_details']['order_items'];
                            if ($orderItemId == $itemId) {
                                $itemArr = array_filter($orderItemArr, function ($item) use ($itemId) {
                                    return ($item['item_id'] == $itemId);
                                });
                                $itemArr = $itemArr[array_keys($itemArr)[0]];
                                $filesDataArr = $itemArr['file_data'];
                                if (!empty($filesDataArr)) {
                                    $i = 1;
                                    foreach ($filesDataArr as $fileKey => $files) {
                                        $designArray[$fileKey]['is_design'] = 1;
                                        $designArray[$fileKey]['name'] = $files['side_name'];
                                        $decorationData = [];
                                        $j = 0;
                                        foreach ($files['decoration_area'] as $decorationArea) {
                                            $decorationData[] = [
                                                'decoration_name' => $decorationArea['decoration_area'],
                                                'print_area_id' => $decorationArea['print_area_id'],
                                                'print_area_name' => $decorationArea['print_area_name'],
                                                'print_profile_id' => $decorationArea['print_method_id'],
                                                'print_profile_name' => $decorationArea['print_methods'],
                                                'print_unit' => $decorationArea['measurement_unit'],
                                                'print_area_height' => $decorationArea['height'],
                                                'print_area_width' => $decorationArea['width'],
                                                'design_width' => $decorationArea['design_width'],
                                                'design_height' => $decorationArea['design_height'],
                                                'svg_url' => $decorationArea['upload_design_url'],
                                                'file' => $decorationArea['file'],
                                                'layer' => $j,
                                                'side' => $i,
                                            ];
                                            $j++;
                                        }
                                        $designArray[$fileKey]['decoration_data'] = $decorationData;
                                        $i++;
                                    }
                                }
                            }
                        }
                    }
                }
                $jsonResponse = [
                    'status' => 1,
                    'decoration_settings_data' => $designArray,
                ];
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => 'file not created',
                ];
            }

        } else {
            $jsonResponse = [
                'status' => 0,
                'message' => 'order id / order item id empty',
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET: Create svg file according sticker height and width
     *
     * @param $stickerHeight   The is current sticker actual height
     * @param $stickerWidht   The is current sticker actual width
     * @param $svgStr       The is current sticker SVg string
     *
     * @author radhanatham@riaxe.com
     * @date   15 Aug 2020
     * @return boolean
     */
    private function createStickerSvg($stickerHeight, $stickerWidht, $svgStr)
    {
        $bleedMarkMaxValue = 0;
        $svgStartTag = '<svg xmlns="http://www.w3.org/2000/svg"';
        $svgXlink = ' id="svgroot" xmlns:xlink="http://www.w3.org/1999/xlink"';
        $svgEndTag = '</g></svg>';
        $svgTagStr = $svgStartTag . $svgXlink;

        $html = new \simple_html_dom();
        $html->load($svgStr, false);
        $path = $html->find('path', 0);
        if ($path->height && $path->width) {
            $height = $path->height;
            $width = $path->width;
            $aHeight = $stickerHeight;
            $aWidth = $stickerWidht;
            //Print area dimension swapping height and width
            if ((intval($aWidth) > intval($aHeight)) && (intval($height) > intval($width))) {
                $temp = 0;
                $temp = $aHeight;
                $aHeight = $aWidth;
                $aWidth = $temp;
            }
            if ((intval($aHeight) > intval($aWidth)) && (intval($width) > intval($height))) {
                $temp = 0;
                $temp = $aWidth;
                $aWidth = $aHeight;
                $aHeight = $temp;
            }

            $x = ($path->x) - ($bleedMarkMaxValue);
            $y = ($path->y) - ($bleedMarkMaxValue);
            if ($this->printUnit == 'Pixel') {
                $acWidth = $aWidth;
                $acHeight = $aHeight;
            } else {
                $aWidth = $this->unitConvertionToInch($aWidth);
                $aHeight = $this->unitConvertionToInch($aHeight);
                $acWidth = $aWidth * $this->dpi;
                $acHeight = $aHeight * $this->dpi;
            }
            $acHeight = $acHeight / $height;
            $acWidth = $acWidth / $width;
            $transForm = '<g xmlns="http://www.w3.org/2000/svg" transform="scale(' . $acWidth . ',' . $acHeight . ') translate(-' . $x . ',-' . $y . ')">';
            $aWidth = $aWidth * $this->dpi;
            $aHeight = $aHeight * $this->dpi;
            $svgWidth = ' width="' . $aWidth . '"';
            $svgHeight = ' height="' . $aHeight . '"';
            $svgXY = ' x="0" y="0" overflow="visible">';
            $svgTag = $svgTagStr . $svgWidth . $svgHeight;
            $svgTagXY = $svgTag . $svgXY . $transForm;
            $finalSvg = $svgTagXY . $path . $svgEndTag;
            $fileName = 'contour.svg';
            $svgFilePath = $this->svgSavePath . $fileName;
            return $svgFileStatus = $artworkArr = write_file(
                $svgFilePath, $finalSvg
            );
        }
    }

    /**
     * Calculate the number of row and column based on the sheet width and height.
     *
     * @param $sheetHeight   Sheet Height
     * @param $sheetWidth    Sheet Width
     * @param $acHeight      Sticker Height
     * @param $acWidth       Sticker Width
     * @param $spacing       Spacing between sticker
     * @param $margin        Margin on the sheet
     *
     * @author malay@riaxe.com
     * @date   19th Jan 2021
     * @return array
     */

    private function calculateSvgPerSheet($sheetHeight, $sheetWidth, $acHeight, $acWidth, $spacing, $margin) {
        $sheetHeightAfterMargin = $sheetHeight - (2 * $margin);
        $sheetWidthAfterMargin  = $sheetWidth - (2 * $margin);
        $stckerWidthWithSpacing = $acWidth + $spacing;
        $stckerHeightWithSpacing = $acHeight + $spacing;
        $stickersPerRow = floor(($sheetHeightAfterMargin + $spacing) / $stckerHeightWithSpacing);
        $stickersPerColoumn = floor(($sheetWidthAfterMargin + $spacing) / $stckerWidthWithSpacing);
        return array("row" => $stickersPerRow, "coloumn" => $stickersPerColoumn);
    }
      /**
     * Generate sticker sheet based on Sheet Size
     *
     * @param $svgFinalString   Final SVG output string.
     * @param $stickerInfo      Sticker information array
     * @param $svgPath          Location to save the Final SVG
     * @param $fileStr          Naming Patern for the file
     * @param $artworkStatus    To check if it is coming from individual file download section.
     * @param $fileFormat       Supported file formats.
     *
     * @author malay@riaxe.com
     * @date   19th Jan 2021
     * @return boolean
     */
    private function generateStickerSheet($svgFinalString, $stickerInfo, $svgPath, $fileStr, $artworkStatus = false, $fileFormat) {

        $stickerAcHeight = $this->dpi * $this->unitConvertionToInch($stickerInfo['height']);
        $stickerAcWidht  = $this->dpi * $this->unitConvertionToInch($stickerInfo['width']);
        $stickerArtworkReturnStatus = false;

        $htmlDomInner = new \simple_html_dom();
        $htmlDomInner->load($svgFinalString, false);
        $domInner = $htmlDomInner->find('#svgroot', 0);
        $groupedSVG = $domInner->innertext;               
        foreach($stickerInfo['sheetInfo'] as $key => $sheet){                                        
            $newSvgStartTag = '<svg xmlns="http://www.w3.org/2000/svg"';
            $newSvgXlink = ' id="stickerSheet" xmlns:xlink="http://www.w3.org/1999/xlink"';
            $newSvgEndTag = '</svg>';
            $sheetWidth =  $sheet['width'];
            $sheetHeight =  $sheet['height'];
            $sheetName  = $sheet['name'];
            $margin = $sheet['margin'];
            $spacing = $sheet['spacing'];
            $stickerQty = $sheet['stickerQtyPerSheet'];
            $boxWidth = $sheet['stickerBoxWidth'];
            $boxHeight = $sheet['stickerBoxHeight'];
            $margin = $this->dpi * $this->unitConvertionToInch($margin);
            $spacing = $this->dpi * $this->unitConvertionToInch($spacing);
            $sheetAcWidth  = $this->dpi * $this->unitConvertionToInch($sheetWidth);
            $sheetAcHeight = $this->dpi * $this->unitConvertionToInch($sheetHeight);   
            $perPage = $this->calculateSvgPerSheet($sheetAcHeight, $sheetAcWidth, $stickerAcHeight, $stickerAcWidht, $spacing, $margin);
            $svgSheet = $newSvgStartTag.$newSvgXlink.' width="'.$sheetAcWidth.'" height="'.$sheetAcHeight.'" x="0" y="0" overflow="visible">';
            if(!empty($perPage)) {
                $svgItem = '';
                for($i = 0; $i < $perPage['row']; $i++) {
                    $vmargin = $margin + ($i * $spacing) + ($i * $stickerAcHeight);
                    for($j = 0; $j < $perPage['coloumn']; $j++){
                        $hspacing = $margin + ($spacing * $j) + ($j * $stickerAcWidht);
                        $svgItem.= '<g transform="translate('.$hspacing.','.$vmargin.')" width="'.$stickerAcWidht.'" height="'.$stickerAcHeight.'" x = "'.$hspacing.'" y="'.$vmargin.'">';
                        $svgItem.= $groupedSVG.'</g>';
                    }
                }
                $finalSVGPerSheet = $svgSheet.$svgItem.$newSvgEndTag;

                $stickerSvgFilePath = $svgPath . '_' . $fileStr . '_'.$sheetName.'.svg';
                //png and pdf file name
                $stickerPngAbsPath = $svgPath . '_' . $fileStr . '_'.$sheetName.'.png';
                $stickerRgbPdfPath = $svgPath . '_' . $fileStr . '_'.$sheetName.'_rgb.pdf';
                $stickerCmykPdfAbsPath = $svgPath . '_' . $fileStr . '_'.$sheetName.'.pdf';

                /* conversion START */
                if (!file_exists($stickerSvgFilePath)) {
                    $stickerSvgFileStatus = $stickerArtworkReturnStatus = write_file(
                        $stickerSvgFilePath, $finalSVGPerSheet
                    );
                } else {
                    $stickerSvgFileStatus = $stickerArtworkReturnStatus = true;
                }
                if (!$artworkStatus) {
                    if (in_array('png', $fileFormat)) {
                        if (!file_exists($stickerPngAbsPath)) {
                            $this->svgConvertToPng(
                                $stickerPngAbsPath, $stickerSvgFilePath
                            );
                        }
                    }
                    if (in_array('pdf', $fileFormat)) {
                        if (!file_exists($stickerRgbPdfPath)) {
                            $this->svgConvertToRGBPdf(
                                $stickerRgbPdfPath, $stickerSvgFilePath
                            );
                        }
                        if (!file_exists($stickerCmykPdfAbsPath)) {
                            $this->rgbPdfConvertToCMYKPdf(
                                $stickerCmykPdfAbsPath, $stickerRgbPdfPath
                            );
                        }
                    }
                }
                /* conversion END */
            }
        }
        return $stickerArtworkReturnStatus;
    }
}