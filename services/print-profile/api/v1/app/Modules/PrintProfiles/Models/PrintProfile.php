<?php
/**
 * This Model used for Print profile
 *
 * PHP version 5.6
 *
 * @category  Print_Profile
 * @package   Print_Profile
 * @author    Tanmaya Patra <tanmayap@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @link      http://inkxe-v10.inkxe.io/xetool/admin
 */

namespace App\Modules\PrintProfiles\Models;

/**
 * Feature Class
 *
 * @category Print_Profile
 * @package  Print_Profile
 * @author   Tanmaya Patra <tanmayap@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @link     http://inkxe-v10.inkxe.io/xetool/admin
 */
class PrintProfile extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'print_profiles';
    protected $primaryKey = 'xe_id';
    protected $fillable = [
        'store_id',
        'name',
        'file_name',
        'description',
        'status',
        'is_vdp_enabled',
        'vdp_data',
        'is_laser_engrave_enabled',
        'image_settings',
        'color_settings',
        'order_settings',
        'text_settings',
        'bg_settings',
        'name_number_settings',
        'is_disabled',
        'is_draft',
    ];
    protected $appends = ['file_name_url', 'thumbnail', 'is_decoration_exists'];
    public $timestamps = false;

    /**
     * Create a relationship of Print Profile with Pricing
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function pricing()
    {
        return $this->hasMany(
            'App\Modules\PrintProfiles\Models\Pricing\PrintProfilePricing', 
            'print_profile_id', 
            'xe_id'
        );
    }
    /**
     * Create a relationship of Print Profile with Print Profile Feature Relation
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function features()
    {
        return $this->hasMany(
            'App\Modules\PrintProfiles\Models\PrintProfileFeatureRel', 
            'print_profile_id', 
            'xe_id'
        );
    }
    /**
     * Create a relationship of Print Profile with Print Profile Assets Category
     * Relation
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function assets()
    {
        return $this->hasMany(
            'App\Modules\PrintProfiles\Models\PrintProfileAssetsCategoryRel', 
            'print_profile_id', 
            'xe_id'
        );
    }
    /**
     * Create a relationship of Print Profile with Print Profile Engrave Setting
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function engraves()
    {
        return $this->hasOne(
            'App\Modules\PrintProfiles\Models\PrintProfileEngraveSetting', 
            'print_profile_id', 
            'xe_id'
        );
    }
    /**
     * Create a relationship of Print Profile with Print Profile Template Setting
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function templates()
    {
        return $this->hasMany(
            'App\Modules\Templates\Models\TemplatePrintProfileRel', 
            'print_profile_id'
        );
    }
    /**
     * Create a relationship of Print Profile with Print Profile Decoration Setting
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function decorations()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\PrintProfileDecorationSettingRel', 
            'print_profile_id'
        );
    }
    /**
     * Check if Print prfofile ID has any Decoration
     *
     * @author tanmayap@riaxe.com
     * @date   01 Apr 2020
     * @return integer
     */
    public function getIsDecorationExistsAttribute()
    {
        $printProfileId = $this->attributes['id'];
        $profileProdDecoSettObj = new \App\Modules\Products\Models\PrintProfileProductSettingRel();
        $profileProdDecoSett = $profileProdDecoSettObj->where('print_profile_id', $printProfileId)
            ->count();
        if (!empty($profileProdDecoSett)) {
            return 1;
        }
        
        return 0;
    }
    /**
     * Create a relationship of Print Profile with Print Profile Products Setting
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function products()
    {
        return $this->hasMany(
            'App\Modules\Products\Models\PrintProfileProductSettingRel', 
            'print_profile_id'
        );
    }

    /**
     * Create Many-to-Many relationship of Print profile with Assets
     * Used for Sync
     * 
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function assets_relation()
    {
        return $this->belongsToMany(
            'App\Modules\PrintProfiles\Models\PrintProfileAssetsCategoryRel', 
            'print_profile_assets_category_rel', 
            'print_profile_id', 'category_id'
        );
    }

    /**
     * Create Many-to-Many relationship of Print profile with Product-Settings
     * Used for Sync
     * 
     * @author tanmayap@riaxe.com
     * @date   5 Oct 2019
     * @return relationship object of category
     */
    public function products_relation()
    {
        return $this->belongsToMany(
            'App\Modules\Products\Models\PrintProfileProductSettingRel', 
            'print_profile_product_setting_rel', 
            'print_profile_id', 'product_setting_id'
        );
    }
    /**
     * Get file name data
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function filename()
    {
        return $this->attributes['file_name'];
    }
    /**
     * Getting full url from file_name by manipulate file_name value
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function getFileNameUrlAttribute()
    {
        $url = "";
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            $url .= path('read', 'print_profile');
            $url .= $this->attributes['file_name'];
        }
        
        return $url;
    }
    /**
     * Getting full thumb url from file_name by manipulate file_name value
     *
     * @author tanmayap@riaxe.com
     * @date   14 Aug 2019
     * @return relationship object of category
     */
    public function getThumbnailAttribute()
    {
        $url = "";
        if (isset($this->attributes['file_name']) 
            && $this->attributes['file_name'] != ""
        ) {
            if (in_array(strtoupper(pathinfo($this->attributes['file_name'], PATHINFO_EXTENSION)), ['JPEG', 'JPG', 'GIF', 'WEBP', 'PNG'])) {
                $url .= path('read', 'print_profile');
                $url .= 'thumb_' . $this->attributes['file_name'];
            } else {
                $url .= path('read', 'print_profile');
                $url .= $this->attributes['file_name'];
            }
        }

        return $url;
    }
}
