<?php
/**
 * This Model used for Distress & Category relationship
 *
 * PHP version 5.6
 *
 * @category  Distresss
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Distresses\Models;

/**
 * Distress Category Relation
 *
 * @category Distresses_Category_Relation
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class DistressCategoryRelation extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    public $timestamps = false;
    protected $table = 'distress_category_rel';
    protected $fillable = ['distress_id', 'category_id'];
    
    /**
     * Create a relationship of Distress with Distress Category Relation Model
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function category()
    {
        return $this->hasOne(
            'App\Modules\Distresses\Models\DistressCategory', 'xe_id', 'category_id'
        )->select(
            'xe_id', 'name', 'parent_id', 'is_disable'
        );
    }
}
