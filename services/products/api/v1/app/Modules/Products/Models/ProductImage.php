<?php
/**
 * Product Image
 *
 * PHP version 5.6
 *
 * @category  Product_Image
 * @package   Products
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;

/**
 * Product Image Class
 *
 * @category Product_Image
 * @package  Products
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductImage extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'product_images';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['name', 'store_id'];
    public $timestamps = false;
    /**
     * Create One-to-Many relationship between Product Image and Product Image
     * Side
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function sides()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\ProductImageSides', 
            'product_image_id'
        );
    }
}
