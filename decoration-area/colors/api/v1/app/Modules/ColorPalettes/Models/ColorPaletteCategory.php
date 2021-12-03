<?php
/**
 * This Model used for ColorPalette's Categories
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

use Illuminate\Database\Capsule\Manager as DB;

/**
 * ColorPalatte Category
 *
 * @category ColorPalettes
 * @package  Assets
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class ColorPaletteCategory extends \Illuminate\Database\Eloquent\Model
{
    protected $primaryKey = 'xe_id';
    protected $table = 'categories';
    protected $guarded = ['xe_id'];

    /**
     * Get Assets Type ID from module slug name
     *
     * @author debashrib@riaxe.com
     * @date   05 Dec 2019
     * @return asset_type_id
     */
    public function scopeAssetsTypeId()
    {
        if (DB::table('asset_types')->where('slug', '=', 'color-palettes')->count() > 0) {
            $statement = DB::table('asset_types')
                ->where('slug', 'color-palettes')
                ->first();
            return [
                'status' => 1,
                'asset_type_id' => $statement->xe_id,
            ];
        }
        return [
            'status' => 0,
            'message' => 'specific slug not found',
        ];
    }
}
