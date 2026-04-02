<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_name',
        'category_name',
        'amount',
        'due_date',
        'status',
        'paid_at'
    ];
}