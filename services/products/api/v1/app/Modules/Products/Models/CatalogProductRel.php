<?php
/**
 * Catalog Product Setting Relation
 *
 * PHP version 5.6
 *
 * @category  Catalog_Product_Relation
 * @package   Products
 * @author    Radhanatha <radhanatham@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Products\Models;

/**
 * Catalog Product Relation Class
 *
 * @category Catalog_Product_Relation
 * @package  Products
 * @author   Radhanatha <radhanatham@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class CatalogProductRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'catalog_product_rel';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    protected $fillable = ['product_id', 'catalog_product_id'];
    public $timestamps = false;

}
