<?php
$data = $data ?? [];
$mode = $mode ?? 'create';
?>

<div class="form-wrap">
    <div class="alert-info">
        <i class="fa-solid fa-circle-info"></i>
        <span>
            <?php if ($mode === 'edit'): ?>
                Anda sedang mengubah data <strong><?= htmlspecialchars($data['nama'] ?? 'Siswa'); ?></strong>
            <?php else: ?>
                Formulir pendaftaran siswa baru
            <?php endif; ?>
        </span>
    </div>

    <div class="form-grid">
        <div class="form-group full">
            <label class="required">NISN / NIS</label>
            <input type="text" name="nisn" class="form-control" value="<?= htmlspecialchars($data['nisn'] ?? ''); ?>" placeholder="0068472910">
        </div>

        <div class="form-group full">
            <label class="required">Nama Lengkap Siswa</label>
            <input type="text" name="nama" class="form-control" value="<?= htmlspecialchars($data['nama'] ?? ''); ?>" placeholder="Ahmad Rizky Pratama">
        </div>

        <div class="form-group">
            <label class="required">Kelas & Konsentrasi Keahlian</label>
            <select name="kelas" class="form-control">
                <option value="">Pilih Jurusan & Kelas</option>
                <option value="XII RPL 1" <?= (($data['kelas'] ?? '') === 'XII RPL 1') ? 'selected' : ''; ?>>XII RPL 1 (Rekayasa Perangkat Lunak)</option>
                <option value="XII RPL 2" <?= (($data['kelas'] ?? '') === 'XII RPL 2') ? 'selected' : ''; ?>>XII RPL 2 (Rekayasa Perangkat Lunak)</option>
                <option value="XII TKJ 1" <?= (($data['kelas'] ?? '') === 'XII TKJ 1') ? 'selected' : ''; ?>>XII TKJ 1 (Teknik Komputer Jaringan)</option>
                <option value="XII DKV 1" <?= (($data['kelas'] ?? '') === 'XII DKV 1') ? 'selected' : ''; ?>>XII DKV 1 (Desain Komunikasi Visual)</option>
                <option value="XII DKV 2" <?= (($data['kelas'] ?? '') === 'XII DKV 2') ? 'selected' : ''; ?>>XII DKV 2 (Desain Komunikasi Visual)</option>
            </select>
        </div>

        <div class="form-group">
            <label class="required">Jenis Kelamin</label>
            <select name="jenis_kelamin" class="form-control">
                <option value="">Pilih Jenis Kelamin</option>
                <option value="Laki-laki" <?= (($data['jenis_kelamin'] ?? '') === 'Laki-laki') ? 'selected' : ''; ?>>Laki-laki</option>
                <option value="Perempuan" <?= (($data['jenis_kelamin'] ?? '') === 'Perempuan') ? 'selected' : ''; ?>>Perempuan</option>
            </select>
        </div>

        <div class="form-group">
            <label class="required">Tempat & Tanggal Lahir</label>
            <input type="text" name="ttl" class="form-control" value="<?= htmlspecialchars($data['ttl'] ?? ''); ?>" placeholder="Bandung, 14 Mei 2007">
        </div>

        <div class="form-group">
            <label class="required">No. Kontak / WhatsApp Siswa</label>
            <input type="text" name="no_hp" class="form-control" value="<?= htmlspecialchars($data['no_hp'] ?? ''); ?>" placeholder="0812-xxxx-xxxx">
        </div>

        <div class="form-group full">
            <label class="required">Alamat Lengkap Siswa</label>
            <textarea name="alamat" class="form-control" placeholder="Jalan, RT/RW, kelurahan, kecamatan, kota"><?= htmlspecialchars($data['alamat'] ?? ''); ?></textarea>
        </div>

        <div class="form-group">
            <label class="required">Nama Orang Tua / Wali</label>
            <input type="text" name="nama_orang_tua" class="form-control" value="<?= htmlspecialchars($data['nama_orang_tua'] ?? ''); ?>" placeholder="Nama lengkap ayah / ibu / wali">
        </div>

        <div class="form-group">
            <label class="required">No. Telepon Orang Tua / Wali</label>
            <input type="text" name="hp_orang_tua" class="form-control" value="<?= htmlspecialchars($data['hp_orang_tua'] ?? ''); ?>" placeholder="Contoh: 0813-xxxx-xxxx">
        </div>

        <div class="form-group">
            <label>Instansi Mitra</label>
            <select name="instansi_id" class="form-control">
                <option value="">Pilih Instansi Mitra (opsional)</option>
                <option value="1" <?= (($data['instansi_id'] ?? '') == '1') ? 'selected' : ''; ?>>PT. Informatika Solusi Nusantara</option>
                <option value="2" <?= (($data['instansi_id'] ?? '') == '2') ? 'selected' : ''; ?>>PT. Cipta Digital</option>
                <option value="3" <?= (($data['instansi_id'] ?? '') == '3') ? 'selected' : ''; ?>>Studio Animasi</option>
            </select>
        </div>

        <div class="form-group">
            <label class="required">Guru Pembimbing Sekolah</label>
            <select name="guru_id" class="form-control">
                <option value="">Pilih Guru Pembimbing</option>
                <option value="1" <?= (($data['guru_id'] ?? '') == '1') ? 'selected' : ''; ?>>Alfredo H, S.Kom</option>
                <option value="2" <?= (($data['guru_id'] ?? '') == '2') ? 'selected' : ''; ?>>Dewi Sartika, M.Sn</option>
                <option value="3" <?= (($data['guru_id'] ?? '') == '3') ? 'selected' : ''; ?>>Eko Prasetyo, S.T</option>
            </select>
        </div>

        <div class="form-group">
            <label>Periode PKL (Mulai - Selesai)</label>
            <div class="form-inline">
                <input type="date" name="mulai_pkl" class="form-control" value="<?= htmlspecialchars($data['mulai_pkl'] ?? ''); ?>">
                <input type="date" name="selesai_pkl" class="form-control" value="<?= htmlspecialchars($data['selesai_pkl'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="required">Status Siswa</label>
            <div class="radio-group">
                <label class="radio-item">
                    <input type="radio" name="status" value="Aktif" <?= (($data['status'] ?? 'Aktif') === 'Aktif') ? 'checked' : ''; ?>>
                    <span>Aktif</span>
                </label>
                <label class="radio-item">
                    <input type="radio" name="status" value="Selesai" <?= (($data['status'] ?? '') === 'Selesai') ? 'checked' : ''; ?>>
                    <span>Selesai</span>
                </label>
                <label class="radio-item">
                    <input type="radio" name="status" value="Pending" <?= (($data['status'] ?? '') === 'Pending') ? 'checked' : ''; ?>>
                    <span>Pending</span>
                </label>
            </div>
        </div>

        <div class="form-group full">
            <label>Upload Berkas Persyaratan / Surat Pernyataan Orang Tua</label>
            <div class="upload-area">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <div>
                    <strong>Unggah berkas persyaratan atau surat lain di sini</strong>
                    <small>Format dokumen PDF, resmi atau foto surat pernyataan (Maksimal ukuran file: 5MB)</small>
                </div>
            </div>
        </div>
    </div>

    <div class="form-actions">
        <button type="button" class="btn btn-light">Batal</button>
        <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-floppy-disk"></i>
            <?= ($mode === 'edit') ? 'Simpan Perubahan' : 'Simpan Data Siswa'; ?>
        </button>
    </div>
</div>