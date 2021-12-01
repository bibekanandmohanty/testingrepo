<?php
/**
 * Routes
 *
 * PHP version 5.6
 *
 * @category  Routes
 * @package   SLIM_Routes
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin ProductsController
 */
use App\Middlewares\ValidateJWTToken as ValidateJWT;
use App\Modules\Products\Controllers\ProductConfiguratorController as Configurator;
use App\Modules\Products\Controllers\ProductDecorationsController as ProductDecoration;
use App\Modules\Products\Controllers\ProductImagesController as ProductImages;
use App\Modules\Products\Controllers\ProductsCatalogController as ProductCatalog;
use App\Modules\Products\Controllers\ProductsController as Products;

$container = $app->getContainer();

/**
 * Measurement Units Routes list
 */
$app->group(
	'/attribute-prices', function () use ($app) {
		$app->get('/{id}', Products::class . ':getProductAttrPrc');
		$app->post('', Products::class . ':saveProdAttrPrc');
	}
)->add(new ValidateJWT($container));

$app->post('/images/save', Products::class . ':saveImages');
/**
 * Measurement Units Routes list
 */
$app->group(
	'/products/measurement-units', function () use ($app) {
		// Get all Measurement Units
		$app->get('', Products::class . ':getMeasurementUnits');
	}
)->add(new ValidateJWT($container));

$app->group(
    '/products', function () use ($app) {
        // Category Routes
        $app->get('/list-of-categories', Products::class . ':totalCategories')->add(new ValidateJWT($container));
        $app->get('/categories', Products::class . ':totalCategories');
        $app->get('/tool', Products::class . ':getToolProductList')->add(new ValidateJWT($container));
        // Count Total Products (JWT Excluded)
        $app->get('/count', Products::class . ':getTotalProductCount');
        $app->get('/categories/{id}', Products::class . ':totalCategories')->add(new ValidateJWT($container));
        // Products Routes
        $app->get('/categories-subcatagories', Products::class . ':CategoriesSubcatagories')->add(new ValidateJWT($container));
        $app->get('/{id}', Products::class . ':getProductList')->add(new ValidateJWT($container));
        $app->get('', Products::class . ':getProductList')->add(new ValidateJWT($container));
        $app->get('/variants/{pid}', Products::class . ':getProductVariants');
        $app->get('/tier-price/{pid}', Products::class . ':getTierPricing');
        $app->post('/tier-price/{pid}', Products::class . ':saveTierPricing');
        $app->post('/categories', Products::class . ':saveCategories')->add(new ValidateJWT($container));
        $app->delete('/categories/{id}', Products::class . ':deleteCategories')->add(new ValidateJWT($container));
    }
);

$app->get('/variant-size-quantity/{pid}/{vid}', Products::class . ':variantAttributeDetails')->add(new ValidateJWT($container));
$app->get('/variant-combination-details/{pid}/{vid}', Products::class . ':multiAttributeVariantDetails')->add(new ValidateJWT($container));
$app->get('/shopify-product/{vid}', Products::class . ':getShopifyParentProduct');

// Separate Category list route for Print profile Assets section

/**
 * Product Images and Sides Routes List
 */
$app->group(
	'/image-sides', function () use ($app) {
		$app->get(
			'', ProductImages::class . ':getProductImages'
		);
		$app->get(
			'/{product_image_id}', ProductImages::class . ':getProductImages'
		);
		$app->post(
			'', ProductImages::class . ':saveProductImages'
		);
		$app->post(
			'/{product_image_id}', ProductImages::class . ':updateProductImages'
		);
		$app->delete(
			'/{ids}', ProductImages::class . ':productImageDelete'
		);
		// Enable/Disable Product Images
		$app->get(
			'/disable-toggle/{id}', ProductImages::class . ':disableProductImage'
		);
	}
)->add(new ValidateJWT($container));

/**
 * Product Decoration Settings Data Save
 */
$app->group(
    '/decorations', function () use ($app) {
        $app->post(
            '', ProductDecoration::class . ':saveProductDecorations'
        );
        $app->get(
            '/{product_id}', ProductDecoration::class . ':getProductDecorations'
        );
        // Update Decoration Settings
        $app->post(
            '/{product_id}', ProductDecoration::class . ':updateProductDecorations'
        );
        $app->delete(
            '/{product_id}', ProductDecoration::class . ':deleteDecoration'
        );
        $app->get(
            '/setting-details/{product_id}',
            ProductDecoration::class . ':productSettingDetails'
        );
        $app->get(
            '/quotation/{product_id}',
            ProductDecoration::class . ':getDecorationDetail'
        );
    }
)->add(new ValidateJWT($container));


// Predecorator Route
$app->group(
    '/predecorators', function () use ($app) {
        // Save new Record
        $app->post('', Products::class . ':savePredecorator');
        // Update existing Record
        $app->put('', Products::class . ':updateProduct');
        // Save new Records
        $app->get('/attributes', Products::class . ':getStoreAttributes');
        // Validate SKU or Name or Other Parameters
        $app->post('/validate', Products::class . ':validateParams');
        // Validate SKU or Name or Other Parameters
        $app->post('/variations', Products::class . ':createVariations');
        // Get Single and Multiple Predeco Data with Filters and paginations
        $app->get('', Products::class . ':getPredecorators');
        $app->get('/{id}', Products::class . ':getPredecorators');
    }
)->add(new ValidateJWT($container));

