<?php
/**
 * Product Configurator
 *
 * PHP version 5.6
 *
 * @category  Product_Configurator
 * @package   Products
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;

/**
 * Product Configurator Class
 *
 * @category Product_Configurator
 * @package  Products
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductSection extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'product_sections';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['product_id', 'name', 'parent_id', 'sort_order', 'is_disable'];
    public $timestamps = false;
    /**
     * Create One-to-Many relationship between Section and Section Image
     *
     * @author satyabratap@riaxe.com
     * @date   20 Feb 2019
     * @return relationship object of category
     */
    public function images()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\ProductSectionImage',
            'section_id'
        );
    }
}
