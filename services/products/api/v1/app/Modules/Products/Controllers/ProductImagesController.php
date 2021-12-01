<?php
/**
 * Manage Product Image Sides
 *
 * PHP version 5.6
 *
 * @category  Product_Image
 * @package   Product
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Controllers;

use App\Modules\Products\Models\ProductImage;
use App\Modules\Products\Models\ProductImageSides;
use App\Modules\Products\Models\ProductSide;
use App\Modules\Products\Models\ProductImageSettingsRel;
use ProductStoreSpace\Controllers\StoreProductsController;

/**
 * Product Image Controller
 *
 * @category Product_Image
 * @package  Product
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductImagesController extends StoreProductsController
{
    /**
     * Get: Getting List of All Product Images
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return json
     */
    public function getProductImages($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $storeId = get_store_details($request);
        $jsonResponse = [
            'status' => 1,
            'data' => [],
            'message' => message('Product Images', 'not_found'),
        ];
        $perpage = $request->getQueryParam('perpage');
        $page = $request->getQueryParam('page_number');
        $name = $request->getQueryParam('name');
        $prodImgInit = new ProductImage();
        $productImageInfo = $prodImgInit->where('xe_id', '>', 0);
        $productImageInfo->with('sides');
        if (!empty($args['product_image_id'])) {
            $productImageInfo->where('xe_id', $args['product_image_id']);
        }
        if (isset($name) && $name != "") {
            $productImageInfo->where('name', 'LIKE', '%' . $name . '%');
        }
        $getTotalPerFilters = $productImageInfo->count();
        if ($page != "") {
            $totalItem = empty($perpage) ? 10 : $perpage;
            $offset = $totalItem * ($page - 1);
            $productImageInfo->skip($offset)->take($totalItem);
        }
        if (!empty($storeId)) {
            $productImageInfo->where($storeId);
        }
        $productImageList = $productImageInfo->orderBy('xe_id', 'desc')
            ->get();
        $jsonResponse = [
            'status' => 1,
            'data' => $productImageList,
            'total_count' => $getTotalPerFilters,
        ];

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Post: Save Product Images
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return json
     */
    public function saveProductImages($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        // Get Store Specific Details from helper
        $store = get_store_details($request);
        $jsonResponse = [];
        $allPostPutVars = $request->getParsedBody();
        $productSidesJson = $allPostPutVars['product_sides'];
        $productSidesArray = json_clean_decode($productSidesJson, true);

        // Save Product Image Details
        if (!empty($productSidesArray['name'])) {
            $productImageData = [
                'name' => $productSidesArray['name'],
            ];
            $productImageData['store_id'] = $store['store_id'];
            $saveProductImage = new ProductImage($productImageData);
            $saveProductImage->save();
            $productImageInsertId = $saveProductImage->xe_id;

            // Save Product Image Sides
            if (!empty($productSidesArray['sides'])) {
                foreach ($productSidesArray['sides'] as $sideData) {
                    // Start saving each sides
                    $imageUploadIndex = $sideData['image_upload_data'];
                    // If image resource was given then upload the image into
                    // the specified folder
                    $getUploadedFileName = do_upload(
                        $imageUploadIndex, path('abs', 'product'), [150], 'string'
                    );
                    // Setup data for Saving/updating
                    $productImageSides = [
                        'product_image_id' => $productImageInsertId,
                        'side_name' => !empty($sideData['side_name'])
                        ? $sideData['side_name'] : null,
                        'sort_order' => $sideData['sort_order'],
                    ];
                    // If File was choosen from frontend then only save/update
                    // the image or skip the image saving
                    if (!empty($getUploadedFileName)) {
                        $productImageSides['file_name'] = $getUploadedFileName;
                    }
                    // Insert Product Image Sides
                    $saveProductImageSide = new ProductImageSides(
                        $productImageSides
                    );
                    if ($saveProductImageSide->save()) {
                        $jsonResponse = [
                            'status' => 1,
                            'product_image_id' => $productImageInsertId,
                            'message' => message('Product Image', 'saved'),
                        ];
                    } else {
                        $jsonResponse = [
                            'status' => 0,
                            'message' => message('Product Image', 'error'),
                        ];
                    }
                }
            }
        } else {
            $jsonResponse = [
                'status' => 0,
                'message' => message('Product Image', 'insufficient'),
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Put: Update Product Images
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return json
     */
    public function updateProductImages($request, $response, $args)
    {
        $jsonResponse = [
            'status' => 0,
            'message' => message('Product Image Side', 'error'),
        ];
        $serverStatusCode = OPERATION_OKAY;
        $storeId = get_store_details($request);
        $allPostPutVars = $request->getParsedBody();
        $productSidesJson = isset($allPostPutVars['product_sides'])
        ? $allPostPutVars['product_sides'] : '{}';
        $productSidesArray = json_clean_decode($productSidesJson, true);
        $productImageId = $args['product_image_id'];
        // Save Product Image Details
        $prodImgInit = new ProductImage();
        $productImageInit = $prodImgInit->where('xe_id', $productImageId);
        if (!empty($productSidesArray['name']) && $productImageInit->count() > 0) {
            // Update Product Image Details
            $updateProdImage = [
                'name' => $productSidesArray['name'],
            ];
            $updateProdImage += $storeId;
            $productImageInit->update($updateProdImage);
            $productImageInsertId = $productImageId;

            // Save Product Image Sides
            if (!empty($productSidesArray['sides'])) {
                foreach ($productSidesArray['sides'] as $sideData) {
                    $getUploadedFileName = '';
                    // Start analysing each side
                    if (!empty($sideData['image_upload_data'])) {
                        // Case #1: If New File uploading requested
                        $requestedFileKey = $sideData['image_upload_data'];
                        $getUploadedFileName = do_upload(
                            $requestedFileKey, path('abs', 'product'), [150], 'string'
                        );

                        // Case #1 : If New file added, then again 2 cases will
                        // arrise. 1. Save new record and 2. Update existing
                        $productImageSides = [
                            'product_image_id' => $productImageInsertId,
                            'side_name' => $sideData['side_name'],
                            'sort_order' => $sideData['sort_order'],
                        ];
                        if (!empty($getUploadedFileName)) {
                            $productImageSides['file_name'] = $getUploadedFileName;
                        }

                        $prodImgSideInit = new ProductImageSides();
                        $checkIdDataExist = $prodImgSideInit->where(
                            'xe_id', $sideData['xe_id']
                        );
                        if ($checkIdDataExist->count() > 0) {
                            // Update Record
                            $checkIdDataExist->update($productImageSides);
                        } else {
                            // Save New
                            $saveSide = new ProductImageSides($productImageSides);
                            $saveSide->save();
                        }
                    } elseif (!empty($sideData['is_trash'])) {
                        // Case #2: Image Side will be deleted from the Product
                        $prodImgSideInit = new ProductImageSides();
                        $trashSide = $prodImgSideInit->where(
                            [
                                'xe_id' => $sideData['xe_id'],
                                'product_image_id' => $productImageInsertId,
                            ]
                        );
                        if ($trashSide->delete()) {
                            $prodSideInit = new ProductSide();
                            $trashProdSide = $prodSideInit->where(
                                [
                                    'product_image_side_id' => $sideData['xe_id'],
                                ]
                            );
                            $trashProdSide->delete();
                        }
                    } else {
                        // Case #3: Existing Image Side will be Updated
                        $productImageSides = [
                            'side_name' => $sideData['side_name'],
                            'sort_order' => $sideData['sort_order'],
                        ];
                        $prodImgSideInit = new ProductImageSides();
                        $updateSide = $prodImgSideInit->where(['xe_id' => $sideData['xe_id'], 'product_image_id' => $productImageInsertId]);
                        $updateSide->update($productImageSides);
                    }
                }
            }
            $jsonResponse = [
                'status' => 1,
                'message' => message('Product Image Side', 'updated'),
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Delete: Delete Product Image(s)
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return json
     */
    public function productImageDelete($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Product side image', 'error'),
        ];
        // If user wants to delete Product Image(s)
        if (!empty($args['ids'])) {
            $getProductImagesId = json_clean_decode($args['ids'], true);
            $prodImgInit = new ProductImage();
            $getSelectedItems = $prodImgInit->whereIn('xe_id', $getProductImagesId);

            if ($getSelectedItems->count() > 0) {
                // Get Existing Data for further processing
                $getExistingData = $getSelectedItems->with('sides')->get();

                if ($getSelectedItems->delete()) {
                    // Delete product-image-setting-relation Records
                    $prodImgSettRelObj = new ProductImageSettingsRel();
                    $prodImgSettRelGet = $prodImgSettRelObj->where(
                        'product_image_id', $getProductImagesId
                    );
                    $prodImgSettRelGet->delete();
                    // Get existing Images and Delte those from directory
                    foreach ($getExistingData as $selectedValue) {
                        if (!empty($selectedValue['sides'])) {
                            foreach ($selectedValue['sides'] as $singleSide) {
                                $rawFileLocation = PRODUCT_FOLDER . $singleSide['file_name'];
                                // Delete file from the directory
                                if (file_exists($rawFileLocation)) {
                                    try {
                                        unlink($rawFileLocation);
                                    } catch (\Exception $e) {
                                        create_log(
                                            'product image sides', 'error',
                                            [
                                                'message' => $e->getMessage(),
                                                'extra' => [
                                                    'module' => 'delete directory',
                                                ],
                                            ]
                                        );
                                    }
                                }
                            }
                        }
                    }
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Product side image', 'deleted')
                    ];
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Get: Enable/Disable Product Image
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return json
     */
    public function disableProductImage($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'data' => [],
            'message' => message('Disabling product image', 'error'),
        ];
        // If user wants to delete Product Image(s)
        if (!empty($args['id'])) {
            $getProductImageId = to_int($args['id']);
            $prodImgInit = new ProductImage();
            $getSelectedItems = $prodImgInit->find($getProductImageId);
            $checkCount = count(object_to_array($getSelectedItems));
            if (!empty($getSelectedItems) && $checkCount > 0) {
                $getSelectedItems->is_disable = !$getSelectedItems->is_disable;
                if ($getSelectedItems->save()) {
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Product Image', 'updated'),
                    ];
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}
