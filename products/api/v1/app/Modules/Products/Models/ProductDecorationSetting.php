<?php
/**
 * Manage Print Profile
 *
 * PHP version 5.6
 *
 * @category  Product_Decoration_Setting
 * @package   Product
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;
/**
 * Product Decoration Setting Controller
 *
 * @category Product_Decoration_Setting
 * @package  Product
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductDecorationSetting extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'product_setting_id',
        'product_side_id',
        'dimension',
        'name',
        'mask_json_file',
        'print_area_id',
        'sub_print_area_type',
        'pre_defined_dimensions',
        'user_defined_dimensions',
        'custom_min_height',
        'custom_max_height',
        'custom_min_width',
        'custom_max_width',
        'custom_bound_price',
        'is_border_enable',
        'is_sides_allow',
        'no_of_sides',
        'locations',
        'image_overlay',
        'multiply_overlay',
        'overlay_file_name',
		'bleed_mark_data',
        'shape_mark_data',
        'is_dimension_enable'];
    public $timestamps = false;
    protected $modelPath = 'App\Modules\Products\Models';

    /**
     * One-to-Many relationship between Product Decoration Setting and Print
     * profile Decoration Setting Relation
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function print_profile_decoration_settings()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\PrintProfileDecorationSettingRel', 
            'decoration_setting_id', 
            'xe_id'
        );
    }
    /**
     * One-to-Many relationship between Product Decoration Setting and Product
     * Size Variant Decoration Settings
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function product_size_variant_decoration_settings()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\ProductSizeVariantDecorationSetting', 
            'decoration_setting_id', 
            'xe_id'
        );
    }
    /**
     * One-to-Many relationship between Product Decoration Setting and Print Area
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function print_area()
    {
        return $this->hasMany(
            'App\Modules\DecorationAreas\Models\PrintArea', 
            'xe_id', 
            'print_area_id'
        );
    }
}
