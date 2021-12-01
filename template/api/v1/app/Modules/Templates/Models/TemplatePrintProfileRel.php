<?php
/**
 * Template Print Profile Relation Model
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

/**
 * Template Print Profile Relation Model Class
 *
 * @category Template
 * @package  Template
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class TemplatePrintProfileRel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'template_print_profile_rel';
    protected $fillable = ['template_id', 'print_profile_id'];
    public $timestamps = false;
}
