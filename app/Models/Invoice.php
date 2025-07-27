<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $fillable = [
        'client_id',
        'invoice_number',
        'invoice_date',
        'total_without_tax',
        'total_tax',
        'total_with_tax',
        'created_by',
        'import_id',
        'fiscal_status',
        'fiscal_response'
    ];
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function import()
    {
        return $this->belongsTo(Import::class);
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
