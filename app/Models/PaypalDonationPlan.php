<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** A PayPal billing plan for monthly donations in one currency (see PayPalDonationService::planIdFor). */
class PaypalDonationPlan extends Model
{
    protected $fillable = ['mode', 'currency', 'product_id', 'plan_id'];
}
