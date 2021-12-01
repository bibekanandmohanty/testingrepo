<?php
/**
 * Product Setting
 *
 * PHP version 5.6
 *
 * @category  Product_Setting
 * @package   Products
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;

/**
 * Product Setting Class
 *
 * @category Product_Setting
 * @package  Products
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductSetting extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'store_id',
        'product_id',
        'is_variable_decoration',
        'is_ruler',
        'is_custom_size',
        'is_crop_mark',
        'is_safe_zone',
        'crop_value',
        'safe_value',
        'is_3d_preview',
        '3d_object',
        'scale_unit_id',
        'decoration_type',
        'decoration_dimensions',
        'custom_size_unit_price',
        'is_configurator',
        'is_svg_configurator'
    ];
    public $timestamps = false;
    protected $modelPath = 'App\Modules\Products\Models';

    /**
     * Create One-to-Many relationship between Product Setting and Product Image
     * Settings Rel 
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function product_imgage_settings_rel()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\ProductImageSettingsRel', 
            'product_setting_id', 
            'xe_id'
        );
    }

    /**
     * Create One-to-Many relationship between Product Setting and Product Side
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function product_sides()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\ProductSide', 
            'product_setting_id', 
            'xe_id'
        );
    }

    /**
     * Create One-to-Many relationship between Product Setting and Product
     * Decoration Setting
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function product_decoration_setting()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\ProductDecorationSetting', 
            'product_setting_id', 
            'xe_id'
        );
    }
    /**
     * Create One-to-Many relationship between Product Setting and Print Profile
     * Product Setting Rel
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function print_profile_product_setting_rel()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\PrintProfileProductSettingRel', 
            'product_setting_id', 
            'xe_id'
        );
    }
    /**
     * Create One-to-Many relationship between Product Setting and Product Side
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function sides()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\ProductSide', 
            'product_setting_id',
            'xe_id'
        )->select(
            'xe_id',
            'product_setting_id',
            'side_name',
            'side_index',
            'product_image_dimension as dimension',
            'is_visible',
            'image_overlay',
            'multiply_overlay',
            'overlay_file_name'
        );
    }
    /**
     * Create One-to-Many relationship between Product Setting and Print Profile
     * Product Setting Rel
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function print_profiles()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\PrintProfileProductSettingRel', 
            'product_setting_id', 
            'xe_id'
        )
            ->select(
                'print_profile_id', 
                'product_setting_id'
            );
    }
    /**
     * Create full URL for 3D Object file
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return string full url
     */
    public function get3dObjectFileAttribute()
    {
        $url = "";
        if (!empty($this->attributes['3d_object_file'])) {
            $url .= path('read', '3d_object');
            $url .= $this->attributes['3d_object_file'];
        }

        return $url;
    }
}
