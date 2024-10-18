<?php

namespace App\Http\Controllers;

use App\Models\Iuran;
use App\Models\Pembayaran;
use App\Models\Penghuni;
use App\Models\Rumah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PembayaranIuranController extends Controller
{
    public function index()
    {
        try {
            $data = Iuran::all();

            $data = $data->map(function ($item) {
                $total_pembayaran_belum_bayar = Pembayaran::where('iuran_id', $item->id)
                    ->where('status_pembayaran', 'Belum Lunas')
                    ->sum('biaya_pembayaran');
                $item->total_pembayaran_belum_bayar = $total_pembayaran_belum_bayar;
                return $item;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar Rumah berhasil didapat',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'iuran.*.nama' => 'required|string',
            'iuran.*.biaya' => 'required|numeric',
            'iuran.*.bulan_tagihan' => 'required|string',
        ], [
            'iuran.*.nama.required' => 'Nama iuran wajib diisi!',
            'iuran.*.biaya.required' => 'Biaya iuran wajib diisi!',
            'iuran.*.bulan_tagihan.required' => 'Bulan tagihan iuran wajib diisi!',
            'iuran.*.tahun_tagihan.required' => 'Tahun tagihan iuran wajib diisi!',
            'iuran.*.nama.string' => 'Nama iuran harus berupa string!',
            'iuran.*.biaya.numeric' => 'Biaya iuran harus berupa numeric!',
            'iuran.*.bulan_tagihan.string' => 'Bulan Tagihan harus berupa string!',
            'iuran.*.tahun_tagihan.string' => 'Tahun Tagihan harus berupa string!',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            foreach ($request->iuran as $iuranData) {
                $iuran = new Iuran();
                $iuran->nama = $iuranData['nama'];
                $iuran->biaya = $iuranData['biaya'];
                $iuran->tanggal_tagihan = $iuranData['tahun_tagihan'] . '-' . $iuranData['bulan_tagihan'] . '-01';
                $iuran->save();

                $penghunis = Penghuni::where('status_penghuni', 'Kontrak')->orWhere('status_penghuni', 'Tetap')->get();
                foreach ($penghunis as $penghuni) {
                    $pembayaran = new Pembayaran();
                    $pembayaran->penghuni_id = $penghuni->id;
                    $pembayaran->iuran_id = $iuran->id;
                    $pembayaran->biaya_pembayaran = $iuran->biaya;
                    $pembayaran->save();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Iuran berhasil ditambahkan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Penambahan pembayaran iuran gagal: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function bayarTagihanBulanan(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'iuran_id',
            'tanggal_pembayaran' => 'required|date',
        ], [
            'iuran_id.required' => 'Iuran ID wajib diisi!',
            'iuran_id.exists' => 'Iuran ID tidak ditemukan!',
            'tanggal_pembayaran.required' => 'Tanggal pembayaran wajib diisi!',
            'tanggal_pembayaran.date' => 'Tanggal pembayaran harus berupa date!',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $rumah = Rumah::with([
                'penghuni' => function ($query) {
                    $query->where('status_penghuni', 'Tetap')
                        ->orWhere('status_penghuni', 'Kontrak');
                }
            ])->find($id);

            foreach ($rumah->penghuni as $penghuniData) {
                $penghuni = Penghuni::with([
                    'pembayaran' => function ($query) use ($request) {
                        $query->where('status_pembayaran', 'Belum Lunas')
                            ->where('iuran_id', $request->iuran_id);
                    }
                ])->find($penghuniData->id);

                foreach ($penghuni->pembayaran as $pembayaran) {
                    $pembayaran->tanggal_pembayaran = $request->tanggal_pembayaran;
                    $pembayaran->status_pembayaran = 'Lunas';
                    $pembayaran->save();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran iuran berhasil diselesaikan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pembayaran iuran gagal diselesaikan: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function bayarTagihanTahunan(Request $request) {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string',
            'tanggal_pembayaran' => 'required|date',
            'tagihan.*.tanggal_tagihan' => 'required|date'
        ], [
            'nama.required' => 'Nama wajib diisi!',
            'nama.string' => 'Nama harus berupa string!',
            'tanggal_pembayaran.required' => 'Tanggal pembayaran wajib diisi!',
            'tanggal_pembayaran.date' => 'Tanggal pembayaran harus berupa date!',
            'tagihan.*.tanggal_tagihan.required' => 'Tagihan wajib diisi!',
            'tagihan.*.tanggal_tagihan.date' => 'Tagihan harus berupa date!',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $namaIuran = $request->nama;
        
            $tanggalTagihan = $request->input('tagihan.*.tanggal_tagihan');
        
            $iuranData = Iuran::where('nama', $namaIuran)
                          ->whereIn('tanggal_tagihan', $tanggalTagihan)
                          ->with('pembayaran')
                          ->get();
        
            if ($iuranData->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ditemukan iuran dengan nama dan tanggal tagihan yang sesuai',
                    'data' => []
                ], 404);
            }
        
            foreach ($iuranData as $iuran) {
                foreach ($iuran->pembayaran as $pembayaran) {
                    $pembayaran->status_pembayaran = 'Lunas';
                    $pembayaran->tanggal_pembayaran = $request->tanggal_pembayaran;
                    $pembayaran->save();
                }
            }
        
            return response()->json([
                'status' => 'success',
                'message' => 'Pembayaran iuran tahunan berhasil diselesaikan'
            ]);
        
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Pembayaran iuran tahunan gagal diselesaikan: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
        
    }
}
