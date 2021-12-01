<?php 
    /**
     *
     * Manage Print Areas 
     *
     * @category   Print Area
     * @package    Product/Store
     * @author     Original Author <tanmayap@riaxe.com>
     * @author     tanmayap@riaxe.com
     * @copyright  2019-2020 Riaxe Systems
     * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
     * @version    Release: @1.0
     */
    namespace App\Modules\PrintAreas\Controllers;

    use StoreSpace\Controllers\StoreProductsController;
    use App\Modules\PrintAreas\Models\PrintArea;
    use App\Modules\PrintAreaTypes\Models\PrintAreaType;
    
    class PrintAreasController extends StoreProductsController
    {
        /**
         * Save New Print Areas
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function savePrintArea($request, $response, $args) {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $request->getParsedBody();
            if(isset($allPostPutVars['name']) && $allPostPutVars['name'] != "") {
                if(isset($_FILES) && count($_FILES) > 0) {
                    $images_path = PRINT_AREA_FOLDER;
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
                $printAreaInit = new PrintArea($allPostPutVars);
                if($printAreaInit->save()) {
                    $jsonResponse = [
                        'status' => 1,
                        'print_area_insert_id' => $printAreaInit->xe_id,
                        'message' => message('Print Area', 'saved')
                    ];
                }
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Print Area', 'insufficient')
                ];
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }

        /**
         * Update Existing Print Areas
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function updatePrintArea($request, $response, $args) {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $this->parsePut();
            $printAreaUpdateId = $args['id'];

            if(
                isset($allPostPutVars['name']) && $allPostPutVars['name'] != "" &&
                isset($printAreaUpdateId) && $printAreaUpdateId > 0
            ) {
                if(PrintArea::where(['xe_id' => $printAreaUpdateId])->count() > 0) {
                    // Check if the provided print area type is custom area type or not
                    $isCustom = 0;
                    $checkIsCustom = PrintAreaType::find($allPostPutVars['print_area_type_id']);
                    if(isset($checkIsCustom) && isset($checkIsCustom->xe_id) && $checkIsCustom->xe_id > 0) {
                        $isCustom = $checkIsCustom->is_custom;
                    }

                    if(isset($_FILES) && count($_FILES) > 0 && $isCustom == 1) {
                        deleteOldFile('print_areas', 'file_name', ['xe_id' => $printAreaUpdateId], PRINT_AREA_FOLDER);
                        $images_path = PRINT_AREA_FOLDER;
                        if (!is_dir($images_path)) {
                            mkdir($images_path, 0777, true);
                        } else {
                            // Get the next Autoincrement ID from the `Table Name` specified
                            // Generate a new name for the uploading file
                            $fileExtension = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
                            $newUploadedFileName = getRandom() . "." . $fileExtension;
            
                            if (copy($_FILES['upload']['tmp_name'], $images_path . $newUploadedFileName) === true) {
                                $allPostPutVars += ['file_name' => $newUploadedFileName];
                            }
                        }
                    }

                    $printAreaInit = PrintArea::find($printAreaUpdateId);
                
                    $printAreaInit->name = $allPostPutVars['name'];
                    $printAreaInit->print_area_type_id = $allPostPutVars['print_area_type_id'];
                    // Check if Image Exist or not
                    if(isset($allPostPutVars['file_name']) && $allPostPutVars['file_name'] != "") {
                        $printAreaInit->file_name = $allPostPutVars['file_name'];
                    }
                    // If the Print Area type other than Custom then Nullify the file name
                    if(isset($isCustom) && $isCustom != 1) {
                        $printAreaInit->file_name = NULL;
                    }
                    $printAreaInit->width = $allPostPutVars['width'];
                    $printAreaInit->height = $allPostPutVars['height'];
                    $printAreaInit->is_user_defined = $allPostPutVars['is_user_defined'];
                    // $printAreaInit->is_default = $allPostPutVars['is_default'];
    
                    if($printAreaInit->save()) {
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Print Area', 'updated')
                        ];
                    } else {
                        $jsonResponse = [
                            'status' => 0,
                            'message' => message('Print Area', 'error')
                        ];
                    }
                } else {
                    $jsonResponse = [
                        'status' => 0,
                        'message' => message('Print Area', 'insufficient')
                    ];
                }
                
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Print Area', 'insufficient')
                ];
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }

        /**
         * Getting List of All Print Areas
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function getPrintAreas($request, $response, $args)
        {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            if(PrintArea::count() > 0) {
                $jsonResponse['status'] = 1;
                $jsonResponse['data'] = PrintArea::orderBy('xe_id','desc')->get();
            } else {
                $jsonResponse['status'] = 0;
                $jsonResponse['message'] = message('Print Area', 'not_found');
            }
            
            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }
        
        /**
         * Delete requested Print Area by ID
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function deletePrintArea($request, $response, $args) {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $request->getParsedBody();
            $printAreaUpdateId = $args['id'];

            if(
                isset($printAreaUpdateId) && $printAreaUpdateId > 0
            ) {
                if(PrintArea::where(['xe_id' => $printAreaUpdateId])->count() > 0) {
                    $printAreaInit = PrintArea::find($printAreaUpdateId);
                    if($printAreaInit->delete()) {
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Print Area', 'deleted')
                        ];
                    } else {
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Print Area', 'error')
                        ];
                    }
                } else {
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Print Area', 'insufficient')
                    ];
                }
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Print Area', 'insufficient')
                ];
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }
    }
    