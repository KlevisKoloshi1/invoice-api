<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Import extends Model
{
    protected $fillable = [
        'file_path',
        'status',
        'error_message',
        'created_by'
    ];
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
