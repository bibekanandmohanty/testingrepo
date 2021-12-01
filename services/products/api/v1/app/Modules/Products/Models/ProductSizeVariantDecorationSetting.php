<?php
/**
 * Product Size Variant Decoration Setting
 *
 * PHP version 5.6
 *
 * @category  Product_Size_Variant_Decoration_Setting
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
 * @category Product_Size_Variant_Decoration_Setting
 * @package  Products
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductSizeVariantDecorationSetting extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'product_size_variant_decoration_settings';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'decoration_setting_id', 
        'print_area_id', 
        'size_variant_id'
    ];
    public $timestamps = false;
}
