<?php
/**
 * Production status
 *
 * PHP version 5.6
 *
 * @category  Production_Status
 * @package   Production_Hub
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */

namespace App\Modules\Productions\Models;

/**
 * Production Satus
 *
 * @category Production_Status
 * @package  Production_Hub
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class StatusFeatures extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'production_status_features';
    public $timestamps = false;
    protected $fillable = [
        'status_id',
        'duration',
        'is_global',
        'is_group'
    ];
}
