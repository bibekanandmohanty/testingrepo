<?php
/**
 * Quotation Payment Model
 *
 * PHP version 5.6
 *
 * @category  Quotation_Payment
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */

namespace App\Modules\Quotations\Models;

use App\Components\Controllers\Component as ParentController;

/**
 * Quotation Payment
 *
 * @category Quotation_Payment
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class QuotationPayment extends \Illuminate\Database\Eloquent\Model
{

    protected $primaryKey = 'xe_id';
    protected $table = 'quote_payments';
    protected $fillable = ['quote_id', 'payment_amount' , 'txn_id', 'payment_date', 'payment_mode', 'payment_status','note'];
    public $timestamps = false;
}
