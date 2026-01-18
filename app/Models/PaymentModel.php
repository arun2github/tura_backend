<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
    
    protected $casts = [
        'amount' => 'decimal:2',
        'form_type_id' => 'integer'
    ];
    
    // Override the update method to add logging
    public function update(array $attributes = [], array $options = [])
    {
        Log::info('PaymentModel update called', [
            'model_id' => $this->id,
            'attributes' => $attributes,
            'current_values' => [
                'payment_id' => $this->payment_id,
                'amount' => $this->amount,
                'form_type_id' => $this->form_type_id
            ]
        ]);
        
        $result = parent::update($attributes, $options);
        
        Log::info('PaymentModel update completed', [
            'result' => $result,
            'new_values' => [
                'payment_id' => $this->payment_id,
                'amount' => $this->amount,
                'form_type_id' => $this->form_type_id
            ]
        ]);
        
        return $result;
    }
}
