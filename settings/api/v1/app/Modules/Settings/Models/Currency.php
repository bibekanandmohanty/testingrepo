<?php
/**
 * Currency Model
 *
 * PHP version 5.6
 *
 * @category  Currencies
 * @package   Settings
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Settings\Models;
/**
 * Currency
 *
 * @category Currency
 * @package  Settings
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class Currency extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'app_currency';
    protected $primaryKey = 'xe_id';
    protected $fillable = ['name', 'symbol', 'code', 'is_default', 'unicode_character'];
}
