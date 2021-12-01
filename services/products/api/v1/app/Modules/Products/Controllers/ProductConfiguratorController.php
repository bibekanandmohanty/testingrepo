<?php
/**
 * Manage Product Configurator
 *
 * PHP version 5.6
 *
 * @category  Product_Configurator
 * @package   Product
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Controllers;

use App\Modules\Products\Models\ProductSection;
use App\Modules\Products\Models\ProductSectionImage;
use App\Modules\Products\Models\ProductSetting;
use ProductStoreSpace\Controllers\StoreProductsController;
use App\Modules\Products\Models\ProductConfigurator;
use App\Modules\Products\Models\ProductConfiguratorSides;
use App\Modules\Backgrounds\Controllers\BackgroundController;

/**
 * Product Configurator Controller
 *
 * @category Product_Configurator_Image
 * @package  Product
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductConfiguratorController extends StoreProductsController
{
    /**
     * GET:Getting List of All Product Configurator Section
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2019
     * @return A JSON Response
     */
    public function getProductConfigurators($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Configurators', 'not_found'),
        ];
        $productId = to_int($args['product_id']);

        if ($productId > 0) {
            $sectionInit = new ProductSection();
            $getSections = $sectionInit->select(
                'xe_id as id', 'name', 'parent_id',
                'sort_order', 'is_disable'
            )
                ->where(
                    [
                        'parent_id' => 0,
                        'product_id' => $productId,
                    ]
                )
                ->orderBy('sort_order', 'asc')
                ->get();

            if ($getSections->count() > 0) {
                $getSections = $getSections->toArray();
                $sectionDetails = [];
                foreach ($getSections as $value) {
                    $subsections = $this->getSubSections($value['id']);
                    if (!empty($subsections)) {
                        $sectionDetails[] = [
                            'id' => $value['id'],
                            'name' => $value['name'],
                            'order' => $value['sort_order'],
                            'is_disable' => $value['is_disable'],
                            'sub_sections' => $this->getSubSections($value['id']),
                        ];
                    } else {
                        $sectionDetails[] = [
                            'id' => $value['id'],
                            'name' => $value['name'],
                            'order' => $value['sort_order'],
                            'is_disable' => $value['is_disable'],
                            'section_images' => $this->getSubSectionImages($value['id']),
                        ];
                    }
                }
                $jsonResponse = [
                    'status' => 1,
                    'data' => $sectionDetails,
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Getting subsections recurssively
     *
     * @param $parentSectionId Parent Section ID
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return All Sub Section List Section section
     */
    protected function getSubSections($parentSectionId)
    {
        $subSection = [];
        $sectionInit = new ProductSection();
        $getSections = $sectionInit->select(
            'xe_id as id', 'name', 'parent_id',
            'sort_order', 'is_disable'
        )
            ->where(['parent_id' => $parentSectionId])
            ->orderBy('sort_order', 'asc')
            ->get();

        foreach ($getSections->toArray() as $value) {
            $sectionInit = new ProductSection();
            $fetchCount = $sectionInit->select(
                'xe_id as id', 'name', 'parent_id', 'is_disable'
            )
                ->where(['parent_id' => $value['id']])
                ->count();

            $subSectionList = [];
            if ($fetchCount > 0) {
                $subSectionList = $this->getSubSections($value['id']);
            }

            $subSection[] = [
                'id' => $value['id'],
                'name' => $value['name'],
                'parent_id' => $value['parent_id'],
                'order' => $value['sort_order'],
                'is_disable' => $value['is_disable'],
                'section_images' => $this->getSubSectionImages($value['id']),
                'sub_sections' => $subSectionList,
            ];
        }

        return $subSection;
    }

    /**
     * Getting subsection images
     *
     * @param $sectionId Section ID
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return All Sub Section List Section section
     */
    protected function getSubSectionImages($sectionId)
    {
        $subSectionImages = [];
        $sectionImageInit = new ProductSectionImage();
        $getImages = $sectionImageInit->select(
            'xe_id', 'name', 'description', 'thumb_value',
            'price', 'sort_order', 'file_name', 'is_disable'
        )
            ->where(['section_id' => $sectionId])
            ->orderBy('sort_order', 'asc')
            ->get();

        foreach ($getImages->toArray() as $value) {
            $subSectionImages[] = [
                'id' => $value['xe_id'],
                'name' => $value['name'],
                'description' => $value['description'],
                'thumb' => $value['thumb_value'],
                'type' => $value['type'],
                'price' => $value['price'],
                'order' => $value['sort_order'],
                'file_name' => $value['file_name'],
                'thumbnail' => $value['thumbnail'],
                'is_disable' => $value['is_disable'],
            ];
        }
        return $subSectionImages;
    }

    /**
     * GET:Getting Configurator Images for Settings
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2019
     * @return A JSON Response
     */
    public function getConfiguratorImages($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Configurator Images', 'not_found'),
        ];
        
        $productId = to_int($args['id']);
        $imgData = [];

        if ($productId > 0) {
            $sectionInit = new ProductSection();
            $getSections = $sectionInit->select('xe_id')
                // ->where('parent_id', '!=', 0)
                ->where(['product_id' => $productId,  'is_disable' => 0])
                ->get();
            if ($getSections->count() > 0) {
                foreach ($getSections->toArray() as $value) {
                    $sectionImageInit = new ProductSectionImage();
                    $getImages = $sectionImageInit->select('file_name', 'thumb_value')
                        ->where(['section_id' => $value['xe_id'], 'is_disable' => 0])
                        ->first();
                    if (!empty($getImages)) {
                        $imgData[] = $getImages;
                    }                
                }
                $jsonResponse = [
                    'status' => 1,
                    'data' => $imgData,
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Post: Save Product Configurator
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return Save Json response
     */
    public function saveProductConfigurator($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Configurator', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $productId = to_int($allPostPutVars['product_id']);
        $parentId = to_int($allPostPutVars['parent_id']);

        if ($productId > 0) {
            $sectionInit = new ProductSection();
            $sortOrder = $sectionInit->where('parent_id', $parentId)
                ->max('sort_order') + 1;
            $sectionData = [
                'product_id' => $productId,
                'name' => $allPostPutVars['name'],
                'parent_id' => $parentId,
                'sort_order' => $sortOrder,
            ];
            $saveSection = new ProductSection($sectionData);
            if ($saveSection->save()) {
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Configurator', 'saved'),
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Put: Update Product Configurator
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return Save Json response
     */
    public function updateProductConfigurator($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'message' => message('Configurator', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        if (!empty($args) && $args['id'] > 0) {
            $sectionData = [
                'name' => $allPostPutVars['name'],
            ];
            try {
                $updateInit = new ProductSection();
                $updateInit->where('xe_id', $args['id'])
                    ->update($sectionData);
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Configurator', 'updated'),
                ];
            } catch (\Exception $e) {
                $serverStatusCode = EXCEPTION_OCCURED;
                create_log(
                    'Product Configurator', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Update product configurator',
                        ],
                    ]
                );
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Delete: Delete Product Configurator
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return Save Json response
     */
    public function deleteProductConfigurator($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'message' => message('Configurator', 'error'),
        ];

        if (!empty($args) && $args['id'] > 0) {
            $deleteInit = new ProductSection();
            $count = $deleteInit->where('xe_id', $args['id'])
                ->count();
            if ($count > 0) {
                $checkChild = $deleteInit->where('parent_id', $args['id'])
                    ->count();
                if ($checkChild > 0) {
                    $childIds = $deleteInit->where('parent_id', $args['id'])
                        ->select('xe_id')
                        ->get();
                    foreach ($childIds as $value) {
                        $sectionId = $value['xe_id'];
                        $imageInit = new ProductSectionImage();
                        $imageIds = $imageInit->where('section_id', $sectionId)
                            ->select('xe_id')
                            ->get();
                        foreach ($imageIds as $imageValue) {
                            $this->deleteConfiguratorImage(
                                $request, $response, ['id' => $imageValue['xe_id']]
                            );
                            $deleteInit->where('parent_id', $args['id'])->delete();
                        }
                    }
                }
                $deleteInit = new ProductSection();
                $deleteInit->where('xe_id', $args['id'])->delete();
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Background', 'deleted'),
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Sort Product Configurator
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   19 Mar 2020
     * @return Sort Json Status
     */
    public function sortProductConfigurator($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Product Configurator', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $sortData = $allPostPutVars['sort_data'];
        $sortDataArray = json_clean_decode($sortData, true);

        if (!empty($sortDataArray)) {
            foreach ($sortDataArray as $section) {
                $sortedData[] = [
                    'parent' => 0,
                    'child' => $section['id'],
                ];
                if (isset($section['children'])
                    && is_array($section['children'])
                    && count($section['children']) > 0
                ) {
                    foreach ($section['children'] as $child) {
                        $sortedData[] = [
                            'parent' => $section['id'],
                            'child' => $child['id'],
                        ];
                    }
                }
            }
        }
        // Final procesing: Set a update array and Update the each record
        $updateSortedData = [];
        $updateStatus = 0;
        if (!empty($sortedData)) {
            foreach ($sortedData as $sortKey => $data) {
                $updateSortedData[] = [
                    'parent_id' => $data['parent'],
                    'sort_order' => $sortKey + 1,
                ];
                $updProductSectionObj = new ProductSection();
                try {
                    $updProductSectionObj->where('xe_id', $data['child'])
                        ->update(['sort_order' => $sortKey + 1]);
                    $updateStatus++;
                } catch (\Exception $e) {
                    // Exception occured
                }
            }
        }
        // Setup Response
        if (isset($updateStatus) && $updateStatus > 0) {
            $jsonResponse = [
                'status' => 1,
                'message' => message('Product Section', 'done'),
            ];
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    
    /**
     * Post: Save Product Configurator Images
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return Save Json response
     */
    public function saveConfiguratorImage($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'message' => message('Configurator Image', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $sectionImageInit = new ProductSectionImage();
        if ($allPostPutVars['name'] != "") {
            $uploadedFiles = $request->getUploadedFiles();
            if (isset($uploadedFiles['thumb'])) {
                $thumb = do_upload(
                    'thumb', path('abs', 'section'), [150], 'string'
                );
            } else {
                $thumb = $allPostPutVars['thumb'];
            }
            $fileName = do_upload(
                'upload', path('abs', 'section'), [150], 'string'
            );
            $sortOrder = $sectionImageInit->where('section_id', $allPostPutVars['section_id'])
                ->max('sort_order') + 1;
            $imageData = [
                'section_id' => $allPostPutVars['section_id'],
                'name' => $allPostPutVars['name'],
                'description' => $allPostPutVars['description'],
                'thumb_value' => $thumb,
                'price' => $allPostPutVars['price'],
                'sort_order' => $sortOrder,
                'file_name' => $fileName,
            ];

            $saveSectionImage = new ProductSectionImage($imageData);
            if ($saveSectionImage->save()) {
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Configurator Image', 'saved'),
                ];
            }
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * Post: Save Bulk Configurator Images
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     *
     * @author tanmayap@riaxe.com
     * @date   28 Feb 2020
     * @return JSON
     */
    public function addBulkConfiguratorImages($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Configurator Image', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $bulkDataSet = json_clean_decode($allPostPutVars['data'], true);

        if (!empty($bulkDataSet)) {
            $bulkSaveRecord = [];
            foreach ($bulkDataSet as $dataKey => $data) {
                $fileName = $thumbData = "";
                $fileName = do_upload(
                    'image_upload_' . $dataKey, path('abs', 'section'), [150], 'string'
                );
                if (!empty($data['thumb'])) {
                    $thumbData = $data['thumb'];
                } else {
                    $thumbData = do_upload(
                        'thumb_' . $dataKey, path('abs', 'section'), [150], 'string'
                    );
                }
                $bulkSaveRecord[$dataKey] = [
                    'section_id' => $allPostPutVars['parent_section_id'],
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'price' => to_decimal($data['price']),
                    'file_name' => $fileName,
                    'thumb_value' => $thumbData,
                    'sort_order' => ($dataKey + 1),
                ];
            }
            if (!empty($bulkSaveRecord)) {
                $imageObj = new ProductSectionImage();
                if ($imageObj->insert($bulkSaveRecord)) {
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Configurator Image', 'saved'),
                    ];
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
    /**
     * Put: Update Product Configurator
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return Save Json response
     */
    public function updateConfiguratorImage($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'message' => message('Configurator Image', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();

        if (!empty($args) && $args['id'] > 0) {
            $imageId = $args['id'];
            $imageInit = new ProductSectionImage();
            $getOldImage = $imageInit->where('xe_id', $imageId);

            if ($getOldImage->count() > 0) {
                // Update image file
                $imageData = [
                    'section_id' => $allPostPutVars['section_id'],
                    'name' => $allPostPutVars['name'],
                    'price' => $allPostPutVars['price']
                ];

                if (isset($allPostPutVars['description']) && $allPostPutVars['description'] != "") {
                    $imageData += ['description' => $allPostPutVars['description']];
                }
                if (isset($allPostPutVars['thumb']) && $allPostPutVars['thumb'] != "") {
                    $imageData += ['thumb_value' => $allPostPutVars['thumb']];
                }
                $thumb = do_upload(
                    'thumb', path('abs', 'section'), [150], 'string'
                );
                if ($thumb != "") {
                    $imageData += ['thumb_value' => $thumb];
                }
                $fileName = do_upload(
                    'upload', path('abs', 'section'), [150], 'string'
                );
                if ($fileName != "") {
                    $imageData += ['file_name' => $fileName];
                }
                try {
                    $imageInit->where('xe_id', $imageId)
                        ->update($imageData);
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Configurator Image', 'updated'),
                    ];
                } catch (\Exception $e) {
                    $serverStatusCode = EXCEPTION_OCCURED;
                    $jsonResponse = [
                        'status' => 0,
                        'message' => message('Configurator Image', 'exeception'),
                        'exception' => show_exception() === true
                        ? $e->getMessage() : '',
                    ];
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Delete: Delete Product Configurator Images
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return Save Json response
     */
    public function deleteConfiguratorImage($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'message' => message('Configurator Image', 'error'),
        ];

        if (!empty($args) && $args['id'] > 0) {
            $imageId = $args['id'];
            $deleteInit = new ProductSectionImage();
            $count = $deleteInit->where('xe_id', $imageId)
                ->count();
            if ($count > 0) {
                $this->deleteOldFile(
                    "product_section_images", "file_name", [
                        'xe_id' => $imageId,
                    ], path('abs', 'section')
                );
                $this->deleteOldFile(
                    "product_section_images", "thumb_value", [
                        'xe_id' => $imageId,
                    ], path('abs', 'section')
                );
                $deleteInit->where('xe_id', $imageId)->delete();
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Configurator Image', 'deleted'),
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Disable/Enable a Configurator
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return Disable/Enable Json response
     */
    public function disableConfigurator($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Configurator', 'error'),
        ];

        if (!empty($args) && $args['id'] > 0) {
            $sectionId = $args['id'];
            $getConfiguratorInit = new ProductSection();
            $configurator = $getConfiguratorInit->find($sectionId);
            if ($configurator->parent_id == 0) {
                $configInit = new ProductSection();
                $getSubConfig = $configInit->where('parent_id', $configurator->xe_id)
                    ->get();
                foreach ($getSubConfig as $subConfigValue) {
                    $subConfigValue->is_disable = !$configurator->is_disable;
                    $subConfigValue->save();
                }
            }
            $configurator->is_disable = !$configurator->is_disable;
            try {
                $configurator->save();
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Configurator', 'done'),
                ];
            } catch (\Exception $e) {
                $serverStatusCode = EXCEPTION_OCCURED;
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Configurator', 'exception'),
                    'exception' => show_exception() === true
                    ? $e->getMessage() : '',
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Disable/Enable a Configurator Images
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2020
     * @return Disable/Enable Json response
     */
    public function disableConfiguratorImage($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Configurator Image', 'error'),
        ];

        if (!empty($args) && $args['id'] > 0) {
            $imageId = $args['id'];
            $getImageInit = new ProductSectionImage();
            $configuratorImage = $getImageInit->find($imageId);
            $configuratorImage->is_disable = !$configuratorImage->is_disable;
            try {
                $configuratorImage->save();
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Configurator Image', 'done'),
                ];
            } catch (\Exception $e) {
                $serverStatusCode = EXCEPTION_OCCURED;
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Configurator Image', 'exception'),
                    'exception' => show_exception() === true
                    ? $e->getMessage() : '',
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Disable/Enable Configurator Settings
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author satyabratap@riaxe.com
     * @date   24 Feb 2020
     * @return Disable/Enable Json response
     */
    public function updateConfiguratorSettings($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Configurator Settings', 'error'),
        ];

        if (!empty($args) && $args['id'] > 0) {
            $productId = $args['id'];
            $getSettingsInit = new ProductSetting();
            $settingId = $getSettingsInit->select('xe_id')
                ->where('product_id', $productId)->first();
            $configuratorSetting = $getSettingsInit->find($settingId['xe_id']);
            $enableStatus = (int)!$configuratorSetting->is_configurator;
            $configuratorSetting->is_configurator = !$configuratorSetting->is_configurator;
            try {
                if (!empty($settingId['xe_id'])) {
                    $configuratorSetting->save();
                    $jsonResponse = [
                        'status' => 1,
                        'is_enable' => $enableStatus,
                        'message' => message('Configurator Settings', 'done'),
                    ];
                } else {
                    $jsonResponse = [
                        'status' => 1,
                        'is_enable' => $enableStatus,
                        'is_setting' => 0,
                        'message' => message('Configurator Settings', 'done'),
                    ];
                }
            } catch (\Exception $e) {
                $serverStatusCode = EXCEPTION_OCCURED;
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Configurator Settings', 'exception'),
                    'exception' => show_exception() === true
                    ? $e->getMessage() : '',
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Post: Save Product Configurator
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   19 Jan 2021
     * @return Save Json response
     */
    public function saveSVGProductConfigurator($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('SVG Configurator', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        $productId = to_int($allPostPutVars['product_id']);

        if (isset($uploadedFiles['upload'])) {
            $svgFileName = do_upload(
                'upload', path('abs', 'section'), [], 'string'
            );
        }
        
        if (isset($uploadedFiles['side-preview'])) {
            $previewFileName = do_upload(
                'side-preview', path('abs', 'section'), [], 'string'
            );
        }

        if ($productId > 0) {
            $proConfiguratorInit = new ProductConfigurator();
            $getConfigurator = $proConfiguratorInit->where('product_id', $productId)->get();
            if ($getConfigurator->count() == 0){
                $configuratorData = [
                    'product_id' => $productId,
                    'name' => $allPostPutVars['item_name'],
                    'price' => $allPostPutVars['price'],
                ];
                $saveConfigurator = new ProductConfigurator($configuratorData);
                if ($saveConfigurator->save()) {
                    $getConfigurator = $proConfiguratorInit->select('xe_id')
                    ->where('product_id', $productId)->get()->toArray();
                    $configuratorSideData = [
                        'section_id' => $getConfigurator[0]['xe_id'],
                        'name' => $allPostPutVars['side_name'],
                        'side_path_obj' => $allPostPutVars['side_path_obj'],
                        'preview_file' => $previewFileName,
                        'svg_file' => $svgFileName,
                    ];
                    $saveConfiguratorSides = new ProductConfiguratorSides($configuratorSideData);
                    if ($saveConfiguratorSides->save()) {
                        $jsonResponse = [
                            'status' => 1,
                            'message' => message('Configurator', 'saved'),
                        ];
                    }
                }
            } else {
                $getOldConfData = $getConfigurator->toArray();
                $configuratorSideData = [
                    'section_id' => $getOldConfData[0]['xe_id'],
                    'name' => $allPostPutVars['side_name'],
                    'side_path_obj' => $allPostPutVars['side_path_obj'],
                    'preview_file' => $previewFileName,
                    'svg_file' => $svgFileName,
                ];
                $saveConfiguratorSides = new ProductConfiguratorSides($configuratorSideData);
                if ($saveConfiguratorSides->save()) {
                    $jsonResponse = [
                        'status' => 1,
                        'message' => message('Configurator', 'saved'),
                    ];
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET:Getting List of All Product Configurator Sides
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   19 Jan 2021
     * @return A JSON Response
     */
    public function getSVGProductConfigurator($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $isReturn = false;
        $jsonResponse = [
            'status' => 0,
            'message' => message('SVG Configurators', 'not_found'),
        ];
        $productId = to_int($args['product_id']);
        $isReturn = $args['isReturn'];

        if ($productId > 0) {
            $proConfiguratorInit = new ProductConfigurator();
            $getProConfigurator = $proConfiguratorInit->select(
                'xe_id', 'product_id',
                'name', 'price'
            )
            ->where('product_id',$productId)
            ->get();

            if ($getProConfigurator->count() > 0) {
                $getCongiguratorData = $getProConfigurator->toArray();
                $configuratorDetails = [];
                foreach ($getCongiguratorData as $value) {
                    $getSides = $this->getConfiguratorSides($value['xe_id']);
                    $configuratorDetails[] = [
                        'sec_id' => $value['xe_id'],
                        'item_name' => $value['name'],
                        'price' => $value['price'],
                        'is_default' => 0,
                        'is_configurator' => 1,
                        'sideList' => $getSides,
                    ];
                }
                $jsonResponse = [
                    'status' => 1,
                    'data' => $configuratorDetails,
                ];
            }
        }
        if ($isReturn) {
            return $configuratorDetails;
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Getting subsections recurssively
     *
     * @param $configuratorId
     *
     * @author mukeshp@riaxe.com
     * @date   19 Jan 2021
     * @return All Sub Section List Section section
     */
    protected function getConfiguratorSides($configuratorId)
    {
        $sides = [];
        $confSidesInit = new ProductConfiguratorSides();
        $getConfSides = $confSidesInit->select(
            'xe_id', 'section_id', 'name', 'side_path_obj',
            'preview_file', 'svg_file'
        )
            ->where(['section_id' => $configuratorId])
            // ->orderBy('sort_order', 'asc')
            ->get();

        foreach ($getConfSides->toArray() as $value) {
            $sides[] = [
                'id' => $value['xe_id'],
                'side_name' => $value['name'],
                'sidePreview' => $value['preview_file'],
                'sideSvg' => $value['svg_file'],
                'side_path_obj' => json_decode($value['side_path_obj'], true),
            ];
        }

        return $sides;
    }

    /**
     * Delete: Delete SVG Product Configurator
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   20 Jan 2021
     * @return Delete Json response
     */
    public function deleteSVGProductConfigurator($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'message' => message('Configurator', 'error'),
        ];

        if (!empty($args) && $args['section_id'] > 0) {
            $deleteInit = new ProductConfigurator();
            $count = $deleteInit->where('xe_id', $args['section_id'])
                ->count();
            if ($count > 0) {
                $confSidesInit = new ProductConfiguratorSides();
                $sideIds = $confSidesInit->where('section_id', $args['section_id'])
                    ->select('xe_id')
                    ->get();
                foreach ($sideIds as $sideValue) {
                    $this->deleteSVGConfiguratorSides(
                        $request, $response, ['side_id' => $sideValue['xe_id']]
                    );
                }
                $deleteInit->where('xe_id', $args['section_id'])->delete();
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Background', 'deleted'),
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Delete: Delete Product Configurator Sides
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   20 Jan 2021
     * @return Delete Json response
     */
    public function deleteSVGConfiguratorSides($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'message' => message('Configurator Image', 'error'),
        ];
        $folder = path('abs', 'section');

        if (!empty($args) && $args['side_id'] > 0) {
            $sideId = $args['side_id'];
            $deleteInit = new ProductConfiguratorSides();
            $count = $deleteInit->where('xe_id', $sideId)
                ->count();
            if ($count > 0) {
            	$getAllFileNames = $deleteInit->select('preview_file','svg_file')
            	->where('xe_id', $sideId)
	            ->get();
	            foreach ($getAllFileNames as $getFile) {
                    if (isset($getFile->preview_file_original) && $getFile->preview_file_original != "") {
                        $previewFileLocation = $folder . $getFile->preview_file_original;
                		delete_file($previewFileLocation);
                    }
                    if (isset($getFile->svg_file_original) && $getFile->svg_file_original != "") {
                        $svGFileLocation = $folder . $getFile->svg_file_original;
                		delete_file($svGFileLocation);
                    }
                }
                $deleteInit->where('xe_id', $sideId)->delete();
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('Configurator Sides', 'deleted'),
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Update: Update SVG Product Configurator Sides
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author Rakesh 
     * @date   20 Jan 2021
     * @return Update Json response
     */
    public function saveSVGConfiguratorSides($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('SVG Configurator side update', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();

        $side_id = to_int($args['side_id']);

        if (isset($uploadedFiles['side-preview'])) {
            $previewFileName = do_upload(
                'side-preview', path('abs', 'section'), [], 'string'
            );
        }

        if (isset($uploadedFiles['upload'])) {
            $svgFileName = do_upload(
                'upload', path('abs', 'section'), [], 'string'
            );
        }

        if ($allPostPutVars['side_name'] != "") {
            $productConfiguratorSidesData['name'] = $allPostPutVars['side_name'];
        }

        if ($allPostPutVars['side_path_obj'] != "") {
            $productConfiguratorSidesData['side_path_obj'] = $allPostPutVars['side_path_obj'];
        }

        if ($previewFileName != null) {
            $productConfiguratorSidesData['preview_file'] = $previewFileName;
        }

        if ($svgFileName != null) {
            $productConfiguratorSidesData['svg_file'] = $svgFileName;
        }
        $productConfiguratorSides = new ProductConfiguratorSides();
        $productConfSides = $productConfiguratorSides->where('xe_id', $side_id)->first();
        if ($productConfiguratorSides->where('xe_id', $side_id)->update($productConfiguratorSidesData)) {
            if ($previewFileName != null) {

                unlink(path('abs', 'section') . "/" . $productConfSides->preview_file);
            }

            if ($svgFileName != null) {

                unlink(path('abs', 'section') . "/" . $productConfSides->svg_file);
            }

            $jsonResponse = [
                'status' => 1,
                'message' => message('Configurator-Side-Updated', 'saved'),
            ];
        }
        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

     /**
     * Disable/Enable SVG Configurator Settings
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   29 Jan 2021
     * @return Disable/Enable Json response
     */
    public function updateSVGConfiguratorSettings($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Configurator SVG Settings', 'error'),
        ];

        if (!empty($args) && $args['id'] > 0) {
            $productId = $args['id'];
            $getSettingsInit = new ProductSetting();
            $settingId = $getSettingsInit->select('xe_id')
                ->where('product_id', $productId)->first();
            $configuratorSetting = $getSettingsInit->find($settingId['xe_id']);
            $enableStatus = (int)!$configuratorSetting->is_svg_configurator;
            $configuratorSetting->is_svg_configurator = !$configuratorSetting->is_svg_configurator;
            try {
                if (!empty($settingId['xe_id'])) {
                    $configuratorSetting->save();
                    $jsonResponse = [
                        'status' => 1,
                        'is_enable' => $enableStatus,
                        'message' => message('Configurator SVG Settings', 'done'),
                    ];
                } else {
                    $jsonResponse = [
                        'status' => 1,
                        'is_enable' => $enableStatus,
                        'is_setting' => 0,
                        'message' => message('Configurator SVG Settings', 'done'),
                    ];
                }
            } catch (\Exception $e) {
                $serverStatusCode = EXCEPTION_OCCURED;
                $jsonResponse = [
                    'status' => 0,
                    'message' => message('Configurator SVG Settings', 'exception'),
                    'exception' => show_exception() === true
                    ? $e->getMessage() : '',
                ];
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * GET:Get Backgroud Colors of respective Configurator Sides
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   19 Jan 2021
     * @return A JSON Response
     */
    public function getColorsSVGProductConfigurator($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'message' => message('Backgroud colors of SVG Configurators', 'not_found'),
        ];
        $productId = to_int($args['product_id']);

        if ($productId > 0) {
            $proConfiguratorInit = new ProductConfigurator();
            $getProConfigurator = $proConfiguratorInit->select(
                'xe_id', 'product_id',
                'name', 'price'
            )
            ->where('product_id',$productId)
            ->get();

            if ($getProConfigurator->count() > 0) {
                $getCongiguratorData = $getProConfigurator->first();
                $configuratorDetails = [];
                $confSidesInit = new ProductConfiguratorSides();
                $getConfSides = $confSidesInit->select('side_path_obj')
                    ->where(['section_id' => $getCongiguratorData['xe_id']])
                    ->get();
                $catIds = [];
                // Fetch all background cat ids from all sides
                foreach ($getConfSides->toArray() as $value) {
                    $sidePath = json_decode($value['side_path_obj'], true);
                    if (!empty($sidePath)) {
                        foreach ($sidePath as $side) {
                            if (!empty($side['background_category'])) {
                                foreach ($side['background_category'] as $catValue) {
                                    $catIds[] = $catValue['id'];
                                }
                            }   
                        }
                    }
                }
                $catIds = array_unique($catIds);
                $request->params["category"] = $catIds; 
                $backgroundInit = new BackgroundController();
                // return response from background API
                return $backgroundInit->getBackgrounds($request, $response, ["category"=>json_encode($catIds)]);
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Put: Update SVG Product Configurator
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Argument parameters
     *
     * @author mukeshp@riaxe.com
     * @date   09 Feb 2021
     * @return Save Json response
     */
    public function updateSVGProductConfigurator($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 1,
            'message' => message('SVG Configurator', 'error'),
        ];
        $allPostPutVars = $request->getParsedBody();
        if (!empty($args) && $args['id'] > 0) {
            $configuratorData = [
                'name' => $allPostPutVars['name'],
                'price' => $allPostPutVars['price'],
            ];
            try {
                $proConfiguratorInit = new ProductConfigurator();
                $proConfiguratorInit->where('xe_id', $args['id'])
                    ->update($configuratorData);
                $jsonResponse = [
                    'status' => 1,
                    'message' => message('SVG Configurator', 'updated'),
                ];
            } catch (\Exception $e) {
                $serverStatusCode = EXCEPTION_OCCURED;
                create_log(
                    'Product Configurator', 'error',
                    [
                        'message' => $e->getMessage(),
                        'extra' => [
                            'module' => 'Update SVG product configurator',
                        ],
                    ]
                );
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }

    /**
     * Post: Clone Product Configurator
     *
     * @param $parentProductId     
     * @param $productId     
     *
     * @author mukeshp@riaxe.com
     * @date   19 Jan 2021
     * @return Save Json response
     */
    public function cloneSVGProductConfigurator($parentProductId, $productId)
    {
        $responseStatus = false;
        if ($productId > 0 && $parentProductId > 0) {
            $proConfiguratorInit = new ProductConfigurator();
            $getConfigurator = $proConfiguratorInit->where('product_id', $parentProductId)->get();
            if ($getConfigurator->count() > 0){
                $parentConfData = $getConfigurator->toArray();
                $configuratorData = [
                    'product_id' => $productId,
                    'name' => $parentConfData[0]['name'],
                    'price' => $parentConfData[0]['price'],
                ];
                $saveConfigurator = new ProductConfigurator($configuratorData);
                if ($saveConfigurator->save()) {
                    $sides = [];
                    $confSidesInit = new ProductConfiguratorSides();
                    $getConfSides = $confSidesInit->select(
                        'xe_id', 'section_id', 'name', 'side_path_obj',
                        'preview_file', 'svg_file'
                    )
                        ->where(['section_id' => $parentConfData[0]['xe_id']])
                        ->get();
                    foreach ($getConfSides->toArray() as $value) {
                        $configuratorSideData = [
                            'section_id' => $saveConfigurator->xe_id,
                            'name' => $value['name'],
                            'side_path_obj' => $value['side_path_obj'],
                            'preview_file' =>  $value['preview_file_original'],
                            'svg_file' => $value['svg_file_original'],
                        ];
                        $saveConfiguratorSides = new ProductConfiguratorSides($configuratorSideData);
                        if ($saveConfiguratorSides->save()) {
                            $responseStatus = true;
                        }
                    }
                }
            }
        }

        return $responseStatus;
    }
}
