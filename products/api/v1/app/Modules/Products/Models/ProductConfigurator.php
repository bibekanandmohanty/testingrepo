<?php
/**
 * Product Configurator
 *
 * PHP version 5.6
 *
 * @category  Product_SVG_Configurator
 * @package   Products
 * @author    Mukesh <mukeshp@riaxe.com>
 * @copyright 2020-2021 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;

/**
 * Product Configurator Class
 *
 * @category Product_SVG_Configurator
 * @package  Products
 * @author   Mukesh <mukeshp@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ProductConfigurator extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'product_configurator';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['product_id', 'name', 'price'];
    public $timestamps = false;
    /**
     * Create One-to-Many relationship between Congigurator and Configurator Sides
     *
     * @author mukeshp@riaxe.com
     * @date   19 Jan 2021
     * @return relationship object of category
     */
    public function images()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\ProductConfiguratorSides',
            'section_id'
        );
    }
}
