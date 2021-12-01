<?php 
    /**
     *
     * Manage Products from Various Stores
     *
     * @category   Print Area
     * @package    Product/Store
     * @author     Original Author <tanmayap@riaxe.com>
     * @author     tanmayap@riaxe.com
     * @copyright  2019-2020 Riaxe Systems
     * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
     * @version    Release: @1.0
     */
    namespace App\Modules\PrintAreaTypes\Controllers;

    use StoreSpace\Controllers\StoreProductsController;
    use App\Modules\PrintAreaTypes\Models\PrintAreaType;
    use \Gumlet\ImageResize;

    class PrintAreaTypesController extends StoreProductsController
    {
        /**
         * Save Print Area Type
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function savePrintAreaType($request, $response, $args) {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $request->getParsedBody();
            if(isset($allPostPutVars['name']) && $allPostPutVars['name'] != "") {
                if(isset($_FILES) && count($_FILES) > 0 ) {
                    //deleteOldFile('print_areas', 'file_name', ['xe_id' => $printAreaUpdateId], PRINT_AREA_FOLDER);
                    $images_path = PRINT_AREA_TYPE_FOLDER;
                    if (!is_dir($images_path)) {
                        mkdir($images_path, 0777, true);
                    } else {
                        // Get the next Autoincrement ID from the `Table Name` specified
                        // Generate a new name for the uploading file
                        $fileExtension = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
                        $newUploadedFileName = getRandom() . "." . $fileExtension;
        
                        if (copy($_FILES['upload']['tmp_name'], $images_path . $newUploadedFileName) === true) {
                            $allPostPutVars += ['file_name' => $newUploadedFileName];

                            /*echo $images_path . $newUploadedFileName; 

                            $image = new ImageResize('C:/xampp72/htdocs/api/v1/uploads/print_area_type/201910210126272711.jpg'); exit;
                            $image->scale(50);
                            $image->save('C:/xampp72/htdocs/api/v1/uploads/print_area_type/test.jpg');


                            exit;*/
                        }
                    }
                }
                $printAreaTypeInit = new PrintAreaType($allPostPutVars);
                if($printAreaTypeInit->save()) {
                    $jsonResponse = [
                        'status' => 1,
                        'print_area_insert_id' => $printAreaTypeInit->xe_id,
                        'message' => message('Print Area Type', 'saved')
                    ];
                }
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Print Area Type', 'insufficient')
                ];
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }

        /**
         * Update Print Area Type
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function updatePrintAreaType($request, $response, $args) {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $this->parsePut();
            $printAreaTypeUpdateId = $args['id'];

            if(
                isset($allPostPutVars['name']) && $allPostPutVars['name'] != "" &&
                isset($printAreaTypeUpdateId) && $printAreaTypeUpdateId > 0
            ) {
                if(PrintAreaType::where(['xe_id' => $printAreaTypeUpdateId])->count() > 0) {
                    if(isset($_FILES) && count($_FILES) > 0 ) {
                        deleteOldFile('print_area_types', 'file_name', ['xe_id' => $printAreaTypeUpdateId], PRINT_AREA_TYPE_FOLDER);
                        $images_path = PRINT_AREA_TYPE_FOLDER;
                        if (!is_dir($images_path)) {
                            mkdir($images_path, 0777, true);
                        } else {
                            // Get the next Autoincrement ID from the `Table Name` specified
                            // Generate a new name for the uploading file
                            $fileExtension = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
                            $newUploadedFileName = getRandom() . "." . $fileExtension;
            
                            if (copy($_FILES['upload']['tmp_name'], $images_path . $newUploadedFileName) === true) {
                                $updatedFileName = $newUploadedFileName;
                            }
                        }
                    }
                    $printAreaInit = PrintAreaType::find($printAreaTypeUpdateId);
                    $printAreaInit->name = $allPostPutVars['name'];
                    if(isset($updatedFileName) && $updatedFileName != '') {
                        $printAreaInit->file_name = $updatedFileName;
                    }

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
         * Getting List of All Print Area Type
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function getPrintAreaType($request, $response, $args)
        {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            if(PrintAreaType::count() > 0) {
                $jsonResponse['status'] = 1;
                $jsonResponse['data'] = PrintAreaType::get();
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
         * Delete a specific Print Area Type
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function deletePrintAreaType($request, $response, $args) {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $request->getParsedBody();
            $printAreaTypeUpdateId = $args['id'];

            if(
                isset($printAreaTypeUpdateId) && $printAreaTypeUpdateId > 0
            ) {
                if(PrintAreaType::where(['xe_id' => $printAreaTypeUpdateId])->count() > 0) {
                    $printAreaInit = PrintAreaType::find($printAreaTypeUpdateId);
                    if($printAreaInit->delete()) {
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Print Area Type', 'deleted')
                        ];
                    } else {
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Print Area Type', 'error')
                        ];
                    }
                } else {
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Print Area Type', 'insufficient')
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
    