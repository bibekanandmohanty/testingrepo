<?php
/**
 * This Model used for Quotation's Tag and their Relationship
 *
 * PHP version 5.6
 *
 * @category  Quotation
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Quotations\Models;

/**
 * Quotation Tag Relation
 *
 * @category Quotation_Tag
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */
class QuotationTagRelation extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'quote_tag_rel';
    protected $fillable = ['quote_id', 'tag_id'];
    public $timestamps = false;

    /**
     * Create a relationship bridge with Quotation and Tags
     *
     * @author debashrib@riaxe.com
     * @date   4th Nov 2019
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
