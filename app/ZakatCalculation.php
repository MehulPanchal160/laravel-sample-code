<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ZakatCalculation extends Model {

    /**
     * Table name.
     * @var string
     */
    protected $table = 'zakat_calculation';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'cash_asset_items',
        'cash_asset_amount',
        'shares_asset_items',
        'shares_asset_amount',
        'debts_asset_items',
        'debts_asset_amount',
        'gold_asset_items',
        'gold_asset_amount',
        'property_asset_items',
        'property_asset_amount',
        'business_asset_items',
        'business_asset_amount',
        'silver_asset_items',
        'silver_asset_amount',
        'pension_asset_items',
        'pension_asset_amount',
        'personal_liability_items',
        'personal_liability_amount',
        'business_liability_items',
        'business_liability_amount',
        'total_assets',
        'total_liabilities',
        'payable_amount',
        'created_at',
        'updated_at'
    ];

    /**
     * The users that belong to the zakat calculation.
     */
    public function users() {
        return $this->belongsTo('App\User', 'user_id');
    }

}
