<?php
/**
 * Manage Color Palette
 *
 * PHP version 5.6
 *
 * @category  ColorPalettes
 * @package   Assets
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\ColorPalettes\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Dependencies\Zipper as Zipper;
use App\Modules\ColorPalettes\Models\ColorPalette;
use App\Modules\ColorPalettes\Models\ColorPaletteCategory as Category;
use Illuminate\Database\Capsule\Manager as DB;
use Intervention\Image\ImageManagerStatic as ImageManager;

/**
 * ColorPalettes Controller
 *
 * @category ColorPalettes
 * @package  Assets
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class ColorPaletteController extends ParentController {
	/**
	 * Initiate Constructer function
	 */
	public function __construct() {
		DB::enableQueryLog();
	}
	/**
	 * Get: Get ColorPalette(s) Details
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   05 Dec 2019
	 * @return json
	 */
	public function getColors($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$subcatagoryIdArr = [];
		$otherFilterData = [];
		// set the common status for error
		$jsonResponse = [
			'status' => 1,
			'data' => [],
			'message' => message('Color', 'error'),
		];
		$isDebug = $request->getQueryParam('debug');
		$finalData = [];
		$categoryInit = new Category();
		$assetTypeArr = $categoryInit->AssetsTypeId();
		if (!empty($assetTypeArr) && $assetTypeArr['status'] == 1) {
			$assetTypeId = $assetTypeArr['asset_type_id'];
			if (!empty($args) && !empty($args['id'])) {
				//For single Color data
				$colorPaletteInit = new ColorPalette();
				$processedColors = $colorPaletteInit->join(
					'categories',
					'color_palettes.category_id',
					'=',
					'categories.xe_id'
				)
					->select(
						'color_palettes.xe_id', 'color_palettes.category_id',
						'color_palettes.subcategory_id', 'color_palettes.name',
						'color_palettes.price', 'color_palettes.value',
						'color_palettes.hex_value', 'categories.name as cat_name'
					)
					->where('color_palettes.xe_id', '=', $args['id'])
					->orderBy('xe_id', 'DESC')
					->get();

                $totalCounts = $processedColors->count();
                if ($totalCounts > 0) {
					$colorType = empty($processedColors[0]['hex_value']) ? 'pattern' : 'color';
                    if ($colorType == 'pattern') {
                        $processedColors[0]['file_name'] = path(
                            'read', 'colorpalette'
                        )
                            . 'thumb_' . $processedColors[0]['value'];
                    }
                    $processedColors[0]['color_type'] = $colorType;
                    $jsonResponse = [
                        'status' => 1,
                        'data' => $processedColors,
                    ];
                }
            } else {
                // Collect all Filter columns from url
                $catagoryId = ($request->getQueryParam('cat_id') != '')
                ? $request->getQueryParam('cat_id') : 0;
                $subcatagoryId = (!empty($request->getQueryParam('subcat_id')))
                ? $request->getQueryParam('subcat_id') : [];
                $keyword = ($request->getQueryParam('keyword') != '')
                ? $request->getQueryParam('keyword') : '';
                $sortBy = $request->getQueryParam('sortby');
                $order = $request->getQueryParam('order');
                $printProfileKey = $request->getQueryParam('print_profile_id');
                $getStoreDetails = get_store_details($request);
                $sortingData = $request->getQueryParam('shorting');
                $isAdmin = ($request->getQueryParam('isAdmin') != '')
                ? $request->getQueryParam('isAdmin') : 0;
				if (!empty($request->getQueryParam('store_id'))) {
					$getStoreDetails['store_id'] = $request->getQueryParam('store_id');
				} else {
					$getStoreDetails = get_store_details($request);
				}
				// For multiple Color data
				// Filter by category ID
				if (!empty($catagoryId)) {
					$getCategory = $categoryInit->where(
						[
							'xe_id' => $catagoryId,
							'store_id' => $getStoreDetails['store_id'],
						]
					)
						->select('xe_id', 'name', 'is_defined', 'is_disable')
						->get();
				} else {
					$getCategory = $categoryInit->where(
						[
							'parent_id' => 0, 'asset_type_id' => $assetTypeId,
							'store_id' => $getStoreDetails['store_id'],
						]
					)
						->orderBy('sort_order', 'asc')
						->select('xe_id', 'name' , 'is_defined', 'is_disable')
						->get();
				}
				// Filter by subcatagoryId ID
				if (!empty($subcatagoryId)) {
					$subcatagoryIdArr = json_clean_decode($subcatagoryId, true);
				}
                $sortingData = $request->getQueryParam('sorting');
                $isAdmin = ($request->getQueryParam('is_admin') != '')
                ? $request->getQueryParam('is_admin') : 0;

				// Other filter data
				$otherFilterData = [
					'keyword' => $keyword,
					'sortBy' => $sortBy,
					'order' => $order,
				];

				$otherFilterData += [
					'store_id' => $getStoreDetails['store_id'],
				];

                $totalCounts = count($getCategory->toArray());//$getCategory->count();
                if ($totalCounts > 0) {
                    $thisResult = [];
                    foreach ($getCategory as $catValue) {
                        $thisResult = [
                            "cat_id" => $catValue['xe_id'],
                            "cat_name" => $catValue['name'],
                            "is_defined" => $catValue['is_defined'],
                            "is_disable" => $catValue['is_disable'],
                            "subcategory" => $this->_getSubcategoryData(
                                ((!empty($subcatagoryIdArr)
                                    ? $subcatagoryIdArr
                                    : $catValue['xe_id'])), $catValue['name'],
                                $otherFilterData, $printProfileKey, $sortingData, $isAdmin
                            ),
                        ];
                        // Acc. to the Print Prof. Assigning status we push data to api
                        if (!empty($printProfileKey)) {
                            $assocPrintProfiles = $this->checkInPrintProfile(
                                $catValue['xe_id'], $printProfileKey
                            );
                            if ($assocPrintProfiles) {
                                $finalData[] = $thisResult;
                            }
                        } else {
                            $finalData[] = $thisResult;
                        }
                    }
                    $jsonResponse = [
                        'status' => 1,
                        'data' => $finalData,
                    ];
                }
            }
        }
        if (!empty($isDebug)) {
            debug(DB::getQueryLog());
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Getting subcategory w.r.t category
     *
     * @param $subcatIds       Subcat id
     * @param $catName         category name
     * @param $otherFilterData data to filter
     *
     * @author debashrib@riaxe.com
     * @date   05 Dec 2019
     * @return array of subcategory
     */
    private function _getSubcategoryData($subcatIds, $catName, $otherFilterData, $printProfileKey, $sortingData, $isAdmin)
    {
        $subcategory = [];
        $page = "";
        $categoryInit = new Category();
        if ($isAdmin) {
            $page = 1;
            $perpage = 10;
        }
        $sortingData = json_decode($sortingData,true);
        if (is_array($subcatIds) && !empty($subcatIds)) {
            $getSubcategories = $categoryInit->select('xe_id', 'name', 'parent_id')
                ->whereIn('xe_id', $subcatIds)
                ->where('is_disable', 0)
                ->orderBy('sort_order', 'asc')
                ->get();
        } else {
            $getSubcategories = $categoryInit->select('xe_id', 'name', 'parent_id')
                ->where(['parent_id' => $subcatIds, 'is_disable' => 0])
                ->orderBy('sort_order', 'asc')
                ->get();
        }

        if ($getSubcategories->count() > 0) {
            foreach ($getSubcategories->toArray() as $value) {
                if (!empty($printProfileKey)) {
                    $profileCatRelObj = new \App\Modules\PrintProfiles\Models\PrintProfileAssetsCategoryRel();
                    $assetTypeArr = $this->assetsTypeId('color-palettes');
                    $printProfileId = json_clean_decode($printProfileKey, true);
                    $profileCatRelDetails = $profileCatRelObj->where(
                        [
                            'asset_type_id' => $assetTypeArr['asset_type_id'],
                            'category_id' => $value['xe_id'],
                            'print_profile_id' => $printProfileId[0],
                        ]
                    );
                    if ($profileCatRelDetails->count() > 0) {
                        if (!empty($sortingData)) {
                            foreach ($sortingData as $keySort => $valueSort) {
                                if ($valueSort['sub_id'] == $value['xe_id']) {
                                    $page = $valueSort['page_no'];
                                    if ($valueSort['per_page']) {
                                        $perpage = $valueSort['per_page'];
                                    }
                                    $colorData = $this->_getColorData(
                                            $value['parent_id'], $value['xe_id'],
                                            $catName, $otherFilterData, $page, $perpage
                                        );
                                    $totalRec = $colorData['totalCount'];
                                    unset($colorData['totalCount']);
                                    $subcategory[] = [
                                        'subcat_id' => $value['xe_id'],
                                        'subcat_name' => $value['name'],
                                        'total_record' => $totalRec,
                                        'color_data' => $colorData,
                                    ];
                                } else {
                                    if ($isAdmin) {
                                        $page = 1;
                                        $perpage = 10;
                                    } else 
                                        $page = "";
                                    $colorData = $$this->_getColorData(
                                            $value['parent_id'], $value['xe_id'],
                                            $catName, $otherFilterData, $page, $perpage
                                        );
                                    $totalRec = $colorData['totalCount'];
                                    unset($colorData['totalCount']);
                                    $subcategory[] = [
                                        'subcat_id' => $value['xe_id'],
                                        'subcat_name' => $value['name'],
                                        'total_record' => $totalRec,
                                        'color_data' => $colorData,
                                    ];
                                }
                            }
                        } else {
                            if ($isAdmin) {
                                $page = 1;
                                $perpage = 10;
                            } else 
                                $page = "";
                            $colorData = $this->_getColorData(
                                    $value['parent_id'], $value['xe_id'],
                                    $catName, $otherFilterData, $page, $perpage
                                );
                            $totalRec = $colorData['totalCount'];
                            unset($colorData['totalCount']);
                            $subcategory[] = [
                                'subcat_id' => $value['xe_id'],
                                'subcat_name' => $value['name'],
                                'total_record' => $totalRec,
                                'color_data' => $colorData,
                            ];
                        }
                    }
                } else {
                    if (!empty($sortingData)) {
                        foreach ($sortingData as $keySort => $valueSort) {
                            if ($valueSort['sub_id'] == $value['xe_id']) {
                                $page = $valueSort['page_no'];
                                if ($valueSort['per_page']) {
                                    $perpage = $valueSort['per_page'];
                                }
                                $colorData = $this->_getColorData(
                                        $value['parent_id'], $value['xe_id'],
                                        $catName, $otherFilterData, $page, $perpage
                                    );
                                $totalRec = $colorData['totalCount'];
                                unset($colorData['totalCount']);
                                $subcategory[] = [
                                    'subcat_id' => $value['xe_id'],
                                    'subcat_name' => $value['name'],
                                    'total_record' => $totalRec,
                                    'color_data' => $colorData,
                                ];
                            } else {
                                if ($isAdmin) {
                                    $page = 1;
                                    $perpage = 10;
                                } else 
                                    $page = "";
                                $colorData = $this->_getColorData(
                                        $value['parent_id'], $value['xe_id'],
                                        $catName, $otherFilterData, $page, $perpage
                                    );
                                $totalRec = $colorData['totalCount'];
                                unset($colorData['totalCount']);
                                $subcategory[] = [
                                    'subcat_id' => $value['xe_id'],
                                    'subcat_name' => $value['name'],
                                    'total_record' => $totalRec,
                                    'color_data' => $colorData,
                                ];
                            }
                        }
                    } else {
                        if ($isAdmin) {
                            $page = 1;
                            $perpage = 10;
                        } else 
                            $page = "";
                            $colorData = $this->_getColorData(
                                $value['parent_id'], $value['xe_id'],
                                $catName, $otherFilterData, $page, $perpage
                            );
                        $totalRec = $colorData['totalCount'];
                        unset($colorData['totalCount']);
                        $subcategory[] = [
                            'subcat_id' => $value['xe_id'],
                            'subcat_name' => $value['name'],
                            'total_record' => $totalRec,
                            'color_data' => $colorData,
                        ];
                    }
                }
            }
        }
        return $subcategory;
    }

    /**
     * Getting color data w.r.t category and subcategory
     *
     * @param $catId           category id
     * @param $subcatId        Subcat id
     * @param $catName         category name
     * @param $otherFilterData data to filter
     *
     * @author debashrib@riaxe.com
     * @date   05 Dec 2019
     * @return array of color data
     */
    private function _getColorData($catId, $subcatId, $catName, $otherFilterData, $page, $perpage)
    {
        $colordata = [];
        $colorPaletteInit = new ColorPalette();
        $getColor = $colorPaletteInit->where('xe_id', '>', 0);
        $getColor->select(
            'xe_id', 'name', 'price', 'value', 'hex_value'
        )
            ->where(
                [
                    'category_id' => $catId,
                    'subcategory_id' => $subcatId,
                ]
            );
        if (!empty($otherFilterData['keyword'])) {
            $getColor->where(
                function ($query) use ($otherFilterData) {
                    $query->where(
                        'name', 'LIKE',
                        '%' . $otherFilterData['keyword'] . '%'
                    )
                        ->orwhere(
                            'hex_value', 'LIKE',
                            '%' . $otherFilterData['keyword'] . '%'
                        );
                }
            );
        }

        if (!empty($otherFilterData['store_id'])) {
            $getColor->where('store_id', $otherFilterData['store_id']);
        }
        $getTotalPerFilters = $getColor->count();
        if ($page != "") {
            $totalItem = empty($perpage) ? 10 : $perpage;
            $offset = $totalItem * ($page - 1);
            $getColor->skip($offset)->take($totalItem);
        }

		if (!empty($otherFilterData['sortBy']) && !empty($otherFilterData['order'])) {
			$getColor->orderBy(
				$otherFilterData['sortBy'], $otherFilterData['order']
			);
		} else {
			$getColor->orderBy('xe_id', 'DESC');
		}

        $getColorRecords = $getColor->get();
        if (!empty($getColorRecords)) {
            foreach ($getColorRecords as $value) {
                $colorType = empty($value->hex_value) ? 'pattern' : 'color';
                if ($colorType == 'pattern') {
                    $colorValue = path('read', 'colorpalette')
                    . 'thumb_' . $value->value;
                } else {
                    $colorValue = $value->value;
                }
                $colordata[] = [
                    'id' => $value->xe_id,
                    'name' => $value->name,
                    'price' => $value->price,
                    'value' => $colorValue,
                    'hex_value' => $value->hex_value,
                    'color_type' => $colorType,
                ];
            }
        }
        $colordata["totalCount"] = $getTotalPerFilters;
        return $colordata;
    }
    /**
     * Check if the record exists according to the category and print profiles
     *
     * @param $categoryId Category id
     * @param $profileKey Print Profile Ids in a json format
     *
     * @author tanmayap@riaxe.com
     * @date   27 Feb 2020
     * @return boolean
     */
    public function checkInPrintProfile($categoryId, $profileKey)
    {
        if (!empty($categoryId)) {
            $printProfileIds = json_clean_decode($profileKey, true);
            $profileCatRelObj = new \App\Modules\PrintProfiles\Models\PrintProfileAssetsCategoryRel();
            $assetTypeArr = $this->assetsTypeId('color-palettes');
            $profileCatRelDetails = $profileCatRelObj->where(
                [
                    'asset_type_id' => $assetTypeArr['asset_type_id'],
                    'category_id' => $categoryId,
                ]
            )
                ->whereIn('print_profile_id', $printProfileIds);
            if ($profileCatRelDetails->count() > 0) {
                return true;
            }
        }
        return false;
    }
    /**
     * POST: Save ColorPalette Data
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author debashrib@riaxe.com
     * @date   06 Dec 2019
     * @return json response wheather data is saved or any error occured
     */
    public function saveColors($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $allPostPutVars = $request->getParsedBody();
        $getStoreDetails = get_store_details($request);
        $jsonResponse = [
            'status' => 0,
            'message' => message('Color', 'error'),
        ];
        $fileExtension = '';

		if (!empty($allPostPutVars)) {
			// If any file exist then upload
			$fileName = do_upload(
				'upload', path('abs', 'colorpalette'), [150], 'string'
			);
			$uploadFileExt = pathinfo($fileName, PATHINFO_EXTENSION);
			$storeId = $allPostPutVars['store_id'] = $getStoreDetails['store_id'];
			$catId = $allPostPutVars['category_id'];
			$subcatId = $allPostPutVars['subcategory_id'];
			$uploadedFilePath = path('abs', 'colorpalette') . $fileName;
			if ($uploadFileExt == 'csv') {
				try {
					if (!empty($fileName)) {
						// function to save data from csv
						$colorLastInsertId = $this->_saveCSVData(
							$storeId, $catId, $subcatId, $uploadedFilePath
						);
					}
				} catch (\Exception $e) {
					$serverStatusCode = EXCEPTION_OCCURED;
					create_log(
						'color', 'error',
						[
							'message' => $e->getMessage(),
							'extra' => [
								'module' => 'Import data by CSV file',
							],
						]
					);
				}
			} else if ($uploadFileExt == 'zip') {
				try {
					if (!empty($fileName)) {
						// function to save data from zip
						$colorLastInsertId = $this->_saveZipData(
							$storeId, $catId, $subcatId, $uploadedFilePath
						);
					}
				} catch (\Exception $e) {
					$serverStatusCode = EXCEPTION_OCCURED;
					create_log(
						'color', 'error',
						[
							'message' => $e->getMessage(),
							'extra' => [
								'module' => 'Import data by ZIP file',
							],
						]
					);
				}
			} else {
				try {
					// During pattern file uploading, this code will run
					if (!empty($uploadFileExt) && !empty($uploadedFilePath)) {
						$allPostPutVars['value'] = $fileName;
					}
					$color = new ColorPalette($allPostPutVars);
					$color->save();
					$colorLastInsertId = $color->xe_id;
				} catch (\Exception $e) {
					$serverStatusCode = EXCEPTION_OCCURED;
					create_log(
						'color', 'error',
						[
							'message' => $e->getMessage(),
							'extra' => [
								'module' => 'Saving a color data',
							],
						]
					);
				}
			}
		}
		if (!empty($colorLastInsertId)) {
			$jsonResponse = [
				'status' => 1,
				'message' => message('Color', 'saved'),
				'color_id' => $colorLastInsertId,
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Saving data from csv file
	 *
	 * @param $storeId  Store_Id
	 * @param $catId    Category_Id
	 * @param $subcatId Subcategory_Id
	 * @param $filePath csv file path
	 *
	 * @author debashrib@riaxe.com
	 * @date   06 Dec 2019
	 * @return last inserted id
	 */
	private function _saveCSVData($storeId, $catId, $subcatId, $filePath) {
		$categoryInit = new Category();
		$getCategory = $categoryInit->find($catId);
		$catName = $getCategory->name;
		$file = fopen($filePath, "r");
		$csvData = [];
		$loop = 0;
		$colorLastInsertId = 0;
		while (($column = fgetcsv($file, 10000, ",")) !== false) {
			if ($loop != 0) {
				if ($catName == 'CMYK') {
					$value = $column[3] . ',' . $column[4]
						. ',' . $column[5] . ',' . $column[6];
				} elseif ($catName == 'RGB') {
					$value = $column[3] . ',' . $column[4] . ',' . $column[5];
				} elseif ($catName == 'Pantone'
					|| $catName == 'Embroidery Thread'
				) {
					$value = $column[3];
				}
				$csvData[$loop] = [
					'store_id' => $storeId,
					'category_id' => $catId,
					'subcategory_id' => $subcatId,
					'name' => $column[0],
					'hex_value' => $column[1],
					'price' => (isset($column[2]) && $column[2] != "")
					? $column[2] : 0,
					'value' => $value,
				];
				// Save Color Data
				$color = new ColorPalette($csvData[$loop]);
				$color->save();
				$colorLastInsertId = $color->xe_id;
			}
			$loop++;
		}
		fclose($file);
		if (!empty($colorLastInsertId) && file_exists($filePath)) {
			try {
				unlink($filePath);
			} catch (\Exception $e) {
				create_log(
					'color', 'error',
					[
						'message' => $e->getMessage(),
						'extra' => [
							'module' => 'Saving CSV color data',
						],
					]
				);
			}
		}
		return $colorLastInsertId;
	}

	/**
	 * Saving data from zip file
	 *
	 * @param $storeId     Store_Id
	 * @param $catId       Category_Id
	 * @param $subcatId    Subcategory_Id
	 * @param $zipFilePath zip file path
	 *
	 * @author debashrib@riaxe.com
	 * @date   07 Dec 2019
	 * @return last inserted id
	 */
	private function _saveZipData($storeId, $catId, $subcatId, $zipFilePath) {
		$categoryInit = new Category();
		$getCategory = $categoryInit->find($catId);
		$catName = $getCategory->name;
		$colorLastInsertId = 0;
		if ($catName == 'Pattern') {
			$imagesPath = path('abs', 'colorpalette');
			$zipExtractedPath = $imagesPath . uniqid(
				'zipextract' . date('Ymdhis') . '-'
			);
			if (!is_dir($zipExtractedPath)) {
				mkdir($zipExtractedPath, 0777, true);
			}
			shell_exec('chmod -R 777 ' . $zipExtractedPath);
			$zip = new Zipper();
			$zipStatus = $zip->make($zipFilePath);
			if ($zipStatus) {
				$zip->extractTo($zipExtractedPath);
			}
			$rawCsvFilePathArr = glob($zipExtractedPath . "/*.csv");
			$rawCsvFilePath = $rawCsvFilePathArr[0];
			if (!empty($rawCsvFilePath)) {
				$file = fopen($rawCsvFilePath, "r");
				$csvData = [];
				$loop = 0;
				while (($column = fgetcsv($file, 10000, ",")) !== false) {
					if ($loop != 0) {
						$imagePathArr = glob($zipExtractedPath . "/" . $column[2]);
						$patterImgPath = $imagePathArr[0];
						$patternImg = getRandom() . $column[2];
						$newPatterImgPath = $imagesPath . $patternImg;
						// copy patter image file from extreacted folder to root
						// folder
						if (copy($patterImgPath, $newPatterImgPath)) {
							// creating thumb file
							$convertToSize = [100];
							$imageManagerInit = new ImageManager();
							$img = $imageManagerInit->make($newPatterImgPath);
							foreach ($convertToSize as $dimension) {
								$img->resize($dimension, $dimension);
								$img->save($imagesPath . 'thumb_' . $patternImg);
							}

							// Creating a Associative array which contains the
							// Database row for inserting into the DB
							$csvData[$loop] = [
								'store_id' => $storeId,
								'category_id' => $catId,
								'subcategory_id' => $subcatId,
								'name' => $column[0],
								'price' => (isset($column[1]) && $column[1] != "")
								? $column[1] : 0,
								'value' => $patternImg,
							];
							// Save Color Data
							$color = new ColorPalette($csvData[$loop]);
							$color->save();
							$colorLastInsertId = $color->xe_id;
						}
					}
					$loop++;
				}
			}
			$zip->close();
			fclose($file);
			if (!empty($colorLastInsertId)) {
				// delete zip file
				if (file_exists($zipFilePath)) {
					unlink($zipFilePath);
				}
				// remove extracted zip folder with file inside it
				if (file_exists($zipExtractedPath)) {
					array_map('unlink', glob("$zipExtractedPath/*.*"));
					rmdir($zipExtractedPath);
				}
			}
		}
		return $colorLastInsertId;
	}

	/**
	 * PUT: Update a single ColorPalette
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   07 Dec 2019
	 * @return json response wheather data is updated or not
	 */
	public function updateColor($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$updateData = $request->getParsedBody();
		$jsonResponse = [
			'status' => 0,
			'message' => message('Color', 'error'),
		];
		$getStoreDetails = get_store_details($request);

		if (!empty($args['id'])) {
			$colorId = to_int($args['id']);
			$colorPaletteInit = new ColorPalette();
			$getOldColor = $colorPaletteInit->where('xe_id', $colorId);
			if ($getOldColor->count() > 0) {
				// Process file uploading
				$getUploadedFileName = do_upload(
					'upload', path('abs', 'colorpalette'), [150], 'string'
				);
				if (!empty($getUploadedFileName)) {
					$updateData += ['value' => $getUploadedFileName];
					$this->deleteOldFile(
						'color_palettes',
						'value', ['xe_id' => $colorId],
						path('abs', 'colorpalette')
					);
				}
				$updateData += ['store_id' => $getStoreDetails['store_id']];
				// Update record
				try {
					$colorPaletteInit->where('xe_id', $colorId)
						->update($updateData);
					$jsonResponse = [
						'status' => 1,
						'message' => message('Color', 'updated'),
					];
				} catch (\Exception $e) {
					$serverStatusCode = EXCEPTION_OCCURED;
					create_log(
						'color', 'error',
						[
							'message' => $e->getMessage(),
							'extra' => [
								'module' => 'Update color details',
							],
						]
					);
				}
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Delete: Delete a ColorPalette
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   06 Dec 2019
	 * @return json
	 */
	public function deleteColor($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Color', 'error'),
		];
		if (!empty($args) && !empty($args['id'])) {
			$getDeleteIds = $args['id'];
			$getDeleteIdsToArray = json_clean_decode($getDeleteIds, true);
			if (!empty($getDeleteIdsToArray)) {
				$colorPaletteInit = new ColorPalette();
				$getPaletteCount = $colorPaletteInit->whereIn(
					'xe_id', $getDeleteIdsToArray
				)
					->count();
				if ($getPaletteCount > 0) {
					// Fetch Color details
					$getColorDetails = $colorPaletteInit->whereIn(
						'xe_id', $getDeleteIdsToArray
					)
						->select('xe_id')
						->get();
					try {
						foreach ($getColorDetails as $colorFile) {
							if (!empty($colorFile['xe_id'])) {
								$this->deleteOldFile(
									"color_palettes",
									"value",
									['xe_id' => $colorFile['xe_id']],
									path('abs', 'colorpalette')
								);
							}
						}
						$colorPaletteInit->whereIn('xe_id', $getDeleteIdsToArray)
							->delete();
						$jsonResponse = [
							'status' => 1,
							'message' => message('Color', 'deleted'),
						];
					} catch (\Exception $e) {
						$serverStatusCode = EXCEPTION_OCCURED;
						create_log(
							'color', 'error',
							[
								'message' => $e->getMessage(),
								'extra' => [
									'module' => 'Delete color',
								],
							]
						);
					}
				}
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Delete: Delete a ColorPalette Category
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author debashrib@riaxe.com
	 * @date   04 Dec 2019
	 * @return json
	 */
	public function deleteColorCategory($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Subcategory', 'error'),
		];
		if (!empty($args) && !empty($args['id'])) {
			$deleteId = to_int($args['id']);
			$categoryInit = new Category();
			$category = $categoryInit->find($deleteId);

			if (!empty($category->xe_id)) {
				$colorPaletteInit = new ColorPalette();
				$color = $colorPaletteInit->where(
					'subcategory_id', $deleteId
				)
					->select('xe_id')
					->get();
				try {
					$colorIds = [];
					$category->delete();
					foreach ($color->toArray() as $value) {
						$temp = '';
						if (!empty($value['xe_id'])) {
							$temp = $value['xe_id'];
						}
						$this->deleteOldFile(
							"color_palettes",
							"value",
							['xe_id' => $value['xe_id']],
							path('abs', 'colorpalette')
						);
						if ($temp !== '') {
							array_push($colorIds, $temp);
						}
					}
					$colorPaletteInit->whereIn('xe_id', $colorIds)
						->delete();
					$jsonResponse = [
						'status' => 1,
						'message' => message('Subcategory', 'deleted'),
					];
				} catch (\Exception $e) {
					$serverStatusCode = EXCEPTION_OCCURED;
					create_log(
						'color', 'error',
						[
							'message' => $e->getMessage(),
							'extra' => [
								'module' => 'Delete color category',
							],
						]
					);
				}
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
}
