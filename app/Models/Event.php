<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Category;
use App\Models\User;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul',
        'deskripsi',
        'tanggal_waktu',
        'lokasi',
        'kategori_id',
        'gambar',
        'user_id',
    ];

    protected $casts = [
        'tanggal_waktu' => 'datetime',
    ];

    public function kategori()
    {
        return $this->belongsTo(Category::class, 'kategori_id');
    }

    public function tickets()
    {
        return $this->hasMany(Tiket::class, 'event_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}