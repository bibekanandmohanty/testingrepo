<?php 
    /**
     *
     * Manage Products from Various Stores
     *
     * @category   Print Profile
     * @package    Product/Store
     * @author     Original Author <tanmayap@riaxe.com>
     * @author     tanmayap@riaxe.com
     * @copyright  2019-2020 Riaxe Systems
     * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
     * @version    Release: @1.0
     */
    namespace App\Modules\PrintProfiles\Controllers;

    use StoreSpace\Controllers\StoreProductsController;
    use App\Modules\PrintProfiles\Models\PrintProfile;
    
    class PrintProfilesController extends StoreProductsController
    {
        public function savePrintProfile($request, $response, $args) {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $request->getParsedBody();
            if(isset($allPostPutVars['name']) && $allPostPutVars['name'] != "") {
                if(isset($_FILES) && count($_FILES) > 0) {
                    $images_path = PRINT_PROFILE_FOLDER;
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
                $printProfileInit = new PrintProfile($allPostPutVars);
                if($printProfileInit->save()) {
                    $jsonResponse = [
                        'status' => 1,
                        'print_profile_insert_id' => $printProfileInit->xe_id,
                        'message' => message('Print Profile', 'saved')
                    ];
                }
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Print Profile', 'insufficient')
                ];
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }

        public function updatePrintProfile($request, $response, $args) {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $this->parsePut();
            $printProfileUpdateId = $args['id'];
            //echo $this->deleteOldFile('print_profiles', 'file_name', ['xe_id' => $args['id']], PRINT_PROFILE_FOLDER);
            if(
                isset($allPostPutVars['name']) && $allPostPutVars['name'] != "" &&
                isset($printProfileUpdateId) && $printProfileUpdateId > 0
            ) {
                if(PrintProfile::where(['xe_id' => $printProfileUpdateId])->count() > 0) {
                    if(isset($_FILES) && count($_FILES) > 0) {
                        $images_path = PRINT_PROFILE_FOLDER;
                        if (!is_dir($images_path)) {
                            mkdir($images_path, 0777, true);
                        } else {
                            // Get the next Autoincrement ID from the `Table Name` specified
                            // Generate a new name for the uploading file
                            $fileExtension = pathinfo($_FILES['upload']['name'], PATHINFO_EXTENSION);
                            $newUploadedFileName = getRandom() . "." . $fileExtension;
            
                            if (copy($_FILES['upload']['tmp_name'], $images_path . $newUploadedFileName) === true) {
                                $allPostPutVars += ['file_name' => $newUploadedFileName];
                                // Delete old file and add new file if provided
                                deleteOldFile('print_profiles', 'file_name', ['xe_id' => $args['id']], PRINT_PROFILE_FOLDER);
                            }
                        }
                    }

                    $printAreaInit = PrintProfile::find($printProfileUpdateId);
                    $printAreaInit->name = $allPostPutVars['name'];
                    $printAreaInit->file_name = $allPostPutVars['file_name'];
    
                    if($printAreaInit->save()) {
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Print Profile', 'updated')
                        ];
                    } else {
                        $jsonResponse = [
                            'status' => 0,
                            'message' => message('Print Profile', 'error')
                        ];
                    }
                } else {
                    $jsonResponse = [
                        'status' => 0,
                        'message' => message('Print Profile', 'insufficient')
                    ];
                }
                
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Print Profile', 'insufficient')
                ];
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }
        /**
         * Get list of Print Profiles
         *
         * @author     tanmayap@riaxe.com
         * @date       5 Oct 2019
         * @parameter  Slim default parameters
         * @response   A JSON Response
         */
        public function getPrintProfile($request, $response, $args)
        {
            $serverStatusCode = OPERATION_OKAY;
            $jsonResponse = [];

            $initPrintProfile = PrintProfile::whereNotNull('name');
            
            if($initPrintProfile->count() > 0) {
                $jsonResponse = [
                    'status' => 1,
                ];
                $jsonResponse['data'] = $initPrintProfile->orderBy('name', 'asc')->select('xe_id as print_profile_id', 'name', 'file_name')->get();
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Print Profile', 'not_found'),
                ];
            }
            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }

        public function deletePrintProfile($request, $response, $args) {
            $jsonResponse = [];
            $serverStatusCode = OPERATION_OKAY;
            $allPostPutVars = $request->getParsedBody();
            $printProfileUpdateId = $args['id'];

            if(
                isset($printProfileUpdateId) && $printProfileUpdateId > 0
            ) {
                if(PrintProfile::where(['xe_id' => $printProfileUpdateId])->count() > 0) {
                    $printAreaInit = PrintProfile::find($printProfileUpdateId);
                    deleteOldFile('print_profiles', 'file_name', ['xe_id' => $args['id']], PRINT_PROFILE_FOLDER);
                    if($printAreaInit->delete()) {
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Print Profile', 'deleted')
                        ];
                    } else {
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Print Profile', 'error')
                        ];
                    }
                } else {
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Print Profile', 'insufficient')
                    ];
                }
            } else {
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Print Profile', 'insufficient')
                ];
            }

            return $response->withJson($jsonResponse)
                ->withHeader("Access-Control-Allow-Origin", "*")
                ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
                ->withStatus($serverStatusCode);
        }
    }