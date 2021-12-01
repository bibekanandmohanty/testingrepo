<?php
/**
 * This Model used for Backgrounds & Category relationship
 *
 * PHP version 5.6
 *
 * @category  Backgrounds
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Backgrounds\Models;

/**
 * Backgrounds Category Relation
 *
 * @category Backgrounds_Category_Relation
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class BackgroundCategoryRelation extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    public $timestamps = false;
    protected $table = 'background_category_rel';
    protected $fillable = ['background_id', 'category_id'];

    /**
     * Create a relationship of Backgrounds with Backgrounds Category Relation Model
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function category()
    {
        return $this->hasOne(
            'App\Modules\Backgrounds\Models\BackgroundCategory', 
            'xe_id', 'category_id'
        )->select(
            'xe_id', 'name', 'is_disable', 'parent_id'
        );
    }

}
