<?php

$docAbsPath = ROOTABSPATH . "wp-blog-header.php";
require_once $docAbsPath;

class StoreComponent
{
    public function copyPluginfiles($newImprintStorePath)
    {
    	$today = date("d-m-Y");
        $time = date("H-i-s");
        $dateTime = $today . "_" . $time;
    	$pluginPath = WP_PLUGIN_DIR.DS.'inkxe_product_designer';
        //Start rename old folders and files
        if (is_dir($pluginPath)) {
            $oldFilePath = $pluginPath . DS . 'inkxe_product_designer.php';
            $oldPathRename = $oldFilePath . '_' . $dateTime;
            if (file_exists($oldFilePath)) {
                 rename($oldFilePath, $oldPathRename);
             }
            $oldAssetPath = $pluginPath . DS . 'assets';
            $oldAssetPathRename = $oldAssetPath . '_' . $dateTime;
            if (is_dir($oldAssetPath)) {
                 rename($oldAssetPath, $oldAssetPathRename);
            }
        }
        $this->recurse_copy($newImprintStorePath . STORE_VERSION . '/plugins', WP_PLUGIN_DIR);
        if (!@copy($newImprintStorePath . STORE_VERSION . '/frontendlc.php', ROOTABSPATH . 'frontendlc.php')) {
            $errorMsg = '- frontendlc.php file didn\'t copy. \n';
            $this->xe_log("\n" . date("Y-m-d H:i:s") . ': Error :' . $errorMsg . "\n");
        }
    }

}
