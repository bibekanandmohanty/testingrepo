<?php
/**
 * ColorPalette Model
 *
 * PHP version 5.6
 *
 * @category  ColorPalettes
 * @package   Assets
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\ColorPalettes\Models;

/**
 * ColorPalette
 *
 * @category ColorPalettes
 * @package  Assets
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */

class ColorPalette extends \Illuminate\Database\Eloquent\Model
{

    protected $table = 'color_palettes';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'name', 'value', 'price', 'category_id', 
        'subcategory_id', 'store_id', 'hex_value',
    ];
    public $timestamps = false;
}
