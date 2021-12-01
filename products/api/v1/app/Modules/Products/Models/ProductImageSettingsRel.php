<?php
/**
 * Product Image Setting Relation
 *
 * PHP version 5.6
 *
 * @category  Product_Image_Setting_Relation
 * @package   Products
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;
/**
 * Product Image Setting Relation Class
 *
 * @category Product_Image_Setting_Relation
 * @package  Products
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductImageSettingsRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'product_image_settings_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['product_setting_id', 'product_image_id'];
    public $timestamps = false;
    /**
     * Create One-to-Many relationship between Product Image Setting Relation
     * and Product Image
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function product_image()
    {
        return $this->hasOne(
            'App\Modules\Products\Models\ProductImage', 
            'xe_id', 
            'product_image_id'
        );
    }
}
