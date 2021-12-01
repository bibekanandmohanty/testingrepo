<?php
/**
 * Quotation Items
 *
 * PHP version 5.6
 *
 * @category  Quotation_Items
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */

namespace App\Modules\Quotations\Models;

/**
 * Production Satus
 *
 * @category Quotation_Items
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class QuotationItems extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'quote_items';
    protected $fillable = ['quote_id' , 'product_id', 'quantity', 'artwork_type', 'custom_design_id', 'design_cost', 'unit_total', 'is_variable_decoration', 'is_custom_size', 'custom_size_dimension', 'custom_size_dimension_unit', 'is_decorated_product', 'is_redesign'];
    public $timestamps = false;

}
