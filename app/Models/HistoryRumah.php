<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryRumah extends Model
{
    use HasFactory;
    protected $table = 'history_rumahs';
    protected $primarykey = 'id';
    protected $fillable = ['rumah_id', 'penghuni_id', 'tnaggal_mulai penghunian', 'tanggal_akhir_penghunian'];

    public function rumah() {
        return $this->belongsTo(Rumah::class, 'rumah_id');
    }

    public function penghuni() {
        return $this->belongsTo(Penghuni::class, 'penghuni_id');
    }
}
