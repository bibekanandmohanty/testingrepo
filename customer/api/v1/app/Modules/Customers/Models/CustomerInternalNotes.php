<?php
/**
 * This Model used for Customer Internal Notes
 *
 * PHP version 5.6
 *
 * @category  Customer_Internal_Notes
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */
namespace App\Modules\Customers\Models;
/**
 * Customer Internal Notes
 *
 * @category Customer_Internal_Notes
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */
class CustomerInternalNotes extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'customer_internal_notes';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'store_id', 'customer_id', 'title', 'note', 'user_type', 'user_id', 'seen_flag', 'created_date'
    ];
    public $timestamps = false;

    /**
     * Get Internal Note Files
     *
     * @author debasrib@riaxe.com
     * @date   24 Feb 2021
     * @return file relation object
     */
    public function files()
    {
        return $this->hasMany(
            'App\Modules\Customers\Models\CustomerInternalNoteFiles', 'note_id'
        );
    }
}
