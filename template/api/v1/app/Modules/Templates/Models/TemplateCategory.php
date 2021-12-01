<?php
/**
 * Template Category Model
 *
 * PHP version 5.6
 *
 * @category  Template
 * @package   Template
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Templates\Models;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Template Category Model Class
 *
 * @category Template
 * @package  Template
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class TemplateCategory extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'categories';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_id'];
    public $timestamps = false;
}
