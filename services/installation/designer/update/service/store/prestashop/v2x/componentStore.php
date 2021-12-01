<?php

include_once dirname(__FILE__) . '/../../../../../../config/config.inc.php';

class StoreComponent
{

    /**
     * Copy all themes and modules files
     *
     * @param $updateStoreFilePath  New package path
     *
     * @author radhanatham@riaxe.com
     * @date   01 Oct 2020
     * @return Nothing
     */
    public function copyStoreThemeFiles($updateStoreFilePath)
    {
        if (!@copy($updateStoreFilePath . 'prestashop/frontendlc.php', ROOTABSPATH . 'frontendlc.php')) {
            $errorMsg = '- frontendlc.php file didn\'t copy. \n';
            $this->xe_log($errorMsg);
        }
        if (!is_dir(ROOTABSPATH . "PrestaShop-webservice-lib-master")) {
            mkdir(ROOTABSPATH . 'PrestaShop-webservice-lib-master', 0755, true);
        }
        $this->recurse_copy($updateStoreFilePath . "prestashop/PrestaShop-webservice-lib-master", ROOTABSPATH . "PrestaShop-webservice-lib-master");
        $this->recurse_copy($updateStoreFilePath . "prestashop/modules", ROOTABSPATH . "modules");
        if (_PS_VERSION_ <= '1.7.3.4') {
            $this->recurse_copy($updateStoreFilePath . "prestashop/themes", ROOTABSPATH . "themes");
        } else {
            if (_PS_VERSION_ <= '1.7.4.4') {
                $this->recurse_copy($updateStoreFilePath . "prestashop/theme1740", ROOTABSPATH . "themes");
            } else {
                $this->recurse_copy($updateStoreFilePath . "prestashop/theme1750", ROOTABSPATH . "themes");
            }
        }
        if (_PS_VERSION_ >= '1.7.5.0') {
            $this->recurse_copy($updateStoreFilePath . "prestashop/src", ROOTABSPATH . "src");
        }
        $this->updateWebServiceKey();
        $this->alterProdutTable();
        $this->addCustomColumnProduct();
    }

