<?php
/**
 * Production Email Template
 *
 * PHP version 5.6
 *
 * @category  Production_Email_Template
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://imprintnext.io
 */

namespace App\Modules\Productions\Models;

/**
 * Production Email Template
 *
 * @category Production_Email_Template
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://imprintnext.io
 */

class ProductionEmailTemplates extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'production_email_templates';
    protected $fillable = ['store_id', 'template_type_name', 'subject', 'message', 'is_configured'];
    public $timestamps = false;

}
