<?php
/**
 * 
 * @category   Product Side
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\ProductDecorations\Models;

class ProductSide extends \Illuminate\Database\Eloquent\Model {

    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['product_setting_id', 'side_name', 'side_index', 'product_image_dimension', 'is_visible'];
    public $timestamps = false;

    // Get Product Decoration Settings
    public function product_decoration_setting() {
        return $this->hasMany('App\Modules\ProductDecorations\Models\ProductDecorationSetting', 'product_side_id', 'xe_id')->select('xe_id', 'product_setting_id as setting_id', 'product_side_id', 'name', 'dimension', 'print_area_id', 'sub_print_area_type', 'custom_min_height as min_height', 'custom_max_height as max_height', 'custom_min_width as min_width', 'custom_max_width as max_width', 'custom_bound_price as bound_price', 'is_border_enable', 'is_sides_allow', 'no_of_sides', 'is_dimension_enable');
    }

    // Get image of all sides (*CNU)
    public function images() {
        return $this->hasMany('App\Modules\ProductDecorations\Models\ProductDecorationSetting', 'product_side_id', 'xe_id');
    }
}
