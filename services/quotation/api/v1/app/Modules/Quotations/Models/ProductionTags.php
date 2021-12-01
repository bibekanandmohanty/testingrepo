<?php
/**
 * Production tag
 *
 * PHP version 5.6
 *
 * @category  Production_Tag
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Quotations\Models;

/**
 * Production Satus
 *
 * @category Production_Tag
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class ProductionTags extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'production_tags';
    protected $guarded = ['xe_id'];
    public $timestamps = false;

}