// Common Store Route
$app->group(
	'/store', function () use ($app) {
		// Get add store attribute name
		$app->get('/attributes', Products::class . ':getAttributeList');
		$app->get('/attributes/{id}', Products::class . ':getAttributeDetails');
	}
)->add(new ValidateJWT($container));

// Color Variants
$app->group(
    '/color-variants', function () use ($app) {
        $app->get('/{id}', Products::class . ':colorsByProductId');
    }
)->add(new ValidateJWT($container));

/**
 * Product Decoration Settings Data For Designer Tool
 */
$app->group(
    '/product-details', function () use ($app) {
        $app->get(
            '/{product_id}', ProductDecoration::class
            . ':productDetailsWithDecoration'
        );
    }
);

/**
 * Decaration Object Routes
 */
$app->group(
	'/obj-details', function () use ($app) {
		$app->post('', ProductDecoration::class . ':objDetailsOperation');
	}
)->add(new ValidateJWT($container));

/**
 * UV files Routes
 */
$app->group(
    '/uv-details', function () use ($app) {
        $app->post('', ProductDecoration::class . ':uvFilesOperation');
    }
)->add(new ValidateJWT($container));

/**
 * Product Configurator Section Data
 */
$app->group(
    '/product-section', function () use ($app) {
        $app->post(
            '/bulk-section-save', Configurator::class
            . ':addBulkConfiguratorImages'
        );
        $app->post(
            '', Configurator::class
            . ':saveProductConfigurator'
        );
        $app->get(
            '/{product_id}', Configurator::class
            . ':getProductConfigurators'
        );
        $app->delete(
            '/{id}', Configurator::class
            . ':deleteProductConfigurator'
        );
        $app->post(
            '/sort', Configurator::class
            . ':sortProductConfigurator'
        );
        $app->post(
            '/image', Configurator::class
            . ':saveConfiguratorImage'
        );
        // Update Product Configurator Images
        $app->post(
            '/image/{id}', Configurator::class
            . ':updateConfiguratorImage'
        );
        // Update product configurators
        $app->post(
            '/{id}', Configurator::class
            . ':updateProductConfigurator'
        );
        $app->get(
            '/settings/{id}', Configurator::class
            . ':getConfiguratorImages'
        );
        $app->delete(
            '/image/{id}', Configurator::class
            . ':deleteConfiguratorImage'
        );
        $app->get(
            '/disable/{id}', Configurator::class
            . ':disableConfigurator'
        );
        $app->get(
            '/image/disable/{id}', Configurator::class
            . ':disableConfiguratorImage'
        );
        $app->get(
            '/settings/disable/{id}', Configurator::class
            . ':updateConfiguratorSettings'
        );
    }
)->add(new ValidateJWT($container));

//Opencart Store API only
$app->get('/product-variant-details', Products::class . ':getProductVariant');

/**
 *Product catalog routes
 */
$app->group(
    '/import-product', function () use ($app) {
        $app->post('', ProductCatalog::class . ':importProucts');
    }
);

$app->group(
    '/catalog-product', function () use ($app) {
        $app->get('/products', ProductCatalog::class . ':getProducts');
        $app->get('/import-status/{id}', ProductCatalog::class . ':getImportProductStatus');
    }
);

$app->post('/product-import-csv', ProductCatalog::class . ':createProductCsvSample');
$app->get('/download-product-csv', ProductCatalog::class . ':downloadProductCsvSample');
$app->group(
    '/catalogs', function () use ($app) {
        $app->get('', ProductCatalog::class . ':getAllCatalog');
    }
);
$app->group(
    '/catalogs', function () use ($app) {
        $app->get('/categories', ProductCatalog::class . ':getCatalogCategory');
    }
);
$app->group(
    '/catalogs', function () use ($app) {
        $app->get('/brand', ProductCatalog::class . ':getCatalogBrand');
    }
);
$app->group(
    '/catalogs', function () use ($app) {
        $app->get('/product', ProductCatalog::class . ':getProductDetails');
    }
);
$app->group(
    '/svg-configurator', function () use ($app) {
        $app->post(
            '', Configurator::class
            . ':saveSVGProductConfigurator'
        );
        $app->get(
            '/{product_id}', Configurator::class
            . ':getSVGProductConfigurator'
        );
        $app->delete(
            '/{section_id}', Configurator::class
            . ':deleteSVGProductConfigurator'
        );
        $app->delete(
            '/sides/{side_id}', Configurator::class
            . ':deleteSVGConfiguratorSides'
        );
        $app->post(
            '/sides/{side_id}', Configurator::class
            . ':saveSVGConfiguratorSides'
        );
        $app->get(
            '/settings/disable/{id}', Configurator::class
            . ':updateSVGConfiguratorSettings'
        );
        $app->get(
            '/colors/{product_id}', Configurator::class
            . ':getColorsSVGProductConfigurator'
        );
        // Update product configurators
        $app->post(
            '/{id}', Configurator::class
            . ':updateSVGProductConfigurator'
        );
    }
)->add(new ValidateJWT($container));
