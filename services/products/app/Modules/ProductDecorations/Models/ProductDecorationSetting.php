<?php
/**
 * 
 * @category   Product Decoration Settings
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\ProductDecorations\Models;

class ProductDecorationSetting extends \Illuminate\Database\Eloquent\Model {

    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['product_setting_id', 'product_side_id', 'name', 'dimension', 'print_area_id', 'sub_print_area_type', 'custom_min_height', 'custom_max_height', 'custom_min_width', 'custom_max_width', 'custom_bound_price', 'is_border_enable', 'is_sides_allow', 'no_of_sides', 'is_dimension_enable'];
    public $timestamps = false;

    public function print_profile_decoration_settings() {
        return $this->hasMany('App\Modules\ProductDecorations\Models\PrintProfileDecorationSettingRel', 'decoration_setting_id', 'xe_id');
    }
}
