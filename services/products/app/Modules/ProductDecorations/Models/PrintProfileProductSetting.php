<?php
/**
 * 
 * @category   Print Profile Product Settings
 * @package    Eloquent
 * @author     Original Author <tanmayap@riaxe.com>
 * @author     Another Author <>
 * @copyright  2019-2020 Riaxe Systems
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@1.0
 */

namespace App\Modules\ProductDecorations\Models;

class PrintProfileProductSetting extends \Illuminate\Database\Eloquent\Model {

    protected $table = 'print_profile_product_setting_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['product_id', 'is_crop_mark', 'is_safe_zone', 'crop_value', 'safe_value', 'is_3d_preview', 'scale_unit_id'];
    public $timestamps = false;
}
