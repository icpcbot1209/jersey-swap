<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Escrow extends Model
{
    use HasFactory;
    protected $table="escrow";
    protected $fillable = [
        'amount',
        'deal_id',
        'given_to',
        'given_by',
        'status'
    ];
}
