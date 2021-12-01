<?php
/**
 * This Model used for Clipart & Category relationship
 *
 * PHP version 5.6
 *
 * @category  Cliparts
 * @package   Assets
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */
namespace App\Modules\Cliparts\Models;

/**
 * Clipart Category
 *
 * @category Cliparts_Category_Relation
 * @package  Assets
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ClipartCategoryRelation extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    public $timestamps = false;
    protected $table = 'clipart_category_rel';
    protected $fillable = ['clipart_id', 'category_id'];
    
    /**
     * Create a relationship of Clipart with Clipart Category Relation Model
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function category()
    {
        return $this->hasOne(
            'App\Modules\Cliparts\Models\ClipartCategory', 'xe_id', 'category_id'
        )->select(
            'xe_id', 'name', 'parent_id', 'is_disable'
        );
    }
}
