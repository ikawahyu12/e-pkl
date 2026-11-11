<?php
/** Profil Siswa */
require_once APP_PATH . '/services/ApiClient.php';

class ProfilController extends Controller
{
    public function __construct()
    {
        $this->requireLogin();
    }

    /** Ambil data profil dari API dan ubah ke bentuk yang dipakai view */
           private function ambilProfil(): array
    {
        $api = new ApiClient();
        $res = $api->get('siswa/profil', $_SESSION['api_token']);

        if (($res['status'] ?? 0) === 401) {
            unset($_SESSION['api_token'], $_SESSION['user']);
            $this->redirect('login');
        }

        $d = $res['data'] ?? [];

        // Simpan foto untuk header
        if (!empty($d['foto_url'])) {
            $_SESSION['user']['foto'] = $d['foto_url'];
        }

        $s = $d['siswa'] ?? [];
        $p = $d['pkl'] ?? [];
        $g = $d['guru_pembimbing'] ?? [];

        $error = null;
        if (($res['success'] ?? false) !== true) {
            $error = $res['message'] ?? 'Gagal memuat data profil.';
        }

        // Status di database -> status di tampilan
        $statusMap = ['aktif' => 'berjalan', 'belum_mulai' => 'belum', 'selesai' => 'selesai'];

        $siswa = [
            'nisn'                       => $s['nisn'] ?? '',
            'nama_lengkap'               => $s['nama_lengkap'] ?? ($d['nama_tampilan'] ?? ''),
            'kelas'                      => $d['kelas'] ?? '',
            'no_hp'                      => $s['no_hp'] ?? '',
            'email'                      => $s['email_sekolah'] ?? ($d['email'] ?? ''),
            'alamat'                     => $s['alamat'] ?? '',
            'foto'                       => $d['foto_url'] ?? '',

            'status_pkl'                 => $statusMap[$p['status'] ?? ''] ?? 'belum',
            'nama_perusahaan'            => $p['nama_perusahaan'] ?? '',
            'alamat_instansi'            => $p['alamat_instansi'] ?? '',
            'pembimbing_lapangan'        => $p['pembimbing_lapangan'] ?? '',
            'kontak_pembimbing_lapangan' => $p['kontak_pembimbing'] ?? '',
            'tanggal_mulai'              => $p['tanggal_mulai'] ?? '',
            'tanggal_selesai'            => $p['tanggal_selesai'] ?? '',

            'guru_pembimbing'            => $g['nama'] ?? '',
            'kontak_guru'                => $g['no_hp'] ?? '',
            'email_guru'                 => $g['email'] ?? '',
        ];

        return [$siswa, $error];
    }
    /** Halaman profil: url('profil') */
    public function index()
    {
        [$siswa, $error] = $this->ambilProfil();

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        if ($error) {
            $flash = ['type' => 'error', 'message' => $error];
        }

        $this->render('siswa/profil/index', [
            'title'    => 'Profil Siswa',
            'showBack' => true,
            'siswa'    => $siswa,
            'flash'    => $flash,
        ]);
    }

    /** Form edit profil: url('profil/edit') */
    public function edit()
    {
        [$siswa, $error] = $this->ambilProfil();

        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);

        if ($error) {
            $flash = ['type' => 'error', 'message' => $error];
        }

        $this->render('siswa/profil/edit', [
            'title'    => 'Edit Profil',
            'showBack' => true,
            'siswa'    => $siswa,
            'flash'    => $flash,
        ]);
    }

    /** Proses simpan edit profil (POST): url('profil/update') */
    public function update()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$this->verifyCsrfToken()) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Sesi formulir tidak valid. Coba lagi.'];
            $this->redirect('profil/edit');
        }

        $fields = [
            'no_hp'  => trim($_POST['no_hp'] ?? ''),
            'alamat' => trim($_POST['alamat'] ?? ''),
        ];

        if (!empty($_FILES['foto']['name'])) {
            if (($_FILES['foto']['error'] ?? 0) !== UPLOAD_ERR_OK) {
                $_SESSION['flash'] = ['type' => 'error', 'message' => 'Upload foto gagal. Coba file lain (maks 2 MB).'];
                $this->redirect('profil/edit');
            }
            $fields['foto'] = new CURLFile(
                $_FILES['foto']['tmp_name'],
                $_FILES['foto']['type'],
                $_FILES['foto']['name']
            );
        }

        $api = new ApiClient();
        $res = $api->postForm('siswa/profil', $fields, $_SESSION['api_token']);


        if (($res['status'] ?? 0) === 401) {
            unset($_SESSION['api_token'], $_SESSION['user']);
            $this->redirect('login');
        }

        if (($res['success'] ?? false) === true) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Profil berhasil diperbarui.'];
            $this->redirect('profil');
        }

        $_SESSION['flash'] = [
            'type'    => 'error',
            'message' => $res['message'] ?? 'Gagal menyimpan profil.',
        ];
        $this->redirect('profil/edit');
    }

    /** Form ganti password: url('profil/password') */
    public function password()
    {
        $this->render('siswa/profil/password', [
            'title'    => 'Ganti Password',
            'showBack' => true,
        ]);
    }

    /** Proses simpan password baru (POST): url('profil/updatePassword') */
    public function updatePassword()
    {
        // TODO: ganti password
        header('Location: ' . url('profil'));
        exit;
    }
}