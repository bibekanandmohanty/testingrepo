<?php
/**
 * This Model used for Clipart's Tag and their Relationship
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
 * Clipart Tag Relation
 *
 * @category Cliparts_Tag
 * @package  Assets
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ClipartTagRelation extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'clipart_tag_rel';
    protected $fillable = ['clipart_id', 'tag_id'];
    public $timestamps = false;

    /**
     * Create a relationship bridge with Clipart and Tags
     *
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function tag()
    {
        return $this->hasOne(
            'App\Components\Models\Tag', 'xe_id', 'tag_id'
        )->select(
            'xe_id', 'name'
        );
    }
}
