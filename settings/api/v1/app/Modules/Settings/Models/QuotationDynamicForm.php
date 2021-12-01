<?php
/**
 * Quotation Dynamic Form Attributes
 *
 * PHP version 5.6
 *
 * @category  Quotation_Dynamic_Form_Attributes
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */

namespace App\Modules\Settings\Models;

/**
 * Quotation Dynamic Form Attributes
 *
 * @category Quotation_Dynamic_Form_Attributes
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */

class QuotationDynamicForm extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'quote_dynamic_form_values';
    protected $fillable = ['label', 'label_slug', 'attribute_id', 'placeholder', 'value', 'is_required', 'sort_order', 'store_id', 'is_default'];
    public $timestamps = false;

}
