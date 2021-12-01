<?php
/**
 * Quotation items files
 *
 * PHP version 5.6
 *
 * @category  Quotation_Items_Files
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */

namespace App\Modules\Quotations\Models;

/**
 * Quotation items files
 *
 * @category Quotation_Items_Files
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */

class QuotationItemFiles extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'quote_item_files';
    protected $fillable = ['item_id' , 'side_id', 'decoration_area_id', 'print_method_id', 'file', 'preview_file', 'extra_data','decoration_settings_id'];
    protected $guarded = ['xe_id'];
    public $timestamps = false;

}
