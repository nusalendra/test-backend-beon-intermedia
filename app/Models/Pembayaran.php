<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;
    protected $table = 'pembayarans';
    protected $primarykey = 'id';
    protected $fillable = ['rumah_id', 'tanggal_pembayaran', 'jumlah_pembayaran', 'status_pembayaran'];

    public function rumah() {
        return $this->belongsTo(Rumah::class, 'rumah_id');
    }
}
