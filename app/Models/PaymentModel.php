<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentModel extends Model
{
    use HasFactory;
    protected $table = 'payment_details';
    
    protected $fillable = [
        'order_id',
        'request_body',
        'form_id'
    ];
}
