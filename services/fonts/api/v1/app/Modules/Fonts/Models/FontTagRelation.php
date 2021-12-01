<?php
/**
 * This Model used for Font's Tag and their Relationship
 *
 * PHP version 5.6
 *
 * @category  Fonts
 * @package   Assets
 * @author    Satyabrata <satyabratap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\Fonts\Models;

/**
 * Fonts Tag Relation
 *
 * @category Fonts_Tag
 * @package  Assets
 * @author   Satyabrata <satyabratap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class FontTagRelation extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'font_tag_rel';
    protected $fillable = ['font_id', 'tag_id'];
    public $timestamps = false;

    /**
     * Create a relationship bridge with Fonts and Tags
     *
     * @author satyabratap@riaxe.com
     * @date   4th Nov 2019
     * @return relationship object of category
     */
    public function tag()
    {
        return $this->hasOne('App\Components\Models\Tag', 'xe_id', 'tag_id')
            ->select('xe_id', 'name');
    }

}