    /**
     * Update PrestaShop weservice key
     *
     * @param nothing
     *
     * @author radhanatham@riaxe.com
     * @date   01 Oct 2020
     * @return Nothing
     */
    private function updateWebServiceKey()
    {
        $xetoolDir = BASE_DIR;
        $result = 0;
        $date = date('Y-m-d H:i:s', time());
        //Set configuration for xetool dir
        $psXeTool = 'PS_XETOOL';
        $checkXetoolSql = "SELECT COUNT(*) AS nos from " . _DB_PREFIX_ . "configuration where name = '" . $psXeTool . "'";
        $rowXetool = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($checkXetoolSql);
        if ($rowXetool[0]['nos']) {
            $queryXetool = "UPDATE " . _DB_PREFIX_ . "configuration SET value= '" . $xetoolDir . "',date_upd='" . $date . "' WHERE name = '" . $psXeTool . "'";
            Db::getInstance()->Execute($queryXetool);
        } else {
            $xeToolInsertSql = "INSERT INTO `" . _DB_PREFIX_ . "configuration` (`name`,`value`,`date_add`,`date_upd`)
                VALUES ('" . $psXeTool . "','" . $xetoolDir . "','" . $date . "','" . $date . "')";
            Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($xeToolInsertSql);
        }

        $description = 'inkxe webservicekey';
        $className = 'WebserviceRequest';
        $resourceArr = array();
        $resourceArr = '{"resource_list": [{"name": "addresses","method": ["GET"]},
            {"name": "categories","method": ["GET", "POST", "PUT"]},
            {"name": "countries","method": ["GET"]},
            {"name": "customers","method": ["GET"]},
            {"name": "order_details","method": ["GET", "POST", "PUT"]},
            {"name": "order_states","method": ["GET", "POST", "PUT"]},
            {"name": "orders","method": ["GET", "POST", "PUT"]},
            {"name": "order_carriers","method": ["GET", "POST", "PUT"]},
            {"name": "order_payments","method": ["GET", "POST", "PUT"]},
            {"name": "order_slip","method": ["GET", "POST", "PUT"]},
            {"name": "order_invoices","method": ["GET", "POST", "PUT"]},
            {"name": "order_histories","method": ["GET", "POST", "PUT"]},
            {"name": "products","method": ["GET", "POST", "PUT"]},
            {"name": "states","method": ["GET"]},
            {"name": "stock_availables","method": ["GET", "POST", "PUT"]}]}';
        $resourceArr = json_decode($resourceArr, true);
        $sqlWebService = "select `id_webservice_account` from `" . _DB_PREFIX_ . "webservice_account` where description = '" . $description . "'";
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlWebService);
        if (!empty($row[0]['id_webservice_account'])) {
            $webserviceId = $row[0]['id_webservice_account'];
            foreach ($resourceArr['resource_list'] as $v) {
                foreach ($v['method'] as $v1) {
                    $checkWsSql = "SELECT COUNT(*) AS nos from " . _DB_PREFIX_ . "webservice_permission where resource = '" . $v['name'] . "' AND method ='" . $v1 . "' AND id_webservice_account=" . $webserviceId . "";
                    $rowWstool = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($checkWsSql);
                    if (!$rowWstool[0]['nos']) {
                        $sql_resurce = "INSERT INTO " . _DB_PREFIX_ . "webservice_permission (resource,method,id_webservice_account) VALUES('" . $v['name'] . "','" . $v1 . "'," . $webserviceId . ")";
                        Db::getInstance(_PS_USE_SQL_SLAVE_)->Execute($sql_resurce);
                    }
                }
            }
        }
    }

    /**
     * Alter product table fields
     *
     * @param nothing
     *
     * @author radhanatham@riaxe.com
     * @date   01 Oct 2020
     * @return nothing
     */
    private function alterProdutTable()
    {
        $status = 0;
        $sqlCatalog = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product LIKE 'is_catalog'";
        $rowscatalog = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlCatalog);
        if (!empty($rowscatalog)) {
            $sqlCatalogDropColumn = "ALTER TABLE " . _DB_PREFIX_ . "product CHANGE `is_catalog` `is_catalog` tinyint(1) NOT NULL";
            $status = Db::getInstance()->Execute($sqlCatalogDropColumn);
            //Alter column
            $sqlCatalogAlter = "ALTER TABLE " . _DB_PREFIX_ . "product ADD COLUMN `is_catalog` tinyint(1) NOT NULL DEFAULT '0'";
            $status = Db::getInstance()->Execute($sqlCatalogAlter);
        } else {
            $sqlCatalogAlter = "ALTER TABLE " . _DB_PREFIX_ . "product ADD COLUMN `is_catalog` tinyint(1) NOT NULL DEFAULT '0'";
            $status = Db::getInstance()->Execute($sqlCatalogAlter);
        }
        $sql = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product LIKE 'xe_is_temp'";
        $rows = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        if (!empty($rows)) {
            $sqlDropColumn = "ALTER TABLE " . _DB_PREFIX_ . "product CHANGE `xe_is_temp` `xe_is_temp` INT(20) NOT NULL";
            $status = Db::getInstance()->Execute($sqlDropColumn);
            //Alter column
            $sqlAlter = "ALTER TABLE " . _DB_PREFIX_ . "product ADD COLUMN `xe_is_temp` INT(20) NOT NULL DEFAULT '0'";
            $status = Db::getInstance()->Execute($sqlAlter);
        } else {
            $sqlAlter = "ALTER TABLE " . _DB_PREFIX_ . "product ADD COLUMN `xe_is_temp` INT(20) NOT NULL DEFAULT '0'";
            $status = Db::getInstance()->Execute($sqlAlter);
        }
        $sql_alter_check = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product LIKE 'is_addtocart'";
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_alter_check);
        if (!empty($result)) {
            $status = 1;
        } else {
            $sql_alter = "ALTER TABLE " . _DB_PREFIX_ . "product ADD COLUMN `is_addtocart` tinyint(1) DEFAULT 0";
            $status = Db::getInstance()->Execute($sql_alter);
        }
        $sql_pa = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product_attribute LIKE 'xe_is_temp'";
        $row = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_pa);
        if (!empty($row)) {
            $status = 1;
        } else {
            $sql1 = "ALTER TABLE " . _DB_PREFIX_ . "product_attribute ADD COLUMN `xe_is_temp` enum('0', '1') DEFAULT '0'";
            $status = Db::getInstance()->Execute($sql1);
        }
        $sql_pca = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product_attribute_combination LIKE 'xe_is_temp'";
        $rows1 = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_pca);
        if (!empty($rows1)) {
            $status = 1;
        } else {
            $sql2 = "ALTER TABLE " . _DB_PREFIX_ . "product_attribute_combination ADD COLUMN `xe_is_temp` enum('0', '1') DEFAULT '0'";
            $status = Db::getInstance()->Execute($sql2);
        }
        return $msg = $status ? $status : 0;
    }

    /**
     * Alter all cutom table fields
     *
     * @param nothing
     *
     * @author radhanatham@riaxe.com
     * @date   01 Oct 2020
     * @return nothing
     */
    private function addCustomColumnProduct()
    {
        $sqlCheck = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "orders LIKE 'ref_id'";
        $rowsCheck = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlCheck);
        if (empty($rowsCheck)) {
            $sqlAlter = 'ALTER TABLE ' . _DB_PREFIX_ . 'orders ADD ref_id int(10) unsigned NOT NULL';
            $status = Db::getInstance()->Execute($sqlAlter);
        }
        $sqlOrderDetails = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "order_detail LIKE 'ref_id'";
        $rowOrderDetails = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlOrderDetails);
        if (!empty($rowOrderDetails)) {
            $sqlDropColumn = "ALTER TABLE " . _DB_PREFIX_ . "order_detail CHANGE `ref_id` `ref_id` INT(10) NOT NULL";
            $status = Db::getInstance()->Execute($sqlDropColumn);
            //Alter column
            $sqlOdAlter = "ALTER TABLE " . _DB_PREFIX_ . "order_detail ADD COLUMN `ref_id`   VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlOdAlter);
        } else {
            $sqlOdAlter = "ALTER TABLE " . _DB_PREFIX_ . "order_detail ADD COLUMN `ref_id`   VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlOdAlter);
        }

        $sql_product = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "product LIKE 'customize'";
        $row_product = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql_product);
        if (empty($row_product)) {
            $sql = 'ALTER TABLE ' . _DB_PREFIX_ . 'product ADD COLUMN customize tinyint(1) DEFAULT 0';
            Db::getInstance()->Execute($sql);
        }
        $sqlCartProduct = "SHOW COLUMNS FROM " . _DB_PREFIX_ . "cart_product LIKE 'ref_id'";
        $rowCartProduct = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sqlCartProduct);
        if (!empty($rowCartProduct)) {
            $sqlDropColumn = "ALTER TABLE " . _DB_PREFIX_ . "cart_product CHANGE `ref_id` `ref_id` INT(10) NOT NULL";
            $status = Db::getInstance()->Execute($sqlDropColumn);
            //Alter column
            $sqlCpAlter = "ALTER TABLE " . _DB_PREFIX_ . "cart_product ADD COLUMN `ref_id`   VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlCpAlter);
        } else {
            $sqlCpAlter = "ALTER TABLE " . _DB_PREFIX_ . "cart_product ADD COLUMN `ref_id`   VARCHAR(250) NOT NULL";
            $status = Db::getInstance()->Execute($sqlCpAlter);
        }
    }

}
