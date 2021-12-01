<?php
/**
 * Production hub Setting
 *
 * PHP version 5.6
 *
 * @category  Production_Hub_Setting
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */

namespace App\Modules\Productions\Models;

/**
 * Production Hub Setting
 *
 * @category Production_Hub_Setting
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */

class ProductionHubSetting extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'production_hub_settings';
    protected $fillable = ['store_id', 'module_id', 'setting_key', 'setting_value', 'flag'];
    public $timestamps = false;

}
