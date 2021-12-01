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
 * @category Quotation_Item_Variant
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class QuotationItemVariants extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'quote_item_variants';
    protected $fillable = ['item_id' , 'variant_id', 'quantity', 'unit_price', 'attribute', 'options'];
    public $timestamps = false;
}
