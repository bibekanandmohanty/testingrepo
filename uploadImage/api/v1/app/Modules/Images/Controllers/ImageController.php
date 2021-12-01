<?php
/**
 * Manage User Image Upload Files
 *
 * PHP version 5.6
 *
 * @category  User_Image
 * @package   Eloquent
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Images\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Modules\Images\Models\BrowserImages;
use App\Modules\Images\Models\UserImage;

/**
 * Upload Image Controller
 *
 * @category Class
 * @package  Upload_Image
 * @author   Mukesh <mukeshp@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ImageController extends ParentController {

	/**
	 * POST: Save Word Cloud
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author mukeshp@riaxe.com
	 * @date   7th Feb 2020
	 * @return json response wheather data is saved or any error occured
	 */
	public function saveUserImages($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Upload Image', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$customerId = $allPostPutVars['customer_id'];
		$ext = strtolower($allPostPutVars['file_type']);
		$storeId = $allPostPutVars['store_id'] ? $allPostPutVars['store_id'] : 1;
		$source = $allPostPutVars['source'] ? $allPostPutVars['source'] : '';
		$originalFilePath = $originalFileUrl = '';
		if ($ext != 'jpg' && $ext != 'svg' && $ext != 'png' && $ext != 'jpeg') {
			$uploadUserFile = do_upload('userfile', path('abs', 'upload'), [], 'string');
			$originalFileUrl = path('read', 'upload') . $uploadUserFile;
			$new_file_name = (explode(".", $uploadUserFile));
			$outputFile = $new_file_name[0] . '.svg';
			$thumbnailOutput = '';
			$originalFilePath = path('abs', 'upload').$uploadUserFile;
			$getUploadedFileName = $this->convertImageFormat($uploadUserFile, 'svg');
		} else {
			$getUploadedFileName = do_upload('userfile', path('abs', 'user'), [100], 'string');
			$thumbnailOutput = "thumb_" . $getUploadedFileName;
			$originalFileUrl = path('read', 'user') . $getUploadedFileName;
			$outputFile = $getUploadedFileName;
		}
		if (!empty($getUploadedFileName)) {
			$saveUserImageData = [
				'customer_id' => $customerId,
				'file_name' => $outputFile,
				'original_file_name' => $getUploadedFileName,
			];
			$saveUserImage = new userImage($saveUserImageData);
			if ($saveUserImage->save()) {
				$jsonResponse = [
					'status' => 1,
					'message' => message('User Image', 'saved'),
					'data' => [
						'file_name' => $getUploadedFileName,
						'original_file_url' => $originalFileUrl,
						'thumb' => "thumb_" . $getUploadedFileName,
						'url' => path('read', 'user') . $getUploadedFileName,
					],
				];
			}
		}
		if ($storeId > 1 && empty($source)) {
			$storeResponse = $this->getStoreDomainName($storeId);
			if (!empty($storeResponse)) {
				$hostname = parse_url($jsonResponse['data']['url'], PHP_URL_HOST); //hostname
				$jsonResponse['data']['url'] = str_replace($hostname, $storeResponse['store_url'], $jsonResponse['data']['url']);
			}

		}
		$isS3Enabled = $this->checkS3Settings($storeId);
		if ($isS3Enabled) {
			$fileToUpload = path('abs', 'user') . $getUploadedFileName;
			$s3Upload = $this->uploadFileToS3("user", $fileToUpload, $storeId);
			if (!empty($originalFilePath)) {
				$s3UploadOrgFile = $this->uploadFileToS3("user", $originalFilePath, $storeId);
				$jsonResponse['data']['original_file_url'] = $s3UploadOrgFile['S3URL'];
			}
			if ($s3Upload['error'] == 0) {
				$jsonResponse['data']['url'] = $s3Upload['S3URL'];
			}
		}

		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * GET: List of User Images
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author mukeshp@riaxe.com
	 * @date   7th Feb 2020
	 * @return All/Single User Images List
	 */
	public function getUserImages($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$wordCloudData = [];
		$jsonResponse = [
			'status' => 0,
			'data' => [],
			'message' => message('User Images', 'not_found'),
		];

		$UserImageInit = new UserImage();
		$getUserImages = $UserImageInit->where(
			'customer_id', '=', $args['id']
		);

		if ($getUserImages->count() > 0) {
			$userImages = $getUserImages->get()
				->toArray();
			$jsonResponse = [
				'status' => 1,
				'records' => count($userImages),
				'data' => $userImages,
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * GET: Convert Image From One format to another
	 *
	 * @param $file_name   Slim's Request object
	 * @param $file_format Slim's Response object
	 *
	 * @author chandrakanta@riaxe.com
	 * @date   18th Mar 2020
	 * @return All/Single User Images List
	 */
	protected function convertImageFormat($file_name, $file_format) {
		$new_file_name = (explode(".", $file_name));
		$outputFile = $new_file_name[0] . '.svg';
		if ($new_file_name[1] == 'ai' || $new_file_name[1] == 'eps' || $new_file_name[1] == 'pdf' || $new_file_name[1] == 'cdr') {
			shell_exec("PATH=/usr/bin inkscape -z -f" . ASSETS_PATH_W . 'user/uploads/' . $file_name . "  --export-plain-svg=" . ASSETS_PATH_W . 'user/' . $outputFile);
		} else if ($new_file_name[1] == 'gif' || $new_file_name[1] == 'psd') {
			$outputFile = $new_file_name[0] . '.png';
			shell_exec("convert " . ASSETS_PATH_W . 'user/uploads/' . $file_name . " " . $outputFile . " && mv " . $new_file_name[0] . "-0.png " . ASSETS_PATH_W . 'user/' . $outputFile . "  && rm -rf " . $new_file_name[0] . "-*.png");
		} else if ($new_file_name[1] == 'bmp' || $new_file_name[1] == 'tif') {
			$outputFile = $new_file_name[0] . '.png';
			shell_exec("convert " . ASSETS_PATH_W . 'user/uploads/' . $file_name . " " . $outputFile . " && mv " . $outputFile . " " . ASSETS_PATH_W . 'user/' . $outputFile);
		} else {
			shell_exec("convert " . ASSETS_PATH_W . 'user/uploads/' . $file_name . " " . ASSETS_PATH_W . 'user/uploads/' . $new_file_name[0] . ".pnm  && convert " . ASSETS_PATH_W . 'user/uploads/' . $new_file_name[0] . ".pnm " . ASSETS_PATH_W . 'user/' . $outputFile);
		}
		return $outputFile;
	}
	/**
	 * GET: Filter Image Whites
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author chandrakanta@riaxe.com
	 * @date   18th Mar 2020
	 * @return All/Single User Images List
	 */
	public function filterImage($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Upload Image', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$ext = $allPostPutVars['file_type'];
		$type = $allPostPutVars['type'];
		if ($ext == 'jpg' || $ext == 'png' || $ext == 'jpeg') {
			$getUploadedFileName = do_upload('userfile', path('abs', 'upload'), [], 'string');
			$new_file_name = (explode(".", $getUploadedFileName));
			$outputFile = $new_file_name[0] . '.png';
			// shell_exec("convert " . ASSETS_PATH_W . 'user/uploads/' . $getUploadedFileName . " -alpha set -bordercolor white -border 1 -fill none -fuzz 10% -draw 'color 0,0 floodfill' -shave 1x1 " . ASSETS_PATH_W . 'user/' . $outputFile);
			$src = ASSETS_PATH_W . 'user/uploads/' . $getUploadedFileName;
			$dest = ASSETS_PATH_W . 'user/' . $outputFile;

			$this->convertImage($src, $dest, $type);
			$jsonResponse = [
				'status' => 1,
				'message' => message('User Image', 'saved'),
				'data' => [
					'file_name' => $outputFile,
					'url' => path('read', 'user') . $outputFile,
				],
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Post: Save Browser Images
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4 May 2019
	 * @return A JSON Response
	 */
	/**
	 * Post: Save Browser Images
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4 May 2019
	 * @return A JSON Response
	 */
	public function saveBrowserImages($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 1,
			'message' => message('Browser Image', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$storeId = $allPostPutVars['store_id'] ? $allPostPutVars['store_id'] : 1;
		$source = $allPostPutVars['source'] ? $allPostPutVars['source'] : '';
		if ($allPostPutVars['browser_id'] != "") {
			$uploadedFiles = $request->getUploadedFiles();
			$fileName = null;
			if (!empty($uploadedFiles['upload'])) {
				$fileName = do_upload_aspect(
					'upload', path('abs', 'browser_image'), [200], 'string'
				);
			}
			$ImgData = [
				'browser_id' => $allPostPutVars['browser_id'],
				'file_name' => $fileName,
			];
			$saveObjData = new BrowserImages($ImgData);
			if ($saveObjData->save()) {
				$jsonResponse = [
					'status' => 1,
					'message' => message('Browser Image', 'saved'),
					'image_url' => path('read', 'browser_image') . $fileName,
				];
				if ($storeId > 1 && empty($source)) {
					$storeResponse = $this->getStoreDomainName($storeId);
					if (!empty($storeResponse)) {
						$hostname = parse_url($jsonResponse['image_url'], PHP_URL_HOST); //hostname
						$jsonResponse['image_url'] = str_replace($hostname, $storeResponse['store_url'], $jsonResponse['image_url']);
					}

				}
			}
		}
		$isS3Enabled = $this->checkS3Settings($storeId);
		if ($isS3Enabled) {
			$fileToUpload = path('abs', 'browser_image') . $fileName;
			$thumbFile = path('abs', 'browser_image') . "thumb_".$fileName;
			$s3Upload = $this->uploadFileToS3("browser_image", $fileToUpload, $storeId);
			if (file_exists($thumbFile)) {
				$s3Upload1 = $this->uploadFileToS3("browser_image", $thumbFile, $storeId);
			}
			if ($s3Upload['error'] == 0) {
				$jsonResponse['image_url'] = $s3Upload['S3URL'];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}

	/**
	 * Get: Get Browser Images
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author satyabratap@riaxe.com
	 * @date   4 May 2019
	 * @return A JSON Response
	 */
	public function getBrowserImages($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 1,
			'data' => [],
			'message' => message('Browser Image', 'not_found'),
		];
		$storeId = $request->getQueryParam('store_id') ? $request->getQueryParam('store_id') : 1;
		$source = $request->getQueryParam('source') ? $request->getQueryParam('source') : '';
		$isS3Enabled = $this->checkS3Settings($storeId);
		if (!empty($args['id'])) {
			$browserImgId = $args['id'];
			$imgInit = new BrowserImages();
			$imgData = $imgInit->where('browser_id', $browserImgId)->get();

			if (!empty($imgData)) {
				foreach ($imgData as $imgKey => $images) {
					$fileName = '';
					$thumbnail = '';
					if ($storeId > 1 && empty($source)) {
						$storeResponse = $this->getStoreDomainName($storeId);
						$hostName = parse_url($images['file_name'], PHP_URL_HOST); //hostname
						$fileName = str_replace($hostName, $storeResponse['store_url'], $images['file_name']);
						$thumbnail = str_replace($hostName, $storeResponse['store_url'], $images['thumbnail']);
					}
					if ($isS3Enabled) {
						$thisFileName = $fileName ? $fileName : $images['file_name'];
						$thisThumbnail = $thumbnail ? $thumbnail : $images['thumbnail'];
						$data[$imgKey] = [
							'browser_id' => $images['browser_id'],
							'file_name' => $this->getS3URL($thisFileName, $storeId),
							'thumbnail' => $this->getS3URL($thisThumbnail, $storeId),
						];
					} else {
						$data[$imgKey] = [
							'browser_id' => $images['browser_id'],
							'file_name' => $fileName ? $fileName : $images['file_name'],
							'thumbnail' => $thumbnail ? $thumbnail : $images['thumbnail'],
						];
					}

				}
				$jsonResponse = [
					'status' => 1,
					'data' => $data,
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 * @param $source  String
	 * @param $destination  String
	 * @param $action  String
	 * @author chandrakanta@riaxe.com
	 * @date   1 Oct 2020
	 * @return Array
	 */
	public function convertImage($source, $destination, $action) {
		switch ($action) {
		case 'removed_white':
			$command = 'convert ' . $source . ' -fuzz 10% -transparent White ' . $destination . '';
			break;
		case 'removed_edges':
			$command = 'convert ' . $source . ' -alpha set -bordercolor white -border 1 -fill none -fuzz 3% -draw "color 0,0 floodfill" -shave 1x1 ' . $destination . '';
			break;
		case 'fill_outline':
			$command = 'convert \( ' . $source . ' -trim +repage -bordercolor white -border 50 -fuzz 5% -fill none -draw "matte 0,0 floodfill" -alpha off -write mpr:img -alpha on -alpha extract -morphology dilate disk:20 -blur 0x1 -level 0x50% -write mpr:msk1 +delete \) \( mpr:msk1 -negate -fill white -opaque black -blur 0x10 -fill white -opaque white -write mpr:msk2 +delete \) \( mpr:msk1 -morphology edgein diamond:1 -negate -write mpr:edg +delete  \) mpr:img mpr:msk1 -alpha off -compose copy_opacity -composite mpr:msk2 -reverse -compose over -composite mpr:edg -compose multiply -composite -trim -alpha set -bordercolor white -border 1 -fill none -fuzz 3% -draw "color 0,0 floodfill" -shave 1x1 ' . $destination . '';
			break;
		default:
			die('please select valid options');
			break;
		}
		shell_exec($command);
		return;
	}
	/**
	 * @author Tapas
	 * @date   1 Feb 2021
	 * @return Array
	 */
	public function deleteBrowserImages($request, $response) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Browser Image', 'error'),
		];
		$allPostPutVars = $request->getParsedBody();
		$dir = path('abs', 'browser_image');
		if (isset($allPostPutVars['file_name']) && $allPostPutVars['file_name'] != "") {
			$ImgData = [
				'file_name' => $allPostPutVars['file_name'],
			];
			$deleteObjData = new BrowserImages($ImgData);
			if ($deleteObjData->where('file_name', $allPostPutVars['file_name'])->delete()) {
				$image_location = $dir . "/" . $allPostPutVars['file_name'];
				$image_location_thumb = $dir . "/thumb_" . $allPostPutVars['file_name'];
				delete_file($image_location);
				delete_file($image_location_thumb);
				$jsonResponse = [
					'status' => 1,
					'message' => message('Browser Image', 'deleted'),
				];
			}
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
}
