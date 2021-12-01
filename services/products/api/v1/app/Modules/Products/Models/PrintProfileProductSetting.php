<?php
/**
 * Cliparts Model
 *
 * PHP version 5.6
 *
 * @category  Print_Profile_Product_Setting
 * @package   Products
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;

/**
 * Cliparts
 *
 * @category Print_Profile_Product_Setting
 * @package  Products
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintProfileProductSetting extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'print_profile_product_setting_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'product_id', 
        'is_crop_mark', 
        'is_safe_zone', 
        'crop_value', 
        'safe_value', 
        'is_3d_preview', 
        'scale_unit_id'
    ];
    public $timestamps = false;
}
