<?php

/**
 * Download order details on various endpoints
 *
 * PHP version 5.6
 *
 * @category  Download_Order
 * @package   DownloadOrderArtwork
 * @author    Soumya Swain <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Orders\Controllers;

use App\Components\Controllers\Component as ParentController;
use App\Dependencies\Zipper as Zipper;
use App\Modules\Orders\Models\OrderItemToken;

/**
 * Order Download Controller
 *
 * @category Class
 * @package  DownloadOrderArtwork
 * @author   Soumya Swain <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class DownloadArtworkController extends ParentController {
	/**
	 * Integer 96 dpi is default dpi for illustrator
	 */
	public $dpi = 96;

	/**
	 * String The path to the current order zip file
	 */
	public $orderPath;

	/**
	 * String The path to the current order zip file
	 */
	public $sidePath;

	/**
	 * String The path to the current svg save file
	 */
	public $svgSavePath;

	/**
	 * Array empty print color array
	 */
	public $printColorsArr = array();

	/**
	 * String svg image tag
	 */
	public $productImageTag;

	/**
	 * String print unit for current order
	 */
	public $printUnit;

	/**
	 * Html dom object
	 */
	public $domObj;

	/**
	 * Initial filter variable
	 */
	public $filter;

	/**
	 * Array SVG file format enabled print method
	 * Remove latter
	 */
	public $svgFileArr = array();

	/**
	 * Define order path
	 **/
	public function __construct() {
		$this->createOrderArtworkDirectory(path('abs', 'assets') . 'orders_artwork');
		$this->orderPath = path('abs', 'orders_artwork');
		$this->domHtmlPathInclue();
	}
	/**
	 * create order artwork directory
	 **/
	public function createOrderArtworkDirectory($dir) {
		if (!is_dir($dir)) {
			mkdir($dir, 0755, true);
		}
	}
	/**
	 * GET : Download vendor artwork
	 *
	 * @param $request  Slim's Request object
	 * @param $response Slim's Response object
	 * @param $args     Slim's Argument parameters
	 *
	 * @author soumyas@riaxe.com
	 * @date   04 June 2020
	 * @return json response wheather data is updated or not
	 */
	public function downloadOrderArtwork($request, $response, $args) {
		$serverStatusCode = OPERATION_OKAY;
		$jsonResponse = [
			'status' => 0,
			'message' => message('Download artwork ', 'error'),
		];
		$status = false;
		$isDownload = true;
		if (isset($args) && !empty($args)) {
			$orderSrcPath = path('abs', 'order');
			$token = $args['token'];
			$tokenData = $this->explodeToken($token);
			$orderId = $tokenData['orderId'];
			$orderItemId = $tokenData['orderItemId'];
			$orderItemTokenInit = new OrderItemToken();
			$tokenReturn = $orderItemTokenInit->select('token')
				->where('order_id', $orderId)
				->where('order_item_id', $orderItemId)
				->get()->toArray();
			if (!empty($tokenReturn[0])) {
				if (!is_dir($this->orderPath . $orderId)) {
					mkdir($this->orderPath . $orderId, 0755, true);
				}
				$orderPathOriginal = $orderSrcPath . $orderId . '/';
				$orderDestPath = $this->orderPath . $orderId;
				/* copy files */
				$this->recurse_copy($orderPathOriginal, $orderDestPath);

				$downloadZipPath = $this->orderPath . "order_" . $orderId . "_item_" . $orderItemId . ".zip";
				$status = $this->downloadOrderByItemId($orderId, $orderItemId);
				if ($status) {
					$returnStatus = $this->createOrderZipFileByItemId($orderId, $orderItemId);
					if ($returnStatus) {
						$status = true;
						$this->deleteOrderToken($orderId, $orderItemId);

						$this->zipFileDownload($downloadZipPath);

						$this->deleteDirectory($orderDestPath);
					}
				}
				$msg = $status ? 'done' : 'error';
				$jsonResponse = [
					'status' => $status,
					'message' => message('order download', $msg),
				];
			} else {
				$jsonResponse = [
					'status' => 0,
					'message' => 'Invalid token',
				];
			}
		} else {
			$jsonResponse = [
				'status' => 0,
				'message' => 'token empty',
			];
		}
		return response(
			$response, ['data' => $jsonResponse, 'status' => $serverStatusCode]
		);
	}
	/**
	 *
	 * Explode the Token and return in array
	 *
	 * @author soumyas@riaxe.com
	 * @date   4 June 2020
	 * @return array
	 *
	 */
	public function explodeToken($token) {
		$result = array();
		$token = base64_decode($token);
		$values = explode("&", $token);
		foreach ($values as $key => $value) {
			$resArray = explode("=", $value);
			$result[$resArray[0]] = $resArray[1];
		}
		return $result;
	}
	/**
	 * GET: Download order file
	 *
	 * @param $orderId The is current request order Id
	 * @param $itemId  The is current request item Id
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return boolean
	 */
	private function downloadOrderByItemId($orderId, $itemId) {
		$status = false;
		$orderIdPath = $this->orderPath . $orderId;

		if (file_exists($orderIdPath) && is_dir($orderIdPath . "/" . $itemId)) {
			$designStateAbsDir = $orderIdPath . "/" . $itemId . "/designState.json";
			$checkUploadFileDir = $orderIdPath . "/" . $itemId;
			if (file_exists($designStateAbsDir)) {
				$designStateStr = read_file($designStateAbsDir);
				$designData = json_clean_decode($designStateStr, true);
				$sidePath = $orderIdPath . "/" . $itemId;
				$scanSideDir = scandir($sidePath);
				$sidePath = $orderIdPath . "/" . $itemId;
				if (file_exists($sidePath) && is_dir($sidePath)) {
					$scanSideDir = scandir($sidePath);
					if (is_valid_array($scanSideDir)) {
						foreach ($scanSideDir as $dir) {
							$absPath = $orderIdPath . "/" . $itemId . "/" . $dir;
							if ($dir != '.' && $dir != '..' && is_dir($absPath)) {
								$i = str_replace("side_", "", $dir);
								$sideDir = $orderIdPath . "/" . $itemId . "/" . $dir;
								$svgPath = $sideDir . "/preview_0" . $i . ".svg";
								$file = $dir . "_" . $itemId . "_" . $orderId;
								$svgPathChk = $sideDir . "/" . $file . ".svg";
								if (file_exists($svgPath)
									&& !file_exists($svgPathChk)
								) {
									$status = $this->generateSvgFile(
										$svgPath, $orderId,
										$itemId, $designData
									);
								} else {
									$status = true;
								}
							}
						}
					}
				}
			} else if (file_exists($checkUploadFileDir)) {
                $status = true;
            }
		}
		return $status;
	}

	/**
	 * GET: Create order zip file
	 *
	 * @param $orderNo     The is current request order Id
	 * @param $orderItemId The is current request item Id
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return boolean
	 */
	private function createOrderZipFileByItemId($orderNo, $orderItemId) {

		$fileExtention = 'svg,png,pdf';
		$status = false;
		$zipName = 'order_' . $orderNo . '_item_' . $orderItemId . '.zip';
		if (file_exists($this->orderPath . '/' . $zipName)) {
			unlink($this->orderPath . '/' . $zipName);
		}
		$assetFileExt = 'svg,pdf,png,jpeg,jpg,gif,bmp,ai,psd,eps,cdr,dxf,tif';
		$assetFileExt .= ',' . strtoupper($assetFileExt);
		$zip = new Zipper();
		$zipStatus = $zip->make($this->orderPath . '/' . $zipName);

		if ($zipStatus) {
			$orderFolderPath = $this->orderPath . $orderNo;
			$orderFolderPath = $this->orderPath . $orderNo;
			$orderJsonPath = $orderFolderPath . '/order.json';
			//echo $orderJsonPath;exit;
			if (file_exists($orderFolderPath)) {
				if (file_exists($orderJsonPath)) {
					$orderJson = read_file($orderJsonPath);
					$jsonContent = json_clean_decode($orderJson, true);
					$itemList = $jsonContent['order_details']['order_items'];
					$noOfRefIds = count($itemList);
					if ($noOfRefIds > 0) {
						$zip->addEmptyDir($orderNo);
						//$zip->add($orderJsonPath, $orderNo . '/order.json');
						foreach ($itemList as $itemDetails) {
							$itemId = (int) $itemDetails['item_id'];
							$refId = (int) $itemDetails['ref_id'];
							$itemPath = $orderFolderPath . "/" . $itemId;
							$ordeItemSide = $orderNo . "/" . $itemId . "/side_";
							$designPath = $itemPath . "/designState.json";
							if (($itemId > 0 && $refId > 0 && $refId != '-1')
								&& $itemDetails['item_id'] == $orderItemId
							) {
								//Fetch the design state json details //
								$designState = read_file($designPath);
								$designData = json_clean_decode($designState, true);
								if (is_array($designData['sides'])) {
									$sidesCount = count($designData['sides']);

									for ($flag = 1; $flag <= $sidesCount; $flag++) {
										if (is_dir($itemPath . "/side_" . $flag)) {
											$zip->addEmptyDir($ordeItemSide . $flag);
										}
										$sidePath = $ordeItemSide . $flag;

										//Add name and number csv file in zip
										$nameNumPath = $itemPath . "/nameNumber.csv";
										$addItemPath = $orderNo . "/" . $itemId;
										if (file_exists($nameNumPath)) {
											$optionsPath = array(
												'add_path' => $addItemPath . "/",
												'remove_path' => $itemPath,
											);
											$zip->addGlob(
												$itemPath . '/*{csv}',
												$optionsPath
											);
										}

										//Add side folder to zip file //
										$fromUrlSide = $itemPath . "/side_" . $flag;
										$optionsSide = array(
											'add_path' => $sidePath . "/",
											'remove_path' => $fromUrlSide,
										);
										$zip->addGlob(
											$fromUrlSide . '/*{' . $fileExtention . '}',
											$optionsSide
										);

										//Add asset folder to zip file//
										if (is_dir($fromUrlSide . "/assets")) {
											$zip->addEmptyDir(
												$sidePath . "/original_image"
											);
											$urlAsset = $fromUrlSide . "/assets";
											$addPath = $sidePath . "/original_image/";
											$optionsAsset = array(
												'add_path' => $addPath,
												'remove_path' => $urlAsset,
											);
											$zip->addGlob(
												$urlAsset . '/*{' . $assetFileExt . '}',
												$optionsAsset
											);
										}

										//Add preview folder to zip file//
										if (is_dir($fromUrlSide . "/preview")) {
											$zip->addEmptyDir($sidePath . "/preview");
											$fromUrlPrvw = $fromUrlSide . "/preview";
											$optionsPreview = array(
												'add_path' => $sidePath . "/preview/",
												'remove_path' => $fromUrlPrvw,
											);
											$zip->addGlob(
												$fromUrlPrvw . '/*{png,PNG}',
												$optionsPreview
											);
										}

										//remove svg preview file from zip file
										$zip->removeFile(
											$sidePath . "/preview_0" . $flag . ".svg"
										);

										//remove svg file as per print profile
										if (!empty($this->svgFileArr)) {
											foreach ($this->svgFileArr as $svgFile) {
												$svgRemovePath = $sidePath . '/' . $svgFile;
												$zip->removeFile($svgRemovePath);
											}
										}
									}
								}
							} else if (($itemId > 0 && $refId == '-1') 
								&& $itemDetails['item_id'] == $orderItemId
							) {
                                $scanItemDir = scandir($itemPath);
                                if (is_valid_array($scanItemDir)) {
                                    foreach ($scanItemDir as $itemsDir) {
                                        if ($itemsDir != '.' && $itemsDir != '..') {
                                            $scanSideDir = scandir($itemPath . '/' . $itemsDir);
                                            if (is_valid_array($scanSideDir)) {
                                                foreach ($scanSideDir as $sideDir) {
                                                    if ($sideDir != '.' && $sideDir != '..' && $sideDir != 'preview') {
                                                        $zip->add($itemPath . '/' . $itemsDir . '/' . $sideDir, $orderNo . '/' . $itemId . '/' . $itemsDir . '/' . $sideDir);
                                                    } else if ($sideDir == 'preview') {
                                                        $scanItemPreviewDir = scandir($itemPath . '/' . $itemsDir . '/' . $sideDir);
                                                        if (is_valid_array($scanItemPreviewDir)) {
                                                            foreach ($scanItemPreviewDir as $itemPreviewDir) {
                                                                if ($itemPreviewDir != '.' && $itemPreviewDir != '..') {
                                                                    $zip->add($itemPath . '/' . $itemsDir . '/' . $sideDir . '/' . $itemPreviewDir, $orderNo . '/' . $itemId . '/' . $itemsDir . '/' . $sideDir . '/' . $itemPreviewDir);
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
						}
					}
				}
				$status = true;
			}
			$zip->close();
		}
		return $status;
	}

	/**
	 * GET: Create svg file according to print area dimension
	 *
	 * @param $reqSvgFile   The is current request SVG string
	 * @param $orderId      The is current request order ID
	 * @param $itemId       The is current request Item ID
	 * @param $resultDesign The is current request design data
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return boolean
	 */
	private function generateSvgFile($reqSvgFile, $orderId, $itemId, $resultDesign) {
		$svgStatus = false;
		$status = false;
		//Filter tag for invert color
		$filterS = '<filter xmlns="http://www.w3.org/2000/svg" id="invertcolor" ';
		$filterM = 'color-interpolation-filters="sRGB" x="0" y="0" height="100%" ';
		$filterE = 'width="100%">';
		$feColorMatrixS = '<feColorMatrix in="SourceGraphic" type="matrix" ';
		$feColorMatrixE = 'values="-1 0 0 0
            1 0 -1 0 0
            1 0 0 -1 0
            1 0 0 0 1 0"/></filter>';
		$this->filter = $filterS . $filterM . $filterE . $feColorMatrixS . $feColorMatrixE;

		if ($reqSvgFile != '') {
			$svgFileStr = read_file($reqSvgFile);
			$oldReplaceStr = array(
				'data: png',
				'data: jpg',
				'data: jpeg',
				'data:image/jpg',
			);
			$newReplaceStr = array(
				'data:image/png',
				'data:image/jpeg',
				'data:image/jpeg',
				'data:image/jpeg',
			);
			$svgFileStr = str_replace($oldReplaceStr, $newReplaceStr, $svgFileStr);
			$svgFileExtention = basename($reqSvgFile);
			$sideNo = str_replace(
				"preview_0", "",
				str_replace(".svg", "", $svgFileExtention)
			);

			$this->sidePath = "side_" . $sideNo . "_" . $itemId . "_" . $orderId;
			$svgFileName = $this->sidePath . ".svg";
			$pngFileName = $this->sidePath . ".png";
			$rgbPdfFileName = $this->sidePath . "_rgb.pdf";
			$cmykPdfFileName = $this->sidePath . ".pdf";
			$multiPrintFileName = $this->sidePath . ".svg";
			$itemPath = $orderId . '/' . $itemId . '/side_' . $sideNo;
			$this->svgSavePath = $this->orderPath . $itemPath . '/';
			$svgAbsPath = $this->svgSavePath . $svgFileName;
			$pngAbsPath = $this->svgSavePath . $pngFileName;
			$rgbPdfAbsPath = $this->svgSavePath . $rgbPdfFileName;
			$cmykPdfAbsPath = $this->svgSavePath . $cmykPdfFileName;
			$sideIdIndex = $sideNo - 1;

			$sidePrintSvg = $resultDesign['sides'][$sideIdIndex];
			$this->printUnit = $resultDesign['sides'][$sideIdIndex]['print_unit']
			? $resultDesign['sides'][$sideIdIndex]['print_unit'] : 'inch';
			$stickerInfo = $resultDesign['sides'][$sideIdIndex]['stickerInfo']
            ? $resultDesign['sides'][$sideIdIndex]['stickerInfo'] : array();
			$htmlStr = new \simple_html_dom();
			$htmlStr->load($svgFileStr, false);
			$svg = $htmlStr->find('image#svg_1', 0);
			$countLayer = substr_count($htmlStr, 'Layer_');
			$multiPrintStatus = false;
			if ($svg) {
				if (!file_exists($this->svgSavePath)) {
					mkdir($this->svgSavePath, 0777, true);
					chmod($this->svgSavePath, 0777);
				}
				if ($countLayer >= 2) {
					$htmlStr->save();

					//For multiple svg files for multiple boundary
					$svgStatus = $this->generateMultipleSvgFile(
						$htmlStr, $multiPrintFileName,$stickerInfo,
						$sidePrintSvg
					);
					$multiPrintStatus = true;
				}
				$status = $svgStatus;
			}
		}
		return $status;
	}

	/**
	 * GET: To create separate svg file for every print area
	 *
	 * @param $reqStr             The is current request SVG string
	 * @param $multiPrintFileName The is current request SVG file name
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return boolean
	 */
	private function generateMultipleSvgFile($reqStr, $multiPrintFileName, $sidePrintSvg, $stickerInfo
	) {
		$stickerPath = '';
        $isStickerEnable = $isContourSvg = 0;
        if (!empty($stickerInfo)) {
            $stickerAHeight = $stickerInfo['height'];
            $stickerAWidht = $stickerInfo['width'];
            if ($stickerInfo['cutline'] == 'Small' || $stickerInfo['cutline'] == 'Medium' || $stickerInfo['cutline'] == 'Large' && $stickerInfo['contourSvg'] != '') {
                $contourSvg = $stickerInfo['contourSvg'];
                if ($contourSvg != '') {
                    $isContourSvg = 1;
                    $htmlSticker = new \simple_html_dom();
                    $htmlSticker->load($contourSvg, false);
                    $stickerPath = $htmlSticker->find('path', 0);
                    $stickerGroup = $htmlSticker->find('g', 0);
                    $stickerGroupHeight = $stickerGroup->height;
                    $stickerGroupWidth = $stickerGroup->width;
                    $stickerGroupX = $stickerGroup->x;
                    $stickerGroupY = $stickerGroup->y;
                }
            }
            if ($stickerInfo['cutline'] == 'Circle' || $stickerInfo['cutline'] == 'Rectangle' || $stickerInfo['cutline'] == 'Heart' || $stickerInfo['cutline'] == 'Star' || $stickerInfo['cutline'] == 'Round corner') {
                $isStickerEnable = 1;
            }
        }
		$printArea = $sidePrintSvg['print_area'];
		$fileStr = chop($multiPrintFileName, '.svg');
		$svgStartTag = '<svg xmlns="http://www.w3.org/2000/svg"';
		$svgXlink = ' id="svgroot" xmlns:xlink="http://www.w3.org/1999/xlink"';
		$svgEndTag = '</g></svg>';
		$svgTagStr = $svgStartTag . $svgXlink;
		$html = new \simple_html_dom();
		$html->load($reqStr, false);
		$svg = $html->find('image#svg_1', 0);
		$svgFileStatus = false;
		if ($svg) {
			$mainLayer = '';
			$mainLayer = $html->find("g[id^=Layer_]");
			$defs = $html->find('defs', 0);
			$vAlignWidthInch = $cropVal = $cropValPx = $bleedMarkMaxValue = 0;
			if (isset($mainLayer)) {
				$bounds = $gbleedM = '';
				$gbleedM = $html->find('g#bleedM', 0);
				if (isset($gbleedM) && $gbleedM !== '') {
					$vAlignBright = $gbleedM->find('rect#vAlignBRight', 0);
					$vAlignHeight = $vAlignBright->height;
					$vAlignWidth = $vAlignBright->width;
					$vAlignHeight = $vAlignBright->height;
					$bleedMarkMaxValue = max($vAlignWidth, $vAlignHeight);
					$vAlignWidthInch = (2 * $bleedMarkMaxValue) / $this->dpi;
				}
				$bounds = $html->find('g#bounds', 0);
				if ($isStickerEnable) {
                    $bounds->display = 'block';
                } else {
                    $bounds->display = 'none';
                }
				foreach ($mainLayer as $k => $v) {
					$printAreaId = 'bound_' . $k;
					$path = $bounds->find('path#bound_' . $k, 0);
					if (!isset($path) || $path == '') {
						$path = $bounds->find('path#bounds_' . $k, 0);
					}
					if ($isStickerEnable) {
                        $path->style = "display:block";
                    } else {
                        $path->style = "display:none";
                    }
					$id = $mainLayer[$k]->id;
					$isBleed = $path->isBleed;
					if (isset($isBleed) && $isBleed) {
						if ($this->printUnit == 'Feet') {
							$this->printUnit = 'Inch';
						}
						$cropVal = $path->cropVal;
						if ($this->printUnit == 'Pixel') {
							$cropValPx = $cropVal;
						} else {
							$cropVal = $this->unitConvertionToInch($cropVal);
							$cropValPx = $cropVal * $this->dpi;
						}
						$path->aHeight = $path->aHeight + ($cropVal * 2);
						$path->aWidth = $path->aWidth + ($cropVal * 2);
					}
					if ($isContourSvg) {
                        $height = $stickerGroupHeight;
                        $width = $stickerGroupWidth;
                        $aHeight = $stickerAHeight;
                        $aWidth = $stickerAWidht;
                    } else {
                        $height = $path->height;
                        $width = $path->width;
                        $aHeight = $path->aHeight ? $path->aHeight : 0;
                        $aWidth = $path->aWidth ? $path->aWidth : 0;
                    }
					//Print area dimension swapping height and width
					if ((intval($aWidth) > intval($aHeight)) && (intval($height) > intval($width))) {
						$temp = 0;
						$temp = $aHeight;
						$aHeight = $aWidth;
						$aWidth = $temp;
					}
					if ((intval($aHeight) > intval($aWidth)) && (intval($width) > intval($height))) {
						$temp = 0;
						$temp = $aWidth;
						$aWidth = $aHeight;
						$aHeight = $temp;
					}

					if ($isContourSvg) {
                        $x = ($stickerGroupX) - ($bleedMarkMaxValue);
                        $y = ($stickerGroupY) - ($bleedMarkMaxValue);
                    } else {
                        $x = ($path->x) - ($bleedMarkMaxValue);
                        $y = ($path->y) - ($bleedMarkMaxValue);
                    }
					if ($this->printUnit == 'Pixel') {
						$acWidth = $aWidth;
						$acHeight = $aHeight;
					} else {
						$aWidth = $this->unitConvertionToInch($aWidth);
						$aHeight = $this->unitConvertionToInch($aHeight);
						$acWidth = $aWidth * $this->dpi;
						$acHeight = $aHeight * $this->dpi;
					}
					$acHeight = $acHeight / $height;
					$acWidth = $acWidth / $width;
					if ($id == "Layer_" . $k . "") {
						if (strpos($mainLayer[$k], "layer") !== false) {
							$xeProps = '';
							$xeProps = $mainLayer[$k]->find(
								'g[xe-props]', 0
							);
							if (isset($xeProps) && $xeProps != '') {
								$this->productImageTag = '';
								//Check product with desin enable or not
								if (isset($printArea[$printAreaId]['is_include_product_image'])
									&& $printArea[$printAreaId]['is_include_product_image'] == 'include'
								) {
									$this->productImageTag = $svg;
								}
								//Prepared SVG
								$aWidth = $aWidth * $this->dpi;
								$aWidth = $aWidth + (2 * $cropValPx) + $vAlignWidthInch;
								$aHeight = $aHeight * $this->dpi;
								$aHeight = $aHeight + (2 * $cropValPx) + $vAlignWidthInch;
								$svgWidth = ' width="' . $aWidth . '"';
								$svgHeight = ' height="' . $aHeight . '"';
								$svgXY = ' x="0" y="0" overflow="visible">';
								//Check horizontal enabled or not
								$hFilp = $printArea[$printAreaId]['is_horizontally_flip'];
								if ($hFilp) {
									$filpY = $aHeight + (($y) * 0);
									$fTransform = '<g transform="translate(-0';
									$transformS = $fTransform . ',' . $filpY . ') scale(';
									if ($acHeight != $acWidth) {
										$sDimension = $acWidth . ',' . $acHeight . ')';
										$scaleValue = $transformS . $sDimension;

									} else {
										$scaleValue = $transformS . $acHeight . ')';
									}
									$filpSacle = ' scale(1,-1) translate(';
									$xyValue = '-' . $x . ',-' . $y . ')">'.$stickerPath;
									$transForm = $scaleValue . $filpSacle . $xyValue;
								} else {
									$scaleStr = '<g transform="scale(';
									$scale = $scaleStr . $acWidth . ',' . $acHeight;
									$translateX = ' translate(-' . $x . '';
									$translateY = ',-' . $y . ')">'.$stickerPath;
									$translate = $translateX . $translateY;
									$transForm = $scale . ')' . $translate;
								}
								$svgTag = $svgTagStr . $svgWidth . $svgHeight;
								$svgTagXY = $svgTag . $svgXY . $transForm . $this->productImageTag;
								$svgMiddleTag = $svgTagXY . $defs . $bounds . $gbleedM;
								$finalSvg = $svgMiddleTag . $mainLayer[$k] . $svgEndTag;
								$htmlSvg = $svgFinalString = '';
								//Check invert color option enabled or not
								$invertColor = $printArea[$printAreaId]['is_invert_color_enabled'];
								if ($invertColor) {
									$htmlSvg = new \simple_html_dom();
									$htmlSvg->load($finalSvg, false);
									$def = $htmlSvg->find('defs', 0);
									$firstTag = $def->first_child();
									$firstTag->outertext = $this->filter;
									$groupLayer = $htmlSvg->find('g.layer');
									foreach ($groupLayer as $key => $gl) {
										$groupLayer[$key]->filter = "url(#invertcolor)";
									}
								} else {
									$htmlSvg = $finalSvg;
								}
								$svgPath = $this->svgSavePath . $id;
								$svgFilePath = $svgPath . '_' . $multiPrintFileName;
								//png and pdf file name
								$pngAbsPath = $svgPath . '_' . $fileStr . '.png';
								$rgbPdfPath = $svgPath . '_' . $fileStr . '_rgb.pdf';
								$cmykPdfAbsPath = $svgPath . '_' . $fileStr . '.pdf';
								//Check bleed mark enabled or not per print profile
								$bleedMarkEnabled = $printArea[$printAreaId]['is_bleed_mark_enabled'];
								if ($bleedMarkEnabled && (isset($isBleed) && $isBleed)) {
									$bleedMark = $printArea[$printAreaId]['bleed_mark'];
									if ($bleedMark['cut_mark']) {
										$htmlDom = new \simple_html_dom();
										$htmlDom->load($htmlSvg, false);
										$domBleedG = $htmlDom->find('g#bleedM', 0);
										$domBleedG->display = 'block';
										$svgFinalString = $htmlDom;
									}
								} else {
									$svgFinalString = $htmlSvg;
								}
								//Check file format
								$fileFormat = $printArea[$printAreaId]['allowed_order_formats'];
								//Check used color for every individual product side
								$isColorSeparation = $printArea[$printAreaId]['is_color_separation_enabled'];
								if ($isColorSeparation) {
									//Used color list
									$this->printColorsArr = $printArea[$printAreaId]['used_colors'];
									$this->generateSvgFileByColor($svgFinalString, $id, $fileFormat);
								}
								if (!in_array('svg', $fileFormat)) {
									array_push($this->svgFileArr, $id . '_' . $multiPrintFileName);
								}
								$svgFileStatus = write_file(
									$svgFilePath, $svgFinalString
								);
								if ($svgFileStatus) {
									if (in_array('png', $fileFormat)) {
										$this->svgConvertToPng(
											$pngAbsPath, $svgFilePath
										);
									}
									if (in_array('pdf', $fileFormat)) {
										$this->svgConvertToRGBPdf(
											$rgbPdfPath, $svgFilePath
										);
										$this->rgbPdfConvertToCMYKPdf(
											$cmykPdfAbsPath, $rgbPdfPath
										);
									}
								}
							}
						}
					}
				}
			}
		}
		return $svgFileStatus;
	}

	/**
	 * GET: Create separate svg file by print color for reactngle print
	 *
	 * @param $svgStr The is current request SVG string
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return boolean
	 */
	private function generateSvgFileByColor($svgStr = null, $groupId = null, $fileFormat = array()) {
		$itemSidePath = $this->svgSavePath . $groupId . '_' . $this->sidePath;
		$svgFileStatus = false;
		$html = new \simple_html_dom();
		$html->load($svgStr, false);
		$pattern = $html->find("pattern[id^=p]");
		$main = $html->find("g#" . $groupId . "", 0);
		$pathStyle = $main->find('path'); //Get all path from svg string
		if (isset($pathStyle) && $pathStyle) {
			foreach ($pathStyle as $k => $v) {
				if ($pathStyle[$k]->id != 'boundMask') {
					$pathStyle[$k]->style = "display:none"; // Hide all path
				}
			}
		}

		$polygonStyle = $main->find('polygon'); //Get all polygon from svg string
		if (isset($polygonStyle) && $polygonStyle) {
			foreach ($polygonStyle as $k => $v) {
				$polygonStyle[$k]->style = "display:none"; // Hide all polygon
			}
		}
		$html->save();
		$htmlStr = new \simple_html_dom();
		$htmlStr->load($html, false);
		if (!empty($this->printColorsArr)) {
			foreach ($this->printColorsArr as $k => $color) {
				if ($color[0] == "#") {
					$path = $htmlStr->find('path[fill^=' . $color . ']');
					$pathId = '';
					foreach ($path as $key => $fill) {
						$pathId = $path[$key]->id;
						$pathTxt = $htmlStr->find("path[id^=" . $pathId . "]", 0);
						$pathTxt->style = "display:block";
						$strokeColor = $pathTxt->stroke;
						$fillColor = $pathTxt->fill;
						if (isset($strokeColor) && $strokeColor != '') {
							$pathTxt->stroke = $color;
							$pathTxt->fillid = $fillColor;
							$pathTxt->strokeid = $strokeColor;
						}
					}

					$pathStroke = $htmlStr->find('path[strokeid^=' . $color . ']');
					if (isset($pathStroke) && $pathStroke != '') {
						$pathStrokeId = '';
						$strokeWidthStr = 'stroke-width';
						$strokeWidth = 0;
						foreach ($pathStroke as $k => $stroke) {
							$pathStrokeId = $pathStroke[$k]->id;
							$pathStrokeTxt = $htmlStr->find("path[id^=" . $pathStrokeId . "]", 0);
							$strokeWidth = $pathStrokeTxt->$strokeWidthStr;
							if ($strokeWidth == 0) {
								$pathStrokeTxt->style = "display:none";
							} else {
								$pathStrokeTxt->style = "display:block";
							}
							$pathStrokeTxt->fill = $color;
							$pathStrokeTxt->stroke = $color;
						}

					}
					$polygon = $htmlStr->find('polygon[fill^=' . $color . ']');
					$polygonId = '';
					foreach ($polygon as $key => $value) {
						$polygonId = $polygon[$key]->id;
						$polygonTxt = $htmlStr->find(
							"polygon[id^=" . $polygonId . "]", 0
						);
						$polygonTxt->style = "display:block";
					}

					//png and pdf file name
					$pngAbsPath = $itemSidePath . '_' . $color . '.png';
					$rgbPdfPath = $itemSidePath . '_' . $color . '_rgb.pdf';
					$cmykPdfAbsPath = $itemSidePath . '_' . $color . '.pdf';
					//svg file
					$svgPath = $itemSidePath . "_" . $color . '.svg';
					$svgFileName = $groupId . '_' . $this->sidePath . "_" . $color . '.svg';
					$svgFileStatus = write_file($svgPath, $htmlStr);
					if (!in_array('svg', $fileFormat)) {
						array_push($this->svgFileArr, $svgFileName);
					}

					//get all group path
					$pathSvg = $htmlStr->find('path');
					foreach ($pathSvg as $k => $v) {
						if ($pathSvg[$k]->id != 'boundMask') {
							$pathSvg[$k]->style = "display:none";
						}
					}

					//get all clip path
					$clipPathArray = $htmlStr->find('clipPath');
					if (isset($clipPathArray) && $clipPathArray) {
						foreach ($clipPathArray as $k => $v) {
							$clipPath = $clipPathArray[$k]->find('path', 0);
							if (isset($clipPath) && $clipPath) {
								$clipPath->style = "display:block";
							}
						}
					}

					//get all polygon group path
					$polygonSvg = $htmlStr->find('polygon');
					foreach ($polygonSvg as $k => $v) {
						$polygonSvg[$k]->style = "display:none";
					}
				} else {
					if ($color != '' && (filter_var($color, FILTER_VALIDATE_URL))) {
						$patternId = '';
						$baseFileName = basename($color);
						$fileNameArr = explode(".", $baseFileName);
						$patternId = $fileNameArr[0];
						if (isset($pattern) && $pattern) {
							$pathPattern = $htmlStr->find(
								'path[fill^=url(#' . $patternId . ')]'
							);
							$pathIdPattern = '';
							foreach ($pathPattern as $kk => $ppv) {
								$pathIdPattern = $pathPattern[$kk]->id;
								$pathTxtPattern = $htmlStr->find(
									"path[id^=" . $pathIdPattern . "]", 0
								);
								$pathTxtPattern->style = "display:block";
							}
						}
						//png and pdf file name
						$pngAbsPath = $itemSidePath . '_' . $patternId . '.png';
						$rgbPdfPath = $itemSidePath . '_' . $patternId . '_rgb.pdf';
						$cmykPdfAbsPath = $itemSidePath . '_' . $patternId . '.pdf';
						//SVg file
						$svgFileName = $this->sidePath . "_" . $patternId . '.svg';
						$svgPath = $this->svgSavePath . $svgFileName;
						$svgFileStatus = write_file($svgPath, $htmlStr);
						//Get all pattern path from svg string
						$pathStylePattern = $htmlStr->find('path');
						foreach ($pathStylePattern as $kkk => $pspv) {
							if ($pathStylePattern[$kkk]->id != 'boundMask') {
								// Hide all path
								$pathStylePattern[$kkk]->style = "display:none";
							}
						}
					}
				}
				if ($svgFileStatus) {
					if (in_array('png', $fileFormat)) {
						$this->svgConvertToPng($pngAbsPath, $svgPath);
					}
					if (in_array('pdf', $fileFormat)) {
						$this->svgConvertToRGBPdf(
							$rgbPdfPath, $svgPath
						);
						$this->rgbPdfConvertToCMYKPdf(
							$cmykPdfAbsPath, $rgbPdfPath
						);
					}
				}
			}
		}
		return $svgFileStatus;
	}

	/**
	 * GET: SVG file convert to PNG file through imagick
	 *
	 * @param $pngAbsPath The is current request PNG path
	 * @param $svgAbsPath The is current request SVG path
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return nothing
	 */
	private function svgConvertToPng($pngAbsPath, $svgAbsPath) {
		$returnResult = $this->checkInkscape();
		if ($returnResult['status'] && file_exists($svgAbsPath)) {
			$shellFun = $returnResult['value'];
			$cmdPng = "inkscape " . escapeshellarg(
				$svgAbsPath
			) . " --export-png=" . escapeshellarg(
				$pngAbsPath
			) . " --without-gui";
			$shellFun($cmdPng);
		}
	}

	/**
	 * GET: SVG file convert to RGB pdf file through Inkscape
	 *
	 * @param $rgbPdfAbsPath The is current request RGB  PDF path
	 * @param $svgAbsPath    The is current request SVG path
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return nothing
	 */
	private function svgConvertToRGBPdf($rgbPdfAbsPath, $svgAbsPath) {
		$returnResult = $this->checkInkscape();
		if ($returnResult['status'] && file_exists($svgAbsPath)) {
			$shellFun = $returnResult['value'];
			$cmdPdf = "inkscape " . escapeshellarg(
				$svgAbsPath
			) . " --export-pdf=" . escapeshellarg(
				$rgbPdfAbsPath
			);
			$shellFun($cmdPdf);
		}
	}

	/**
	 * GET: RGB pdf file convert to CMYK pdf file through Ghostscript
	 *
	 * @param $cmykPdfAbsPath The is current request CYMK  PDF path
	 * @param $rgbPdfAbsPath  The is current request RGB  PDF path
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return nothing
	 */
	private function rgbPdfConvertToCMYKPdf($cmykPdfAbsPath, $rgbPdfAbsPath) {
		$returnResult = $this->checkGhostScript();
		if ($returnResult['status'] && file_exists($rgbPdfAbsPath)) {
			$cmykPdfAbsPath = escapeshellarg($cmykPdfAbsPath);
			$fromRgbPdfAbsPath = escapeshellarg($rgbPdfAbsPath);
			$shellFun = $returnResult['value'];
			$cmdPdfCmyk = "gs -dSAFER -dBATCH \
                -dNOPAUSE -dNOCACHE -sDEVICE=pdfwrite -dAutoRotatePages=/None \
                -sColorConversionStrategy=CMYK \
                -dProcessColorModel=/DeviceCMYK \
                -sOutputFile=" . $cmykPdfAbsPath . " \
                " . $fromRgbPdfAbsPath;
			$shellFun($cmdPdfCmyk);
			if (file_exists($rgbPdfAbsPath)) {
				unlink($rgbPdfAbsPath); //remove rgb pdf file
			}
		}
	}

	/**
	 * GET: Check Inkscape is avialable or not in server
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return array of shell enabled function
	 */
	private function checkInkscape() {
		$result['status'] = true;
		$result['value'] = 'shell_exec';
		$retrunResult = $this->getShellEnabledFunction();
		if ($retrunResult['status']) {
			$shell_function = $retrunResult['value'];
			system("inkscape --version > /dev/null", $retvalInk);
			if ($retvalInk == 0) {
				$result['status'] = true;
				$result['value'] = $shell_function;
			} else {
				if ($shell_function == 'exec' && empty($retvalInk)) {
					$result['status'] = true;
					$result['value'] = $shell_function;
				} else {
					$result['status'] = false;
					$result['value'] = '';
				}
			}
		}
		return $result;
	}

	/**
	 * GET: Check Ghostscript is avialable or not in server
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return array of shell enabled function
	 */
	private function checkGhostScript() {
		$result['status'] = true;
		$result['value'] = 'shell_exec';
		$retrunResult = $this->getShellEnabledFunction();
		if ($retrunResult['status']) {
			$shell_function = $retrunResult['value'];
			system("gs --version > /dev/null", $retvalInk);
			if ($retvalInk == 0) {
				$result['status'] = true;
				$result['value'] = $shell_function;
			} else {
				if ($shell_function == 'exec' && empty($retvalInk)) {
					$result['status'] = true;
					$result['value'] = $shell_function;
				} else {
					$result['status'] = false;
				}
			}
		}
		return $result;
	}

	/**
	 * GET: Check enabled/disabled function in server(php.ini file)
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return array of shell enabled function
	 */
	private function getShellEnabledFunction() {
		$result = array();
		//default function
		$disableFunctions = ini_get("disable_functions");
		if ($disableFunctions != '') {
			$disableFunctionsArr = explode(',', rtrim($disableFunctions, ','));
		} else {
			$result['status'] = true;
			$result['value'] = 'shell_exec';
		}
		//all default function for run shell command
		$deafaultShell = array(
			"passthru",
			"exec",
			"system",
			"shell_exec",
		);
		if (!empty($disableFunctionsArr)) {
			foreach ($deafaultShell as $value) {
				if (!in_array($value, $disableFunctionsArr)) {
					$result['value'] = $value;
					$result['status'] = true;
				} else {
					$result['value'] = '';
					$result['status'] = false;
				}
			}
		}
		return $result;
	}

	/**
	 * GET: Check enabled/disabled function in server(php.ini file)
	 *
	 * @param $value unit value in string
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return float or Integer
	 */
	private function unitConvertionToInch($value) {
		$result = 0;
		switch ($this->printUnit) {
		case 'Centimeter':
			return $result = ($value / 2.54);
			break;
		case 'Millimeter':
			return $result = ($value / 25.4);
			break;
		case 'Feet':
			return $result = ($value * 12);
			break;
		default:
			return $value;
			break;
		}
	}

	/**
	 * GET: Download Zip file
	 *
	 * @param $dir This is for zip download directory
	 *
	 * @author radhanatham@riaxe.com
	 * @date   03 Jan 2020
	 * @return boolean
	 */
	private function zipFileDownload($dir) {
		if (file_exists($dir)) {
			header('Content-Description: File Transfer');
			header("Content-type: application/x-msdownload", true, 200);
			header('Content-Disposition: attachment; filename=' . basename($dir));
			header('Expires: 0');
			header('Cache-Control: must-revalidate');
			header("Pragma: no-cache");
			header('Content-Length: ' . filesize($dir));
			readfile($dir);
			if (file_exists($dir)) {
				unlink($dir);

			}
			$status = true;
			exit();
		} else {
			$status = false;
		}
		return $status;
	}

	/**
	 * GET: include dom html file
	 *
	 * @author radhanatham@riaxe.com
	 * @date   04 April 2020
	 * @return nothing
	 */
	private function domHtmlPathInclue() {
		include_once dirname(__FILE__) . '/../../../Dependencies/simple_html_dom.php';
	}
	/**
	 * Delete: Directory
	 *
	 * @author soumyas@riaxe.com
	 * @date   05 June 2020
	 * @return nothing
	 */
	public function deleteDirectory($dirname) {
		if (is_dir($dirname)) {
			$dir_handle = opendir($dirname);
		}
		if (!$dir_handle) {
			return false;
		}
		while ($file = readdir($dir_handle)) {
			if ($file != "." && $file != "..") {
				if (!is_dir($dirname . "/" . $file)) {
					unlink($dirname . "/" . $file);
				} else {
					delete_directory($dirname . '/' . $file);
				}

			}
		}
		closedir($dir_handle);
		rmdir($dirname);
		return true;
	}
	/**
	 *
	 * Delete token
	 * @param $orderId
	 * @param $orderItemId
	 * @author soumyas@riaxe.com
	 * @date   04 June 2020
	 * @return array
	 *
	 */
	public function deleteOrderToken($orderId, $orderItemId) {
		$tokenInit = new OrderItemToken();
		$tokenDelete = $tokenInit->where(
			['order_id' => $orderId, 'order_item_id' => $orderItemId]
		);
		return $tokenDelete->delete();
	}
	/**
	 *
	 * Recurse copy
	 * @param $src
	 * @param $dst
	 * @author soumyas@riaxe.com
	 * @date   04 June 2020
	 * @return true
	 *
	 */
	protected function recurse_copy($src, $dst) {
		$dir = opendir($src);
		@mkdir($dst);
		while (false !== ($file = readdir($dir))) {
			if (($file != '.') && ($file != '..')) {
				if (is_dir($src . '/' . $file)) {
					$this->recurse_copy($src . '/' . $file, $dst . '/' . $file);
				} else {
					@copy($src . '/' . $file, $dst . '/' . $file);
				}
			}
		}
		closedir($dir);
	}

}