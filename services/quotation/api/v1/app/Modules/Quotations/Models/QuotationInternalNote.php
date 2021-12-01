<?php
/**
 * Quotation Internal Log
 *
 * PHP version 5.6
 *
 * @category  Quotation__Internal_Log
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */
namespace App\Modules\Quotations\Models;

/**
 * Production Log
 *
 * @category Quotation__Internal_Log
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class QuotationInternalNote extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'quote_internal_note';
    protected $fillable = ['quote_id' , 'note', 'user_type', 'user_id', 'created_date'];
    public $timestamps = false;

    /**
     * Get Internal Note Files
     *
     * @author debasrib@riaxe.com
     * @date   26 May 2020
     * @return file relation object
     */
    public function files()
    {
        return $this->hasMany(
            'App\Modules\Quotations\Models\QuotationInternalNoteFiles', 'note_id'
        );
    }

}
