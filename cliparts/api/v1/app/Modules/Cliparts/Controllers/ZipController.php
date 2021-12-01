<?php
/**
 * Manage Zip Import Process for Cliparts
 *
 * PHP version 5.6
 *
 * @category  Clipart_Upload_Zip_File
 * @package   Assets
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Cliparts\Controllers;

use App\Dependencies\Zipper as Zipper;
use App\Modules\Cliparts\Controllers\ClipartController;
use App\Modules\Cliparts\Models\Clipart;
use App\Modules\Cliparts\Models\ClipartCategory;
use App\Modules\Cliparts\Models\ClipartCategoryRelation;

/**
 * Zip Controller
 *
 * @category Clipart_Upload_Zip_File
 * @package  Assets
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ZipController extends ClipartController
{
    /**
     * POST: Import the zip file which should contains images and a csv files
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tanmayap@riaxe.com
     * @date   12 Aug 2019
     * @return json response
     */
    public function zipImport($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Clipart zip file upload', 'error'),
        ];
        // Get Store Specific Details from helper
        $getStoreDetails = get_store_details($request);
        // Check if any file is requested
        $uploadedFiles = $request->getUploadedFiles();
        if (!empty($uploadedFiles['zip']->file)
            && $uploadedFiles['zip']->file != null
        ) {
            // Upload the zip file into the folder
            $assetsFolder = path('abs', 'vector');
            $archivesPath = $assetsFolder . 'archives/';
            $extractedFolder = path('abs', 'vector') . 'extracted/';
            // Create a Folder if not exists for Archive files
            create_directory($archivesPath);

            $zipFilename = uniqid('zip-' . date('Ymd') . '-') . '.zip';
            // Upload the file with a predefined file name, thats why here
            // copy() is used.
            if (copy(
                $uploadedFiles['zip']->file, $archivesPath . $zipFilename
            ) === true
            ) {
                // Extract the zip file to a specific directory
                $zipFilePath = $archivesPath . $zipFilename;
                $zipExtractedFolder = uniqid('zipextract' . date('Ymdhis') . '-');
                $zipExtractedPath = $extractedFolder . $zipExtractedFolder;
                create_directory($zipExtractedPath);

                $zip = new Zipper();
                $zipStatus = $zip->make($zipFilePath);
                if ($zipStatus) {
                    $zip->extractTo($zipExtractedPath);
                }
            }
            // Get the records from the csv file and insert into the database
            $rawCsvFilePath = $extractedFolder . $zipExtractedFolder . '/en.csv';

            // Get all vector images form Renaming file of vector then move to
            // main vector directory
            $readVectorDir = $extractedFolder . $zipExtractedFolder;
            $files = read_dir($readVectorDir, true);

            $vectorFileNameLinks = [];
            foreach ($files as $sourceFile) {
                if (!is_dir($sourceFile)) {
                    $explodeFile = explode(
                        $zipExtractedFolder . SEPARATOR, $sourceFile
                    );
                    // Get Old file name from the url
                    $oldFileNameExtension = pathinfo(
                        $sourceFile, PATHINFO_EXTENSION
                    );
                    // Craete a random file name against each file
                    $newFile = getRandom() . '.' . $oldFileNameExtension;
                    // As CSV contains file location, So here the csv file
                    // location and real file locations are stored in a array
                    $vectorFileNameLinks[$explodeFile[1]] = $newFile;
                    // Move the files from the subdirectories to the vector
                    // directory, if the file is not csv
                    if (isset($oldFileNameExtension)
                        && $oldFileNameExtension != 'csv'
                    ) {
                        rename(
                            $sourceFile, $assetsFolder . pathinfo(
                                $newFile, PATHINFO_BASENAME
                            )
                        );
                    }
                }
            }

            /**
             * Get the CSV file and Import the csv to the Database
             * Fetch the CSV from the file and loop through the each line
             */
            if (file_exists($rawCsvFilePath)) {
                $readCsvFile = fopen($rawCsvFilePath, "r");
                $csvData = [];
                $loop = 0;
                while (($column = fgetcsv($readCsvFile, 10000, ",")) !== false) {
                    if ($loop != 0) {
                        // Get the real file name from the old file name by
                        // using  the Array
                        // Find / and replace with \\
                        $findFileName = str_replace("/", SEPARATOR, $column[0]);
                        $getFileName = $vectorFileNameLinks[$findFileName];

                        // Creating a Associative array which contains the
                        // Database row for inserting into the DB
                        $csvData[$loop] = [
                            'store_id' => $getStoreDetails['store_id'],
                            'name' => $column[1],
                            'price' => (isset($column[6]) && $column[6] != "")
                            ? $column[6] : 0,
                            'width' => (isset($column[7]) && $column[7] != "")
                            ? $column[7] : 0,
                            'height' => (isset($column[8]) && $column[8] != "")
                            ? $column[8] : 0,
                            'file_name' => $getFileName,
                            'is_scaling' => $column[9],
                        ];

                        // Save Clipart Record
                        $saveClipart = new Clipart($csvData[$loop]);
                        $saveClipart->save();
                        $currentClipartId = $saveClipart->xe_id;
                        // Save Category and Clipart Relation
                        $catData = [];
                        $subCatData = [];
                        $categoryId = $this->_getCatId(
                            $column[3], $getStoreDetails['store_id']
                        );
                        if (isset($categoryId) && $categoryId != "") {
                            $catData += [
                                [
                                    'clipart_id' => $currentClipartId,
                                    'category_id' => $categoryId,
                                ],
                            ];
                            $catRelInit = new ClipartCategoryRelation();
                            $catRelInit->insert($catData);
                            $subCategoryId = $this->_getSubCatId(
                                $column[4], $categoryId, $getStoreDetails['store_id']
                            );
                            $subCatData += [
                                [
                                    'clipart_id' => $currentClipartId,
                                    'category_id' => $subCategoryId,
                                ],
                            ];
                            $subCatRelInit = new ClipartCategoryRelation();
                            $subCatRelInit->insert($subCatData);
                        }
                        // Save Clipart Tags with the Method inside ClipartController
                        $this->saveClipartTags($getStoreDetails['store_id'], $currentClipartId, $column[5]);
                    }
                    $loop++;
                }
                fclose($readCsvFile);
            }
            // Delete the .zip file
            if (file_exists($rawCsvFilePath) && file_exists($zipFilePath)) {
                unlink($zipFilePath);
            }
            // Delete the Extracted Directory
            delete_directory($zipExtractedPath);

            if (!empty($loop) && $loop > 0) {
                $jsonResponse = [
                    'status' => 1,
                    'total' => count($csvData),
                    'message' => message('CSV', 'saved'),
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * GET: From Category Name, fetch their respective ID
     *
     * @param $name    Category/Subcat Name
     * @param $storeId Store Id
     *
     * @author satyabratap@riaxe.com
     * @date   21 Jan 2019
     * @return boolean/int
     */
    private function _getCatId($name, $storeId)
    {
        if (isset($name)) {
            $categoryInit = new ClipartCategory();
            $getCategory = $categoryInit->select('xe_id');
            $getCategory->where('name', trim($name))->where('parent_id', '=', 0);
            if ($getCategory->count() > 0) {
                $getCategoryDetails = $getCategory->first();
                return $getCategoryDetails['xe_id'];
            } else {
                $orderInit = new ClipartCategory();
                $categoryData = [
                    'store_id' => $storeId,
                    'asset_type_id' => 2,
                    'parent_id' => 0,
                    'name' => trim($name),
                    'sort_order' => $orderInit->max('sort_order') + 1,
                    'is_disable' => 0,
                    'is_default' => 0,
                ];
                $category = new ClipartCategory($categoryData);
                $category->save();
                return $category->xe_id;
            }
        }
    }

    /**
     * GET: From Subcategory Name, fetch their respective ID
     *
     * @param $name    Subcategory Name
     * @param $catId   Category Id
     * @param $storeId Store Id
     *
     * @author satyabratap@riaxe.com
     * @date   21 Jan 2019
     * @return boolean/int
     */
    private function _getSubCatId($name, $catId, $storeId)
    {
        if (isset($name)) {
            $categoryInit = new ClipartCategory();
            $getCategory = $categoryInit->select('xe_id');
            $getCategory->where('name', trim($name))
                ->where('parent_id', '=', $catId);
            if ($getCategory->count() > 0) {
                $getCategoryDetails = $getCategory->first();
                return $getCategoryDetails['xe_id'];
            } else if ($getCategory->count() == 0) {
                $orderInit = new ClipartCategory();
                $categoryData = [
                    'store_id' => $storeId,
                    'asset_type_id' => 2,
                    'parent_id' => $catId,
                    'name' => trim($name),
                    'sort_order' => $orderInit->max('sort_order') + 1,
                    'is_disable' => 0,
                    'is_default' => 0,
                ];
                $category = new ClipartCategory($categoryData);
                $category->save();
                return $category->xe_id;
            }
        }
    }
}
