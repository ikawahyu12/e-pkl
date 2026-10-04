<?php

namespace App\Controllers;

class GuruController
{
    protected string $base = '';

    public function index()
    {
        return view('admin/guru/index', [
            'title'      => 'Data Guru',
            'activePage' => 'data-guru',
            'guru'       => [],
        ]);
    }

    public function create()
    {
        return view('admin/guru/create', [
            'title'      => 'Tambah Guru',
            'activePage' => 'data-guru',
        ]);
    }

    public function detail(int $id)
    {
        return view('admin/guru/detail', [
            'title'      => 'Detail Guru',
            'activePage' => 'data-guru',
            'id'         => $id,
        ]);
    }

    public function edit(int $id)
    {
        return view('admin/guru/edit', [
            'title'      => 'Edit Guru',
            'activePage' => 'data-guru',
            'id'         => $id,
        ]);
    }

    public function destroy(int $id)
    {
        header('Location: ' . $this->base . '/admin/guru');
        exit;
    }
}