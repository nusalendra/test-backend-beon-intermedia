<?php

namespace App\Http\Controllers;

use App\Models\HistoryRumah;
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
            $data = Rumah::with('penghuni.pembayaran')->get();

            $data = $data->map(function ($rumah) {
                $totalBelumBayar = 0;

                foreach ($rumah->penghuni as $penghuni) {
                    $totalBelumBayar += $penghuni->pembayaran
                        ->where('status_pembayaran', 'Belum Lunas')
                        ->sum('biaya_pembayaran');
                }

                $rumah->setAttribute('total_belum_bayar', $totalBelumBayar);

                return $rumah;
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
            'alamat' => 'required|string',
            'status_rumah' => 'required|string',
            'tanggal_mulai_huni' => 'date',
            'tanggal_akhir_huni' => 'date',
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

                    $historyRumah = new HistoryRumah();
                    $historyRumah->rumah_id = $rumah->id;
                    $historyRumah->penghuni_id = $penghuni->id;
                    $historyRumah->tanggal_mulai_huni = $request->tanggal_mulai_huni;
                    $historyRumah->tanggal_akhir_huni = $request->tanggal_akhir_huni;
                    $historyRumah->save();
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
            }, 'historyRumah' => function ($query) {
                $query->select('id', 'rumah_id', 'tanggal_mulai_huni', 'tanggal_akhir_huni')
                    ->limit(1);
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
            $data = Rumah::with([
                'penghuni' => function ($query) {
                    $query->select('id', 'rumah_id', 'nama_lengkap', 'status_penghuni', 'nomor_telepon', 'status_menikah')
                        ->where('status_penghuni', 'Tetap')
                        ->orWhere('status_penghuni', 'Kontrak');
                },
                'historyRumah' => function ($query) {
                    $query->select('id', 'rumah_id', 'tanggal_mulai_huni', 'tanggal_akhir_huni')
                        ->limit(1);
                }
            ])->find($id);

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
        // Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'alamat' => 'string',
            'status_rumah' => 'string',
            'tanggal_mulai_huni' => 'date',
            'tanggal_akhir_huni' => 'date',
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

            if ($request->status_rumah == 'Dihuni') {
                foreach ($request->penghuni as $index => $penghuniData) {
                    if (!empty($penghuniData['id'])) {
                        $penghuni = Penghuni::find($penghuniData['id']);
                        if ($penghuni) {
                            $historyRumah = HistoryRumah::where('penghuni_id', $penghuni->id)->first();
                            $historyRumah->tanggal_mulai_huni = $request->tanggal_mulai_huni ?? $historyRumah->tanggal_mulai_huni;
                            $historyRumah->tanggal_akhir_huni = $request->tanggal_akhir_huni ?? $historyRumah->tanggal_akhir_huni;
                            $historyRumah->save();

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

                    $historyRumah = new HistoryRumah();
                    $historyRumah->rumah_id = $rumah->id;
                    $historyRumah->penghuni_id = $penghuni->id;
                    $historyRumah->tanggal_mulai_huni = $request->tanggal_mulai_huni;
                    $historyRumah->tanggal_akhir_huni = $request->tanggal_akhir_huni;
                    $historyRumah->save();
                }
            } elseif ($request->status_rumah == 'Tidak Dihuni') {
                $penghunis = Penghuni::where('rumah_id', $rumah->id)->get();
                foreach ($penghunis as $penghuni) {
                    $penghuni->status_penghuni = 'Keluar';
                    $penghuni->save();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data rumah dan penghuni berhasil diupdate'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Update data rumah gagal: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function perubahanKepemilikan($id)
    {
        try {
            $rumah = Rumah::find($id);
            foreach ($rumah->penghuni as $penghuni) {
                $penghuni = Penghuni::find($penghuni->id);

                if ($penghuni) {
                    $penghuni->status_penghuni = 'Keluar';
                    $penghuni->save();
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Perubahan kepemilikan berhasil'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Perubahan kepemilikan gagal diperbarui: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }

    public function historicalPenghuni($id)
    {
        try {
            $data = Rumah::with(['penghuni' => function ($query) {
                $query->where('status_penghuni', 'Keluar')
                    ->with(['historyRumah' => function ($historyQuery) {
                        $historyQuery->select('id', 'penghuni_id', 'tanggal_mulai_huni', 'tanggal_akhir_huni');
                    }]);
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

    public function cekTagihan($id)
    {
        try {
            $data = Rumah::with([
                'penghuni' => function ($query) {
                    $query->whereIn('status_penghuni', ['Tetap', 'Kontrak']);
                },
                'penghuni.pembayaran' => function ($query) {
                    $query->where('status_pembayaran', 'Belum Lunas');
                }
            ])->find($id);

            $result = [];
            $iuranTercatat = [];
            $jumlahPenghuni = count($data->penghuni);

            foreach ($data->penghuni as $penghuni) {
                foreach ($penghuni->pembayaran as $pembayaran) {
                    if (!in_array($pembayaran->iuran->id, $iuranTercatat)) {
                        $totalTagihan = $pembayaran->iuran->biaya * $jumlahPenghuni;

                        $result[] = [
                            'id' => $pembayaran->iuran->id,
                            'alamat' => $data->alamat,
                            'nama_iuran' => $pembayaran->iuran->nama,
                            'biaya_iuran' => $pembayaran->iuran->biaya,
                            'tanggal_tagihan' => $pembayaran->iuran->tanggal_tagihan,
                            'total_tagihan' => $totalTagihan
                        ];
                        $iuranTercatat[] = $pembayaran->iuran->id;
                    }
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar Rumah berdasarkan id berhasil didapat',
                'data' => [
                    'alamat' => $data->alamat,
                    'tagihan' => $result
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function cekTagihanTahunan($id)
    {
        try {
            $data = Rumah::with([
                'penghuni' => function ($query) {
                    $query->whereIn('status_penghuni', ['Tetap', 'Kontrak']);
                },
                'penghuni.pembayaran' => function ($query) {
                    $query->where('status_pembayaran', 'Belum Lunas');
                }
            ])->find($id);

            $result = [];
            $iuranTerhitung = [];
            $jumlahPenghuni = count($data->penghuni);

            foreach ($data->penghuni as $penghuni) {
                foreach ($penghuni->pembayaran as $pembayaran) {
                    $namaIuran = $pembayaran->iuran->nama;
                    $biayaIuran = $pembayaran->iuran->biaya;
                    $tahunTagihan = date('Y', strtotime($pembayaran->iuran->tanggal_tagihan));

                    $key = $namaIuran . '-' . $tahunTagihan;

                    $totalTagihan = $pembayaran->iuran->biaya * $jumlahPenghuni;

                    if (isset($iuranTerhitung[$key])) {
                        if (!in_array($pembayaran->iuran->tanggal_tagihan, $iuranTerhitung[$key]['tanggal_tagihan'])) {
                            $iuranTerhitung[$key]['tanggal_tagihan'][] = $pembayaran->iuran->tanggal_tagihan;
                        }
                    } else {
                        $iuranTerhitung[$key] = [
                            'id' => $pembayaran->iuran->id,
                            'alamat' => $data->alamat,
                            'nama_iuran' => $namaIuran,
                            'biaya_iuran' => $biayaIuran,
                            'tanggal_tagihan' => [$pembayaran->iuran->tanggal_tagihan],
                            'total_tagihan' => $totalTagihan
                        ];
                    }
                }
            }

            $result = array_values($iuranTerhitung);

            return response()->json([
                'status' => 'success',
                'message' => 'Daftar Rumah berdasarkan id berhasil didapat',
                'data' => [
                    'alamat' => $data->alamat,
                    'tagihan' => $result
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
