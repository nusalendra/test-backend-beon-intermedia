<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembayaran extends Model
{
    use HasFactory;
    protected $table = 'pembayarans';
    protected $primarykey = 'id';
    protected $fillable = ['penghuni_id', 'iuran_id','tanggal_pembayaran', 'biaya_pembayaran', 'status_pembayaran'];

    public function penghuni() {
        return $this->belongsTo(Penghuni::class, 'penghuni_id');
    }

    public function iuran() {
        return $this->belongsTo(Iuran::class, 'iuran_id');
    }
}
