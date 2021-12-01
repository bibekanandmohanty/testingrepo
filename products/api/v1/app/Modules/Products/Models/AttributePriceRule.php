<?php
/**
 * Cliparts Model
 *
 * PHP version 5.6
 *
 * @category  Attribute_Price_Rule
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
 * @category Attribute_Price_Rule
 * @package  Products
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class AttributePriceRule extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'attribute_price_rules';
    public $timestamps = false;
    protected $fillable = [
        'product_id', 
        'attribute_id', 
        'attribute_term_id', 
        'print_profile_id', 
        'price'
    ];
}
