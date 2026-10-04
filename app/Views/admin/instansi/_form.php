<div class="mb-3">
    <label class="form-label">Nama Instansi / Perusahaan</label>
    <input type="text" name="nama_instansi" class="form-control" value="<?= htmlspecialchars($mitra['nama_instansi'] ?? '') ?>" required>
</div>
<div class="mb-3">
    <label class="form-label">Alamat Lengkap</label>
    <textarea name="alamat" class="form-control" rows="3" required><?= htmlspecialchars($mitra['alamat'] ?? '') ?></textarea>
</div>
<div class="mb-3">
    <label class="form-label">Penanggung Jawab / Pembimbing</label>
    <input type="text" name="pembimbing_lapangan" class="form-control" value="<?= htmlspecialchars($mitra['pembimbing_lapangan'] ?? '') ?>" required>
</div>
<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">No. Telepon / HP</label>
        <input type="text" name="telepon" class="form-control" value="<?= htmlspecialchars($mitra['telepon'] ?? '') ?>" required>
    </div>
    <div class="col-md-6 mb-3">
        <label class="form-label">Kuota Siswa</label>
        <input type="number" name="kuota" class="form-control" value="<?= htmlspecialchars($mitra['kuota'] ?? '') ?>" required>
    </div>
</div>