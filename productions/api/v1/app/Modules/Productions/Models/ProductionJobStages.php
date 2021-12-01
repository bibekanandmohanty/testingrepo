<?php
/**
 * This Model used for Production Job Stages
 *
 * PHP version 5.6
 *
 * @category  Production_Job_Stages
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */
namespace App\Modules\Productions\Models;
/**
 * Production Jobs Stages
 *
 * @category Production_Job_Stages
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */
class ProductionJobStages extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'production_job_stages';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'job_id','print_method_id', 'stages_id', 'stage_name', 'stage_color_code', 'created_date', 'starting_date','exp_completion_date','completion_date','status','message'
    ];
    public $timestamps = false;
}
