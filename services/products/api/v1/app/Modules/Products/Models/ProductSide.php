<?php
/**
 * Product Side
 *
 * PHP version 5.6
 *
 * @category  Product_Side
 * @package   Products
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Products\Models;

/**
 * Product Side Class
 *
 * @category Product_Side
 * @package  Products
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductSide extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = [
        'product_setting_id', 
        'side_name', 
        'side_index', 
        'product_image_dimension', 
        'is_visible',
        'image_overlay',
        'multiply_overlay',
        'overlay_file_name'
    ];
    public $timestamps = false;
    /**
     * Create One-to-Many relationship between Product Side and Product
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
            'product_side_id', 
            'xe_id'
        );
    }
}
