<?php
/**
 * This Model used for Production Job Notes
 *
 * PHP version 5.6
 *
 * @category  Production_Job_Notes
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */
namespace App\Modules\Productions\Models;
/**
 * Production Job Notes
 *
 * @category Production_Job_Notes
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */
class ProductionJobNotes extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'production_job_notes';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'job_id','note', 'user_type', 'user_id','seen_flag','created_date'
    ];
    public $timestamps = false;

    /**
     * Get Internal Note Files
     *
     * @author debasrib@riaxe.com
     * @date   03 Oct 2020
     * @return file relation object
     */
    public function files()
    {
        return $this->hasMany(
            'App\Modules\Productions\Models\ProductionJobNoteFiles', 'note_id'
        );
    }
}
