<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ShoppingItem extends Model
{
    protected $fillable = ['user_id','name','quantity','unit','notes','category','status'];
}