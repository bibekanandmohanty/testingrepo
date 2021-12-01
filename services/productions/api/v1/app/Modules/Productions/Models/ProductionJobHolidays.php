<?php
/**
 * Production Job Holidays list
 *
 * PHP version 5.6
 *
 * @category  Production_Hub_Setting
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */

namespace App\Modules\Productions\Models;

/**
 * Production Job Holidays list
 *
 * @category Production_Hub_Setting
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class ProductionJobHolidays extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'production_job_holidays';
    protected $fillable = ['store_id', 'holiday_name', 'day', 'date'];
    public $timestamps = false;

}
