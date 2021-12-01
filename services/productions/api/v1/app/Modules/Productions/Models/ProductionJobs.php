<?php
/**
 * This Model used for Production Jobs
 *
 * PHP version 5.6
 *
 * @category  Production_Jobs
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */
namespace App\Modules\Productions\Models;
/**
 * Production Jobs
 *
 * @category Production_Jobs
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */
class ProductionJobs extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'production_jobs';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'store_id', 'job_id','order_id', 'order_item_id', 'order_item_quantity', 'job_title', 'job_status', 'note', 'comp_percentage', 'due_date', 'scheduled_date', 'created_at', 'current_stage_id'
    ];
    public $timestamps = false;
}
