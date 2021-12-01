<?php
/**
 * Manage Print Profile
 *
 * PHP version 7.2
 *
 * @category  Print_Profile_Asset_Type
 * @package   Eloquent
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\PrintProfiles\Models;
/**
 * Print Profile's Asset Type Controller
 *
 * @category Class
 * @package  Print_Profile
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class AssetType extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'asset_types';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
}
