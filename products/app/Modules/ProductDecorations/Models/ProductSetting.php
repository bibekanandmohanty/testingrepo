<?php
/**
 * 
 * @category   Product Settings
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\ProductDecorations\Models;

class ProductSetting extends \Illuminate\Database\Eloquent\Model {

    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['product_id', 'is_crop_mark', 'is_safe_zone', 'crop_value', 'safe_value', 'is_3d_preview', 'scale_unit_id'];
    public $timestamps = false;

    // Get Product Image Setting Relations by product Setting id
    public function product_imgage_settings_rel() {
        return $this->hasOne('App\Modules\ProductDecorations\Models\ProductImageSettingsRel', 'product_setting_id', 'xe_id');
    }

    // Get Sides details by Product setting id
    public function sides() {
        return $this->hasMany('App\Modules\ProductDecorations\Models\ProductSide', 'product_setting_id', 'xe_id')->select(
            'xe_id',
            'product_setting_id',
            'side_name',
            'side_index',
            'product_image_dimension as dimension',
            'is_visible'
        );
    }
    
    // Get Product Decoration Setting by Product Setting ID
    public function product_decoration_setting() {
        return $this->hasMany('App\Modules\ProductDecorations\Models\ProductDecorationSetting', 'product_setting_id', 'xe_id');
    }

    // Get list of Print PRofiles by the product setting id
    public function print_profiles() {
        return $this->hasMany('App\Modules\ProductDecorations\Models\PrintProfileProductSettingRel', 'product_setting_id', 'xe_id')->select('print_profile_id', 'product_setting_id');
    }

  
}
