<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rumah extends Model
{
    use HasFactory;
    protected $table = 'rumahs';
    protected $primarykey = 'id';
    protected $fillable = ['alamat', 'status_rumah'];

    public function penghuni() {
        return $this->hasMany(Penghuni::class, 'rumah_id');
    }

    public function pembayaran() {
        return $this->hasMany(Pembayaran::class, 'rumah_id');
    }

    public function historyRumah() {
        return $this->hasMany(HistoryRumah::class, 'rumah_id');
    }
}
