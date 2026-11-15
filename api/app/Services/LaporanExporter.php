<?php

namespace App\Services;

use DateTime;
use DateTimeZone;

/**
 * Membuat isi berkas laporan (tabel HTML) dari data LaporanService.
 *
 * - xls : tabel HTML yang dibuka Excel sebagai spreadsheet
 * - pdf : halaman cetak (dialog print -> "Simpan sebagai PDF")
 */
class LaporanExporter
{
    private const SEKOLAH = 'SMK Al Fattah Ngawak Kertosono';

    private const BULAN = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /**
     * @param  array<string, mixed>  $data  hasil LaporanService::presensi()/jurnal()
     * @return array{title: string, columns: array<int, string>, rows: array<int, array<int, mixed>>, summary: array<int, array{0: string, 1: mixed}>}
     */
    public function tabel(string $jenis, array $data): array
    {
        $r = $data['ringkasan'];
        $rows = [];

        if ($jenis === 'presensi') {
            foreach ($data['rows'] as $x) {
                $rows[] = [
                    $x['nis'], $x['nama'], $x['kelas'], $x['instansi'],
                    $x['hadir'], $x['izin'], $x['sakit'], $x['alpha'], $x['belum_lengkap'],
                    $this->persen($x['persentase']),
                ];
            }

            $summary = [
                ['Total Siswa', $r['total_siswa']],
                ['Total Hari Tercatat', $r['total_hari_tercatat']],
                ['Persentase Kehadiran', $this->persen($r['persentase_kehadiran'])],
            ];

            $result = [
                'title' => 'Rekap Presensi Siswa PKL',
                'columns' => ['NIS', 'Nama Siswa', 'Kelas', 'Instansi Mitra', 'Hadir', 'Izin', 'Sakit', 'Alpa', 'Belum Lengkap', 'Persentase'],
            ];
        } else {
            foreach ($data['rows'] as $x) {
                $rows[] = [
                    $x['nis'], $x['nama'], $x['kelas'], $x['instansi'],
                    $x['masuk'], $x['terverifikasi'], $x['menunggu'], $x['revisi'],
                ];
            }

            $summary = [
                ['Total Siswa', $r['total_siswa']],
                ['Total Jurnal Masuk', $r['jurnal_masuk']],
                ['Terverifikasi', $this->persen($r['persentase_terverifikasi'])],
                ['Perlu Review', $r['perlu_review']],
                ['Revisi', $r['revisi']],
            ];

            $result = [
                'title' => 'Rekap Jurnal Kegiatan Siswa PKL',
                'columns' => ['NIS', 'Nama Siswa', 'Kelas', 'Instansi Mitra', 'Jurnal Masuk', 'Terverifikasi', 'Perlu Review', 'Revisi'],
            ];
        }

        if (! empty($data['terpotong'])) {
            $summary[] = ['Catatan', 'Data dibatasi '.count($rows).' baris pertama'];
        }

        return $result + ['rows' => $rows, 'summary' => $summary];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $jenis, string $format, array $data): string
    {
        $tabel = $this->tabel($jenis, $data);

        $judul = $this->e($tabel['title']);
        $jurusan = $data['jurusan']['kode'] ?? 'Semua Jurusan';
        $kepala = '<h3 style="margin:0 0 4px">'.$judul.'</h3>'
            .'<div>Periode: '.$this->e($data['periode']['nama']).' &bull; Jurusan: '.$this->e($jurusan).'</div>'
            .'<div>'.self::SEKOLAH.' &bull; Dibuat '.$this->e($this->tanggal(new DateTime('now', new DateTimeZone('UTC')))).'</div><br>';

        $isi = $kepala.$this->tabelHtml($tabel);

        if ($format === 'xls') {
            return "\xEF\xBB\xBF<html><head><meta charset=\"utf-8\"></head><body>".$isi.'</body></html>';
        }

        return '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8"><title>'.$judul.' | E-PKL</title>'
            .'<style>body{font-family:Arial,Helvetica,sans-serif;color:#0b1c30;margin:32px;font-size:12px}'
            .'table{border-collapse:collapse;width:100%;font-size:12px}th,td{border:1px solid #cfd8e3;padding:6px 8px;text-align:left}'
            .'th{background:#eff4ff}@media print{body{margin:0}}</style></head>'
            .'<body onload="window.print()">'.$isi.'</body></html>';
    }

    public function namaBerkas(string $jenis, string $periodeNama, ?string $jurusanKode, string $ext): string
    {
        $slug = trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', str_replace('/', '-', $periodeNama)), '_');
        $jur = $jurusanKode === null ? 'SemuaJurusan' : strtoupper($jurusanKode);

        return 'Rekap_'.($jenis === 'jurnal' ? 'Jurnal' : 'Presensi').'_'.$jur.'_'.($slug !== '' ? $slug : 'Periode').'.'.$ext;
    }

    public function keterangan(string $jenis, ?string $jurusanKode): string
    {
        return ($jenis === 'jurnal' ? 'Logbook Harian' : 'Presensi Siswa')
            .' • '.($jurusanKode === null ? 'Semua Jurusan' : 'Jurusan '.strtoupper($jurusanKode));
    }

    /**
     * @param  array{columns: array<int, string>, rows: array<int, array<int, mixed>>, summary: array<int, array{0: string, 1: mixed}>}  $tabel
     */
    private function tabelHtml(array $tabel): string
    {
        $html = '<table border="1" cellspacing="0" cellpadding="6"><thead><tr>';
        foreach ($tabel['columns'] as $kolom) {
            $html .= '<th style="background:#eff4ff;text-align:left">'.$this->e($kolom).'</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach ($tabel['rows'] as $baris) {
            $html .= '<tr>';
            foreach ($baris as $sel) {
                // Sel sebagai teks agar nol di depan (NIS) tidak hilang di Excel
                $html .= '<td style="mso-number-format:\'\\@\'">'.$this->e($sel).'</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';

        if ($tabel['summary'] !== []) {
            $html .= '<br><table border="1" cellspacing="0" cellpadding="6"><tbody>';
            foreach ($tabel['summary'] as $item) {
                $html .= '<tr><td><strong>'.$this->e($item[0]).'</strong></td><td>'.$this->e($item[1]).'</td></tr>';
            }
            $html .= '</tbody></table>';
        }

        return $html;
    }

    private function persen(float|int|null $nilai): string
    {
        return $nilai === null ? '-' : number_format((float) $nilai, 1, '.', '').'%';
    }

    private function tanggal(DateTime $waktu): string
    {
        $waktu->setTimezone(new DateTimeZone('Asia/Jakarta'));

        return $waktu->format('j').' '.self::BULAN[(int) $waktu->format('n')].' '.$waktu->format('Y, H:i');
    }

    private function e(mixed $nilai): string
    {
        return htmlspecialchars((string) $nilai, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}