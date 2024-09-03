<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory, Notifiable;

    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id_customer');
    }

    public function driver()
    {
        return $this->belongsTo(User::class, 'user_id_driver');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }
}
