<?php
/**
 * Distress Category Model
 *
 * PHP version 5.6
 *
 * @category  Distress
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Distresses\Models;

/**
 * Distress Category
 *
 * @category Distress
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class DistressCategory extends \Illuminate\Database\Eloquent\Model
{

    protected $primaryKey = 'xe_id';
    protected $table = 'categories';
    protected $guarded = ['xe_id'];
}
