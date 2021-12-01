<?php
/**
 * This Model used for Quotation's Tag and their Relationship
 *
 * PHP version 5.6
 *
 * @category  Quotation
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Quotations\Models;

/**
 * Quotation Tag Relation
 *
 * @category Quotation_Tag
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class QuotationRequestFormValues extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'quotation_request_form_values';
    protected $fillable = ['quote_id', 'form_key', 'form_value', 'form_type'];
    public $timestamps = false;
}
