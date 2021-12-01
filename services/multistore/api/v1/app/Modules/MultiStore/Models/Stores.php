<?php
/**
 * MultiStore
 *
 * PHP version 5.6
 *
 * @category  MultiStore
 * @package   Multi_Store
 * @author    Soumya Swain <soumyas@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\MultiStore\Models;

/**
 * Store
 *
 * @category MultiStore
 * @package  Multi_Store
 * @author   Soumya Swain <soumyas@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class Stores extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'stores';
    protected $fillable = ['store_name', 'store_url', 'created_date', 'status', 'settings'];
    public $timestamps = false;
}
