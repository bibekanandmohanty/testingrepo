<?php
/**
 * Template Design State Model
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
 * Template Design State Model Class
 *
 * @category Template
 * @package  Template
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class TemplateDesignStates extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'design_states';
    protected $primaryKey = 'xe_id';
    protected $guarded = ['xe_Id'];
    public $timestamps = false;
    /**
     * Reverse One-to-one relationship between Template and Product-Settings
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function productSetting()
    {
        return $this->hasOne(
            'App\Modules\Products\Models\ProductSetting', 
            'xe_id', 'product_setting_id'
        );
    }
}
