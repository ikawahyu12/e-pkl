<?php

namespace App\Controllers;

class SiswaController
{
    public function index()
    {
        return view('admin/siswa/index', [
            'title'      => 'Data Siswa',
            'activePage' => 'data-siswa',
            'siswa'      => [],
        ]);
    }

    public function create()
    {
        return view('admin/siswa/create', [
            'title'      => 'Tambah Siswa',
            'activePage' => 'data-siswa',
        ]);
    }

    public function detail($id)
    {
        return view('admin/siswa/detail', [
            'title'      => 'Detail Siswa',
            'activePage' => 'data-siswa',
            'id'         => $id,
        ]);
    }

    public function edit($id)
    {
        return view('admin/siswa/edit', [
            'title'      => 'Edit Siswa',
            'activePage' => 'data-siswa',
            'id'         => $id,
        ]);
    }

    public function destroy($id)
    {
        header('Location: ' . $this->base . '/admin/siswa');
        exit;
    }
}