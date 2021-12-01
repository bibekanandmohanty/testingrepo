<?php 
    /**
     *
     * Manage Product Image Sides
     *
     * @category   Image Sides
     * @package    Product/Store
     * @author     Original Author <tanmayap@riaxe.com>
     * @author     tanmayap@riaxe.com
     * @copyright  2019-2020 Riaxe Systems
     * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
     * @version    Release: @1.0
     */
    namespace App\Modules\ProductImageSides\Controllers;

    use StoreSpace\Controllers\StoreProductsController;
    use App\Modules\ProductImageSides\Models\ProductImage;
    use App\Modules\ProductImageSides\Models\ProductImageSides;
    use Intervention\Image\ImageManagerStatic as ImageManager;
    use SVG\SVG;
    use SVG\Nodes\Shapes\SVGRect;
    use Imagick;
    use ImagickPixel;

    class ProductImagesController extends StoreProductsController
    {

        /**
         * Getting List of All Product Images
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function getProductImages($request, $response, $args)
        {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;

            $initProductImage = ProductImage::with('sides');
            if(isset($args['product_image_id']) && $args['product_image_id'] != "" && $args['product_image_id'] > 0) {
                $initProductImage->where('xe_id', $args['product_image_id']);
            }
            if($initProductImage->count() > 0 ) {
                $jsonResponse['status'] = 1;
                $jsonResponse['data'] = $initProductImage->orderBy('xe_id', 'desc')->get();
            } else {
                $jsonResponse['status'] = 1;
                $jsonResponse['message'] = message('Product Images', 'not_found');
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }

        /**
         * Save Product Images
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         * @format: {"name":"Inkxe 10 Product Image","is_default":0,"sides":[{"side_name":"Side 1","sort_order":"1","image_upload_data":"product_image_0"},{"side_name":"Side 2","sort_order":"3","image_upload_data":"product_image_1"}, {"side_name":"Side 3","sort_order":"3","image_upload_data":"product_image_2"}]}
         */
        public function saveProductImages($request, $response, $args) {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $request->getParsedBody();
            $productSidesJson = $allPostPutVars['product_sides'];
            $productSidesArray = json_decode($productSidesJson, true);

            // Save Product Image Details 
            if(isset($productSidesArray['name']) && $productSidesArray['name'] != "") {
                $saveProductImage = new ProductImage(['name' => $productSidesArray['name']]);
                $saveProductImage->save();
                $productImageInsertId = $saveProductImage->xe_id;

                // Save Product Image Sides
                if(isset($productSidesArray['sides']) && count($productSidesArray['sides']) > 0) {
                    foreach ($productSidesArray['sides'] as $sideKey => $sideData) {
                        // Start saving each sides 
                        $imageUploadIndex = $sideData['image_upload_data'];
                        /**
                         * If image resource was given then upload the image into the specified folder
                         */
                        if(isset($_FILES) && count($_FILES) > 0) {
                            $images_path = PRODUCT_FOLDER;
                            if (!is_dir($images_path)) {
                                mkdir($images_path, 0777, true);
                            } else {
                                // Get the next Autoincrement ID from the `Table Name` specified
                                // Generate a new name for the uploading file
                                $fileExtension = pathinfo($_FILES[$imageUploadIndex]['name'], PATHINFO_EXTENSION);
                                $random = getRandom();
                                $newUploadedFileName = $random . "." . $fileExtension;
                                if (move_uploaded_file($_FILES[$imageUploadIndex]['tmp_name'], $images_path . $newUploadedFileName) === true) {
                                    
                                    if (!is_dir($images_path . 'processed')) {
                                        mkdir($images_path . 'processed', 0777, true);
                                    }
                                    // Image Uploaded. Write any operations if required --
                                    if(isset($fileExtension) && $fileExtension == 'svg') {
                                        $sourceFile = $images_path . $newUploadedFileName;
                                        $fileToProcess = $images_path . 'processed/' . $random . "_convert.jpeg";
                                        exec("convert " . $sourceFile . " " . $fileToProcess);
                                    } else {
                                        $fileToProcess = $images_path . $newUploadedFileName;
                                    }

                                    $img = ImageManager::make($fileToProcess);
                                    $listOfDimensions = [100, 400];
                                    foreach ($listOfDimensions as $dimension) {
                                        $img->resize($dimension, $dimension);
                                        $img->save($images_path . 'processed/' . $random . "_" . $dimension . 'x' . $dimension . ".jpeg");
                                    }
                                }
                            }
                        }

                        // Setup data for Saving/updating
                        $productImageSides = [
                            'product_image_id' => $productImageInsertId,
                            'side_name' => $sideData['side_name'],
                            'sort_order' => $sideData['sort_order'],
                        ];

                        // If File was choosen from frontend then only save/update the image or skip the image saving
                        if(isset($newUploadedFileName) && $newUploadedFileName != ""){
                            $productImageSides['file_name'] = $newUploadedFileName;
                        }

                        // Insert Product Image Sides
                        $saveProductImageSide = new ProductImageSides($productImageSides);
                        if($saveProductImageSide->save()) {
                            $saveProductImageSideInsertId = $saveProductImageSide->xe_id;
                            $jsonResponse = [
                                'status' => 1,
                                'product_image_id' => $productImageInsertId,
                                'message' => message('Product Image', 'saved')
                            ];
                        } else {
                            $jsonResponse = [
                                'status' => 0,
                                'message' => message('Product Image', 'error')
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

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }

        /**
         * Update Product Images
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function updateProductImages($request, $response, $args)
        {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $this->parsePut();
            $productSidesJson = $allPostPutVars['product_sides'];
            $productSidesArray = json_decode($productSidesJson, true);
            $productImageId = $args['product_image_id'];
            // Save Product Image Details 
            $productImageInit = ProductImage::where('xe_id', $productImageId);
            if(isset($productSidesArray['name']) && $productSidesArray['name'] != "" && $productImageInit->count() > 0) {
                // Update Product Image Details
                $productImageInit->update(['name' => $productSidesArray['name']]);

                $productImageInsertId = $productImageId;

                // Save Product Image Sides
                if(isset($productSidesArray['sides']) && count($productSidesArray['sides']) > 0) {
                    $productImageSidesInit = ProductImageSides::where(['product_image_id' => $productImageInsertId]);
                    // Delete old records 
                    $getProductImageSidesDataSet = $productImageSidesInit->get();
                    $loopCheck = 0;

                    foreach ($productSidesArray['sides'] as $sideKey => $sideData) {
                        $newUploadedFileName = '';
                        // Start saving each sides 
                        if(isset($sideData['image_upload_data']) && isset($_FILES[$sideData['image_upload_data']]['name']) && $_FILES[$sideData['image_upload_data']]['name'] != "") {
                            /**
                             * If image resource was given then upload the image into the specified folder
                             */
                            if(isset($_FILES) && count($_FILES) > 0) {
                                $images_path = PRODUCT_FOLDER;
                                if (!is_dir($images_path)) {
                                    mkdir($images_path, 0777, true);
                                } else {
                                    // Get the next Autoincrement ID from the `Table Name` specified
                                    // Generate a new name for the uploading file
                                    $fileExtension = pathinfo($_FILES[$sideData['image_upload_data']]['name'], PATHINFO_EXTENSION);
                                    $newUploadedFileName = getRandom() . "." . $fileExtension;
                                    if (move_uploaded_file($_FILES[$sideData['image_upload_data']]['tmp_name'], $images_path . $newUploadedFileName) === true) {
                                        // Image Uploaded. Write any operations if required --
                                    }
                                }
                            }
                            // Case #1 : New Image Side will be added to the Product
                            $productImageSides = [
                                'product_image_id' => $productImageInsertId,
                                'side_name' => $sideData['side_name'],
                                'sort_order' => $sideData['sort_order'],
                                'file_name' => $newUploadedFileName
                            ];
                            $saveSide = new ProductImageSides($productImageSides);
                            if($saveSide->save()) { $loopCheck++; }
                        } else if(isset($sideData['is_trash']) && $sideData['is_trash'] === 1) {
                            // Case #2 : Image Side will be deleted from the Product
                            $trashSide = ProductImageSides::where(['xe_id' => $sideData['xe_id'], 'product_image_id' => $productImageInsertId]);
                            if($trashSide->delete()) { $loopCheck++; }
                        } else {
                            // Case #3 : Existing Image Side will be Updated
                            $productImageSides = [
                                'side_name' => $sideData['side_name'],
                                'sort_order' => $sideData['sort_order'],
                            ];
                            $updateSide = ProductImageSides::where(['xe_id' => $sideData['xe_id'], 'product_image_id' => $productImageInsertId]);
                            if($updateSide->update($productImageSides)) { $loopCheck++; }
                        }
                    }
                    
                }

                if(isset($loopCheck) && $loopCheck > 0) {
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Product Image Side', 'updated')
                    ];
                } else {
                    $jsonResponse = [
                        'status' => 0,
                        'message' => message('Product Image Side', 'error')
                    ];
                }
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Product Image Side', 'insufficient'),
                ];
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }

        /**
         * Delete Product Image(s)
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters, json array of Delete Ids [1,2,3...]
         * @response   A JSON Response
         */
        public function productImageDelete($request, $response, $args)
        {
            $serverStatusCode = OPERATION_OKAY;
            // If user wants to delete Product Image(s)
            if(isset($args) && count($args) > 0 && $args['ids'] != '') {
                $getProductImagesId = json_decode($args['ids'], true);
                $getSelectedItems = ProductImage::whereIn('xe_id', $getProductImagesId);
                
                if($getSelectedItems->count() > 0) {
                    // Get Existing Data for further processing
                    $getExistingData = $getSelectedItems->with('sides')->get();
                    
                    if($getSelectedItems->delete()) {
                        // Get existing Images and Delte those from directory
                        foreach ($getExistingData as $selectedKey => $selectedValue) {
                            if(isset($selectedValue['sides']) && count($selectedValue['sides']) > 0) {
                                foreach ($selectedValue['sides'] as $key => $singleSide) {
                                    $rawFileLocation = PRODUCT_FOLDER . $singleSide['file_name'];
                                    // Delete file from the directory
                                    if (file_exists($rawFileLocation)) {
                                        chmod($rawFileLocation, 0755);
                                        // For Linux System Below code will change the permission of the file
                                        shell_exec('chmod -R 777 ' . $rawFileLocation);
                                        unlink($rawFileLocation);
                                    } 
                                }
                            }
                        }
                        $jsonResponse = [
                            'status' => 1,
                            'message' => 'Product Image data deleted successfully'
                        ];
                    } else {
                        $jsonResponse = [
                            'status' => 0,
                            'message' => 'Product Image data could not deleted'
                        ];
                    }
                } else {
                    $serverStatusCode = MISSING_PARAMETER;
                    $jsonResponse = [
                        'status' => 0,
                        'message' => 'Product Image not found. Try again later'
                    ];
                }
            } else {
                $serverStatusCode = MISSING_PARAMETER;
                $jsonResponse = [
                    'status' => 0,
                    'message' => 'Invalid data provided. Please provide valid data.'
                ];
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }

        /**
         * Enable/Disable Product Image
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function disableProductImage($request, $response, $args)
        {
            $serverStatusCode = OPERATION_OKAY;
            // If user wants to delete Product Image(s)
            if(isset($args) && count($args) > 0 && $args['id'] != '') {
                $getProductImageId = $args['id'];
                $getSelectedItems = ProductImage::find($getProductImageId);
                if(isset($getSelectedItems) && count(objectToArray($getSelectedItems)) > 0) {
                    $getSelectedItems->is_disable = !$getSelectedItems->is_disable;
                    if($getSelectedItems->save()) {
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Product Image', 'updated')
                        ];
                    } else {
                        $jsonResponse = [
                            'status' => 0,
                            'message' => message('Product Image', 'error')
                        ];
                    }
                } else {
                    $jsonResponse = [
                        'status' => 0,
                        'message' => message('Product Image', 'not_found')
                    ];
                }
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => 'Invalid request. please try again later'
                ];
            }
            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }
    }
