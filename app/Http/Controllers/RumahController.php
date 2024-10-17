<?php

namespace App\Http\Controllers;

use App\Models\Penghuni;
use App\Models\Rumah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RumahController extends Controller
{
    public function index()
    {
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
                'message' => 'Penambahan data rumah berhasil'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Penambahan data rumah gagal: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $data = Rumah::with(['penghuni' => function ($query) {
                $query->where('status_penghuni', 'Tetap');
                $query->orWhere('status_penghuni', 'Kontrak');
            }])->find($id);

            foreach ($data->penghuni as $penghuni) {
                $penghuni->foto_ktp = url('storage/' . $penghuni->foto_ktp);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar Rumah berdasarkan id berhasil didapat',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            $data = Rumah::with(['penghuni' => function ($query) {
                $query->select('id', 'rumah_id', 'nama_lengkap', 'status_penghuni', 'nomor_telepon', 'status_menikah');
                $query->where('status_penghuni', 'Tetap');
                $query->orWhere('status_penghuni', 'Kontrak');
            }])->find($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar Rumah berdasarkan id berhasil didapat',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'alamat' => 'string',
            'status_rumah' => 'string',
            'penghuni.*.nama_lengkap' => 'string',
            'penghuni.*.status_penghuni' => 'string',
            'penghuni.*.nomor_telepon' => 'string',
            'penghuni.*.status_menikah' => 'string',
            'penghuni.*.foto_ktp' => 'file|mimes:jpeg,png,jpg'
        ], [
            'alamat.string' => 'Alamat harus berupa string!',
            'status_rumah.string' => 'Status Rumah harus berupa string!',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $rumah = Rumah::find($id);
            $rumah->alamat = $request->alamat ?? $rumah->alamat;
            $rumah->status_rumah = $request->status_rumah ?? $rumah->status_rumah;
            $rumah->save();

            $penghuniIdsInRequest = collect($request->penghuni)->pluck('id')->filter()->toArray();
            $existingPenghuni = Penghuni::where('rumah_id', $rumah->id)->get();
            if ($request->status_rumah == 'Dihuni') {
                foreach ($request->penghuni as $index => $penghuniData) {
                    if (!empty($penghuniData['id'])) {
                        $penghuni = Penghuni::find($penghuniData['id']);
                        if ($penghuni) {
                            continue;
                        }
                    }

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
                }

                foreach ($existingPenghuni as $penghuni) {
                    if (!in_array($penghuni->id, $penghuniIdsInRequest)) {
                        if ($penghuni->foto_ktp) {
                            Storage::disk('public')->delete($penghuni->foto_ktp);
                        }
                        $penghuni->delete();
                    }
                }
            } elseif ($request->status_rumah == 'Tidak Dihuni') {
                foreach ($existingPenghuni as $penghuni) {
                    if (!in_array($penghuni->id, $penghuniIdsInRequest)) {
                        if ($penghuni->foto_ktp) {
                            Storage::disk('public')->delete($penghuni->foto_ktp);
                        }
                        $penghuni->delete();
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data rumah dan penghuni berhasil diupdate',
                'data'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Update data rumah gagal: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }
}
