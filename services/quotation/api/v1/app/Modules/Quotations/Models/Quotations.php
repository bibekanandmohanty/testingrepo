<?php
/**
 * Quotations Model
 *
 * PHP version 5.6
 *
 * @category  Quotations
 * @package   Production_Hub
 * @author    Debashri Bhakat <debashrib@riaxe.com>
 * @copyright 2019-2020 Riaxe Systems
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 */

namespace App\Modules\Quotations\Models;

use App\Components\Controllers\Component as ParentController;

/**
 * Quotations
 *
 * @category Quotations
 * @package  Production_Hub
 * @author   Debashri Bhakat <debashrib@riaxe.com>
 * @license  http://www.gnu.org/copyleft/gpl.html GNU General Public License
 */

class Quotations extends \Illuminate\Database\Eloquent\Model
{

    protected $primaryKey = 'xe_id';
    protected $fillable = ['store_id', 'quote_id' , 'customer_id', 'shipping_id', 'agent_id', 'created_by', 'created_by_id', 'quote_source', 'title', 'description', 'ship_by_date', 'exp_delivery_date', 'is_artwork', 'is_rush', 'rush_type', 'rush_amount', 'discount_type', 'discount_amount', 'shipping_type','shipping_amount', 'tax_amount', 'design_total', 'quote_total', 'status_id', 'note', 'draft_flag', 'reject_note', 'invoice_id', 'request_payment', 'request_date', 'customer_name', 'customer_email', 'customer_availability', 'is_ready_to_send', 'quotation_request_id'];

    /**
     * Create relationship between Quotation and
     * Production Status
     *
     * @author debashrib@riaxe.com
     * @date   24th Mar 2019
     * @return relationship object of status
     */
    public function status()
    {
        return $this->hasOne(
            'App\Modules\Quotations\Models\ProductionStatus', 
            'xe_id', 'status_id'
        )->select(
            'status_name', 'color_code'
        );
        
    }

    /**
     * Create One-to-Many relationship between Quotation and
     * Quotation-Tag-Relationship
     *
     * @author debashrib@riaxe.com
     * @date   21 Apr 2019
     * @return relationship object of tag
     */
    public function quotationTag()
    {
        return $this->hasMany(
            'App\Modules\Quotations\Models\QuotationTagRelation',
            'quote_id'
        );
    }
}
