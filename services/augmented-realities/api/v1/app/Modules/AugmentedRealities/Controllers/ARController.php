<?php
/**
 * Augmented Reality
 *
 * PHP version 5.6
 *
 * @category  Augmented_Reality
 * @package   Add-on
 * @author    Tanmaya <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\AugmentedRealities\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\AugmentedRealities\Models\AugmentedReality as ARM;

/**
 * Augmented Reality Controller
 *
 * @category Class
 * @package  Add-on
 * @author   Tanmaya <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ARController extends ParentController
{

    /**
     * Save the custom pattern file to the server
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author tanmayap@riaxe.com
     * @date   04 Apr 2020
     * @return json
     */
    public function savePattern($request, $response)
    {
        $serverStatusCode = OPERATION_OKAY;
        $directoryWrite = path('abs', 'augmented');
        $pattern = 'predefined/default_ar_pattern.patt';
        $uploadedPattern = do_upload('pattern', $directoryWrite, [], 'string');
        if (!empty($uploadedPattern)) {
            $pattern = $uploadedPattern;
        }

        return $pattern;
    }
    /**
     * Save the Augmented Reality Files and generate HTML file
     *
     * @param $request  Slim's Request object
     * @param $response Slim's Response object
     * @param $args     Slim's Arguments
     *
     * @author tanmayap@riaxe.com
     * @date   29 mar 2020
     * @return json
     */
    public function saveAugmentData($request, $response, $args)
    {
        $serverStatusCode = OPERATION_OKAY;
        $jsonResponse = [
            'status' => 0,
            'data' => [],
            'message' => message('Augmented data', 'error')
        ];
        $allPostPutVars = $request->getParsedBody();
        $augmentedData = [];
        $allowedFormats = [
            'jpeg' => 'image',
            'jpg' => 'image',
            'png' => 'image',
            'gif' => 'animated',
            'mp4' => 'video',
            'mpg' => 'video',
            'wmv' => 'video',
            'avi' => 'video',
            'gltf' => 'object',
        ];
        $img_height = 5;
        $anime_height = 2;
        $img_width = 5;
        $anime_width = 2;
        if (!empty($allPostPutVars['height'])) {
            $img_height = $allPostPutVars['height'];
            $anime_height = $allPostPutVars['height'];
        }
        if (!empty($allPostPutVars['width'])) {
            $img_width = $allPostPutVars['width'];
            $anime_width = $allPostPutVars['width'];
        }

        // Dynamic source for main content
        $objectHtml = '<!-- 3D Object files -->
            <a-asset-item id="animated-asset" src="[SOURCE]">' . PHP_EOL;
        $animatedHtml = '<!-- GIF Files -->
            <img crossorigin="anonymous" id="asset" src="[SOURCE]">' . PHP_EOL;
        $imageHtml = '<!-- Image Files -->
            <img crossorigin="anonymous" id="img" src="[SOURCE]">' . PHP_EOL;
        $videoHtml = '<!-- Video Files -->
            <video id="vid" crossorigin="anonymous" autoplay loop="true" type="video/mp4" preload="auto" src="[SOURCE]"></video>' . PHP_EOL;

        // Pattern section dynamic html codes
        $objectPatternHtml = '<!-- 3D Object files -->
            <a-entity animation-mixer gltf-model="#animated-asset" scale="2 2 2"></a-entity>' . PHP_EOL;
        $animatedPatternHtml = '<!-- GIF Files -->
            <a-entity geometry="primitive:plane;width:' . $anime_width . ';height:' . $anime_height . ';" position="0 0 0" scale="4 4 4" rotation="-90 0 0" material="shader:gif;src:#asset;alphaTest:0.5;"></a-entity>' . PHP_EOL;
        $imagePatternHtml = '<!-- Image Files -->
            <a-image width="' . $img_width . '" height="' . $img_height . '" position="0 0 0" rotation="-90 0 0" material="transparent:true;shader:flat;side:double;src:#img"></a-image>' . PHP_EOL;
        $videoPatternHtml = '<!-- Video Files -->
            <a-entity position="0 0 -0.6"><a-video width="6" height="5" rotation="-90 0 0" material="transparent:true;shader:flat;side:double;src:#vid"></a-video></a-entity>' . PHP_EOL;
        
        // Barcode Section dynamic html codes
        $objectBarcodeHtml = '<!-- 3D Object files -->
            <a-entity animation-mixer gltf-model="#animated-asset" scale="2 2 2"></a-entity>' . PHP_EOL;
        $animatedBarcodeHtml = '<!-- GIF Files -->
            <a-entity geometry="primitive:plane;width:' . $anime_width . ';height:' . $anime_height . ';" position="0 0 0" scale="4 4 4" rotation="-90 0 0" material="shader:gif;src:#asset;alphaTest:0.5;"></a-entity>' . PHP_EOL;
        $imageBarcodeHtml = '<!-- Image Files -->
            <a-image width="' . $img_width . '" height="' . $img_height . '" position="0 0 0" rotation="-90 0 0" material="transparent:true;shader:flat;side:double;src:#img"></a-image>' . PHP_EOL;
        $videoBarcodeHtml = '<!-- Video Files -->
            <a-entity position="0 0 -0.6"><a-video width="6" height="5" rotation="-90 0 0"   material="transparent:true;shader:flat;side:double;src:#vid"></a-video></a-entity>' . PHP_EOL;
            
        // Define some required paths
        $saveAugPath = path('abs', 'augmented');
        $fetchAugPath = path('read', 'augmented');
        $htmlName = getRandom() . '.html';
        $saveHtmlFilePath = $saveAugPath . $htmlName;
        $getHtmlFilePath = $fetchAugPath . $htmlName;

        // Get the master HTML skeleton
        $predefinedDir = path('abs', 'augmented') . 'predefined/';
        $masterHtmlFile = file_get_contents($predefinedDir. 'augmented-reality-master.html');

        // Upload the file and generate the html
        $uploadedFiles = do_upload('upload', $saveAugPath, [150], 'string');
        if (!empty($uploadedFiles)) {
            $generateCore = "";
            $selPatternHtml = "";
            $selectedType = null;
            $uploadedFile = $uploadedFiles;
            $sourceFile = $fetchAugPath . $uploadedFiles;
            $augmentedData['files'][] = $fetchAugPath . $uploadedFiles;
            $extension = pathinfo($sourceFile, PATHINFO_EXTENSION);
            $extension = strtolower($extension);

            if (in_array($extension, array_keys($allowedFormats))) {
                if (!empty($allowedFormats[$extension])) {
                    $selectedType = $allowedFormats[$extension];
                    $sourceByExt = ${$selectedType . 'Html'};
                    $generateCore .= str_replace("[SOURCE]", $sourceFile, $sourceByExt);
                    
                    $selPatternHtml = ${$selectedType . 'PatternHtml'};
                    $selBarcodeHtml = ${$selectedType . 'BarcodeHtml'};
                }

                $finalHtmlCodes = str_replace("[DYNAMIC_SOURCE]", $generateCore, $masterHtmlFile);
                $finalHtmlCodes = str_replace("[PATTERN_SOURCES]", $selPatternHtml, $finalHtmlCodes);
                $finalHtmlCodes = str_replace("[BARCODE_SOURCES]", $selBarcodeHtml, $finalHtmlCodes);
                
                // Selecting right pattern file 
                $getPattern = $this->savePattern($request, $response);
                $patternUrl = $fetchAugPath . $getPattern;
         
                if (!empty(getPattern)) {
                    $finalHtmlCodes = str_replace("[PATTERN_URL]", $patternUrl, $finalHtmlCodes);
                }
                
                if (write_file($saveHtmlFilePath, $finalHtmlCodes)) {
                    // Save in database
                    $data = [
                        'file' => $uploadedFile,
                        'pattern_file' => $getPattern,
                        'html_file' => $htmlName
                    ];
                    $augmented = new ARM($data);
                    if ($augmented->save()) {
                        $augmentedId = $augmented->xe_id;
                        $augmentedData['last_insert_id'] = $augmentedId;
                        $augmentedData['html'] = $getHtmlFilePath;
                        $jsonResponse = [
                            'status' => 1,
                            'data' => $augmentedData,
                            'message' => message('Augmented Data', 'saved')
                        ];
                    }
                }
            }
        }

        return response(
            $response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
        );
    }
}
