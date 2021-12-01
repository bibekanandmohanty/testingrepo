<?php
/**
 * Quotation Log
 *
 * PHP version 5.6
 *
 * @category  Quotation_Log
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Quotations\Models;

/**
 * Production Log
 *
 * @category Quotation_Log
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class QuotationLog extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'quote_log';
    protected $fillable = ['quote_id' , 'description', 'user_type', 'user_id', 'created_date'];
    public $timestamps = false;

}
