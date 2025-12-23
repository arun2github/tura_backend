<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentModel extends Model
{
    use HasFactory;
    protected $table = 'payment_details';
    
    protected $fillable = [
        'form_id',
        'payment_id',
        'order_id',
        'amount',
        'status',
        'request_body',
        'response_body',
        'form_type_id'
    ];
}
