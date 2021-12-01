<?php
/**
 * Augmented Reality Model
 *
 * PHP version 5.6
 *
 * @category  Augmented_Reality
 * @package   Add-on
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\AugmentedRealities\Models;;

/**
 * Augmented Reality Class
 *
 * @category Augmented_Reality
 * @package  Add-on
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class AugmentedReality extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'augmented_realities';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
}
