<?php

namespace App\Http\Controllers;

use App\Models\Penghuni;
use App\Models\Rumah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RumahController extends Controller
{
    public function index() {
        try {
            $data = Rumah::all();

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
            'alamat' => 'required|string',
            'status_rumah' => 'required|string',
            'penghuni.*.nama_lengkap' => 'required|string',
            'penghuni.*.status_penghuni' => 'required|string',
            'penghuni.*.nomor_telepon' => 'required|string',
            'penghuni.*.status_menikah' => 'required|string',
            'penghuni.*.foto_ktp' => 'required|file|mimes:jpeg,png,jpg'
        ], [
            'alamat.required' => 'Alamat wajib diisi!',
            'alamat.string' => 'Alamat harus berupa string!',
            'status_rumah.required' => 'Status Rumah wajib diisi!',
            'status_rumah.string' => 'Status Rumah harus berupa string!',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $rumah = new Rumah();
            $rumah->alamat = $request->alamat;
            $rumah->status_rumah = $request->status_rumah;
            $rumah->save();

            $penghuniArr = [];
            if ($request->status_rumah == 'Dihuni') {
                foreach ($request->penghuni as $index => $penghuniData) {
                    $penghuni = new Penghuni();
                    $penghuni->rumah_id = $rumah->id;
                    $penghuni->nama_lengkap = $penghuniData['nama_lengkap'];
                    $penghuni->status_penghuni = $penghuniData['status_penghuni'];
                    $penghuni->nomor_telepon = $penghuniData['nomor_telepon'];
                    $penghuni->status_menikah = $penghuniData['status_menikah'];
                    if ($request->hasFile("penghuni.$index.foto_ktp")) {
                        $filePath = $request->file("penghuni.$index.foto_ktp")->store('ktp', 'public');
                        $penghuni->foto_ktp = $filePath;
                    }
                    $penghuni->save();

                    $penghuniArr[] = [
                        'nama_lengkap' => $penghuni->nama_lengkap,
                        'status_penghuni' => $penghuni->status_penghuni,
                        'nomor_telepon' => $penghuni->nomor_telepon,
                        'status_menikah' => $penghuni->status_menikah
                    ];
                }
            }
            return response()->json([
                'status' => 'success',
                'message' => 'Penambahan data rumah berhasil',
                'data' => [
                    'rumah' => $rumah,
                    'penghuni' => $penghuniArr
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Penambahan data rumah gagal: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
