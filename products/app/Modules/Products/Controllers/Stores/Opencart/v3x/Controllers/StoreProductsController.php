<?php 
    /**
     *
     * This Controller used to save, fetch or delete Cliparts on various endpoints
     *
     * @category   Products
     * @package    WooCommerce API
     * @author     Original Author <tanmayap@riaxe.com>
     * @author     Another Author <>
     * @copyright  2019-2020 Riaxe Systems
     * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
     * @version    Release: @package_version@1.0
     */
    namespace StoreSpace\Controllers;

    use Automattic\WooCommerce\Client;
    use App\Modules\Products\Controllers\Stores\Woocommerce\v3x\Controllers\Api as Api;
    use GuzzleHttp\Client as GuzzleClient;

    class StoreProductsController extends Api 
    {
        /**
         * Short description for class
         *
         * @package    PackageName
         * @author     Original Author <author@example.com>
         * @date       13th Aug 2019
         * @version    Release: @package_version@
         * @parameter  $args
         * @response   A JSON Response
         */
        public function getProducts($request, $response, $args)
        {
            $serverStatusCode = OPERATION_OKAY;
            $wcApi = Api::initWcApi();
            $productList = [];
            $endPoint = 'products';

            
            // Get all requested Query params 
            $filters = [
                'name' => $request->getQueryParam('name'),
                'sku' => $request->getQueryParam('sku'),
                'category' => $request->getQueryParam('category'),
                'page' => $request->getQueryParam('page'),
                'per_page' => $request->getQueryParam('per_page'),
                'order' => (!empty($request->getQueryParam('order')) && $request->getQueryParam('order') != "") ? $request->getQueryParam('order') : 'desc',
                'orderby' => (!empty($request->getQueryParam('orderby')) && $request->getQueryParam('orderby') != "") ? $request->getQueryParam('orderby') : 'id',
            ];
            
            $options = [];
            foreach ($filters as $filterKey => $filterValue) {
                if(isset($filterValue) && $filterValue != "") {
                    $options += [$filterKey => $filterValue];
                }
            }
            
            /**
             * For fetching Single Product, the endpoint is modified
             */
            if(isset($args['id']) && $args['id'] != "" && $args['id'] > 0) {
                $endPoint .= '/' . $args['id'];
            }
            /*if(isset($name) && $name != "") {
                $options += ['search' => $name];
            }
            if(isset($sku) && $sku != "") {
                $options += ['sku' => $sku];
            }
            if(isset($category) && $category != "") {
                $options += ['category' => $category];
            }
            if(isset($page) && $page != "") {
                $options += ['page' => $page];
            }
            if(isset($per_page) && $per_page != "") {
                $options += ['per_page' => $per_page];
            }*/
            // End of the filter

            try {
                $getProducts = $wcApi->get($endPoint, $options);
                $getProducts = objectToArray($getProducts);

                /**
                 * For single product listing, we get a 1D product array.
                 * But the for-loop works for Multi-Dimentional Array. So to push the single product array into the for-loop
                 * I converted the 1D array to Multi dimentional array, so that foreach loop will be intact
                 */
                if(!isset($getProducts[0])) {
                    $getProducts = [$getProducts];
                }
                // Connect with GuzzleClient to get Opencart Products
                $gzc = new GuzzleClient();
                $products = $gzc->request('GET', 'http://localhost/opencart/index.php?route=api/product', [
                    'auth' => []
                ]);
                $productsJSON = $products->getBody();
                $productsToArray = json_decode($productsJSON, true);
                // Only required columns are pushed to products array
                foreach ($productsToArray['products'] as $key => $product) {
                    if(isset($product['id']) && $product['id'] > 0) {
                        $productList[$key] = [
                            'id' => $product['id'],
                            'name' => $product['name'],
                            'slug' => $product['slug'],
                            'sku' => $product['sku'],
                            'price' => $product['price'],
                            'type' => $product['type'],
                            'categories' => $product['categories'],
                            'images' => $product['images'],
                            'description' => $product['description'],
                            'date_created' => $product['date_created']
                        ];
                    }
                }

                if(isset($productList) && is_array($productList) && count($productList) > 0) {
                    $response = [
                        'status' => 1,
                        'total' => count($productList),
                        'data' => $productList
                    ];
                } else {
                    $serverStatusCode = INVALID_FORMAT_REQUESTED;
                    $response = [
                        'status' => 0,
                        'message' => 'No products available',
                        'data' => []
                    ];
                }
            } catch (\Exception $e) {
                $serverStatusCode = EXCEPTION_OCCURED;
                $response = [
                    'status' => 0,
                    'message' => 'Invalid request',
                    'exception' => $e->getMessage()
                ];
            }
            
            return [
                'data' => $response,
                'httpStatusCode' => $serverStatusCode
            ];
        }

        /**
         * Short description for class
         *
         * @package    PackageName
         * @author     Original Author <author@example.com>
         * @date       13th Aug 2019
         * @version    Release: @package_version@
         * @parameter  $args
         * @response   A JSON Response
         */
        public function getCategories($request, $response, $args) {
            $serverStatusCode = OPERATION_OKAY;
            $wcApi = Api::initWcApi();
            $categories = [];
            $endPoint = 'products/categories';

            // Get all requested Query params 
            $name = $request->getQueryParam('name');
            
            // Set default option parameters
            $options = [
                'order' => 'desc',
                'orderby' => 'id'
            ];
            /**
             * Filtering criterial starts
             */
            if(isset($args['id']) && $args['id'] != "" && $args['id'] > 0) {
                $options += ['parent' => $args['id']];
            }
            if(isset($name) && $name != "") {
                $options += ['search' => $name];
            }

            // End of the filter

            $getCategories = $wcApi->get($endPoint, $options);

            // debug($getCategories, true);

            foreach ($getCategories as $key => $category) {
                $categories[$key] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'parent' => $category->parent
                ];
            }

            if(isset($categories) && is_array($categories) && count($categories) > 0) {
                $response = [
                    'status' => 1,
                    'data' => $categories
                ];
            }
            
            return [
                'data' => $response,
                'httpStatusCode' => $serverStatusCode
            ];
        }
    }