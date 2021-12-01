<?php 
    /**
     *
     * Manage Product's Color Swatches
     *
     * @category   Color Swatches
     * @package    Product/Store
     * @author     Original Author <tanmayap@riaxe.com>
     * @author     tanmayap@riaxe.com
     * @copyright  2019-2020 Riaxe Systems
     * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
     * @version    Release: @1.0
     */
    namespace App\Modules\ColorSwatches\Controllers;

    use StoreSpace\Controllers\StoreProductsController;
    use App\Modules\ColorSwatches\Models\ColorSwatch;
    
    class ColorSwatchesController extends StoreProductsController
    {        
        /**
         * Get List of all Color Swatches
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function getColorSwatch($request, $response, $args)
        {
            $serverStatusCode = OPERATION_OKAY;
            $jsonResponse = [];

            $initColorSwatch = ColorSwatch::whereNotNull('hex_code');
            if(isset($args['id']) && $args['id'] != "") {
                $initColorSwatch->where(['xe_id' => $args['id']]);
            }
            if($initColorSwatch->count() > 0) {
                $jsonResponse = [
                    'status' => 1,
                ];
                $jsonResponse['data'] = $initColorSwatch->orderBy('xe_id', 'desc')->get();
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => 'Sorry! no color swatch found',
                ];
            }
            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }

        /**
         * Save Color Swatch
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function saveColorSwatch($request, $response, $args)
        {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $request->getParsedBody();

            if(isset($allPostPutVars['attribute_id']) && isset($allPostPutVars['hex_code'])) {
                if(isset($_FILES) && count($_FILES) > 0) {
                    $images_path = SWATCH_FOLDER;
                    if (!is_dir($images_path)) {
                        mkdir($images_path, 0777, true);
                    } else {
                        // Get the next Autoincrement ID from the `Table Name` specified
                        // Generate a new name for the uploading file
                        $fileExtension = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
                        $newUploadedFileName = getRandom() . "." . $fileExtension;
        
                        if (move_uploaded_file($_FILES['upload']['tmp_name'], $images_path . $newUploadedFileName) === true) {
                            $allPostPutVars += ['file_name' => $newUploadedFileName];
                        }
                    }
                }

                $swatchData = [
                    'attribute_id' => $allPostPutVars['attribute_id'],
                    'hex_code' => $allPostPutVars['hex_code']
                ];
                if(isset($allPostPutVars['file_name']) && $allPostPutVars['file_name'] != '') {
                    $swatchData['file_name'] = $allPostPutVars['file_name'];
                }
                //if(isset($allPostPutVars['attribute']))
                $initColorSwatch = new ColorSwatch($swatchData);
                
                try {
                    $initColorSwatch->save();
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Color Swatch', 'saved'),
                        'color_swatch_id' => $initColorSwatch->xe_id
                    ];
                } catch (\Exception $e) {
                    $serverStatusCode = EXCEPTION_OCCURED;
                    $jsonResponse = [
                        'status' => 0,
                        'message' => message('Color Swatch', 'exception'),
                        'exception' => $e->getMessage()
                    ];
                }
            } else {
                $serverStatusCode = EXCEPTION_OCCURED;
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Color Swatch', 'insufficient'),
                ];
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);

        }

        /**
         * Update Color Swatch
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function updateColorSwatch($request, $response, $args)
        {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $this->parsePut();
            $colorSwatchId = $args['color_swatch_id'];
            $initColorSwatch = ColorSwatch::where(['xe_id' => $colorSwatchId]);

            if($initColorSwatch->count() > 0) {
                $getSwatchData = $initColorSwatch->first();

                if(isset($allPostPutVars['attribute_id']) && isset($allPostPutVars['hex_code'])) {
                    // Save new Image
                    if(isset($_FILES) && count($_FILES) > 0) {
                        $images_path = SWATCH_FOLDER;
                        if (!is_dir($images_path)) {
                            mkdir($images_path, 0777, true);
                        } else {
                            // Get the next Autoincrement ID from the `Table Name` specified
                            // Generate a new name for the uploading file
                            $fileExtension = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
                            $newUploadedFileName = getRandom() . "." . $fileExtension;
            
                            if (copy($_FILES['upload']['tmp_name'], $images_path . $newUploadedFileName) === true) {
                                $allPostPutVars += ['file_name' => $newUploadedFileName];
                                // Delete old Image (if it exists)
                                if(isset($getSwatchData->file_name) & $getSwatchData->file_name != "") {
                                    $oldFilePath = SWATCH_FOLDER . $getSwatchData->file_name;
                                    if (file_exists($oldFilePath)) {
                                        unlink($oldFilePath);
                                    }
                                }
                            }
                        }
                    }

                    $swatchData = [
                        'attribute_id' => $allPostPutVars['attribute_id'],
                        'hex_code' => $allPostPutVars['hex_code']
                    ];
                    if(isset($allPostPutVars['file_name']) && $allPostPutVars['file_name'] != '') {
                        $swatchData['file_name'] = $allPostPutVars['file_name'];
                    }

                    try {
                        $initColorSwatch->update($swatchData);
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Color Swatch', 'updated'),
                            'color_swatch_id' => $colorSwatchId
                        ];
                    } catch (\Exception $e) {
                        $serverStatusCode = EXCEPTION_OCCURED;
                        $jsonResponse = [
                            'status' => 0,
                            'message' => message('Color Swatch', 'exception'),
                            'exception' => $e->getMessage()
                        ];
                    }
                } else {
                    $serverStatusCode = EXCEPTION_OCCURED;
                    $jsonResponse = [
                        'status' => 0,
                        'message' => message('Color Swatch', 'insufficient'),
                    ];
                }
            }
            

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);

        }

        /**
         * Delete Color Swatch
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function deleteColorSwatch($request, $response, $args)
        {
            $serverStatusCode = OPERATION_OKAY;
            if(isset($args) && count($args) > 0 && $args['color_swatch_id'] != '') {
                $getDeleteId = $args['color_swatch_id'];
                $initColorSwatch = ColorSwatch::where('xe_id', $getDeleteId);
                if($initColorSwatch->count() > 0) {
                    $getColorSwatch = $initColorSwatch->first();
                    if($initColorSwatch->delete()) {

                        // Delete associated Files
                        if(isset($getColorSwatch->file_name) && $getColorSwatch->file_name != "") {
                            $rawFileLocation = SWATCH_FOLDER . $getColorSwatch->file_name;
                            if (file_exists($rawFileLocation)) {
                                chmod($rawFileLocation, 0755);
                                // For Linux System Below code will change the permission of the file
                                shell_exec('chmod -R 777 ' . $rawFileLocation);
                                unlink($rawFileLocation);
                            } 
                        }
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Color Swatch', 'deleted')
                        ];
                    } else {
                        $jsonResponse = [
                            'status' => 0,
                            'message' => message('Color Swatch', 'error')
                        ];
                    }
                } else {
                    $jsonResponse = [
                        'status' => 0,
                        'message' => message('Color Swatch', 'not_found')
                    ];
                }

            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Color Swatch', 'insufficient')
                ];
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }
    }
    