<?php
/**
 * This Model used for Production Job Log
 *
 * PHP version 5.6
 *
 * @category  Production_Job_Log
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */
namespace App\Modules\Productions\Models;
/**
 * Production Jobs Log
 *
 * @category Production_Job_Log
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */
class ProductionJobLog extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'production_job_log';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'job_id', 'title', 'description', 'user_type', 'user_id', 'created_date'
    ];
    public $timestamps = false;
}
