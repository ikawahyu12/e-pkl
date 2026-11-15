<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

class LaporanEksporRequest extends LaporanFilterRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'jenis' => ['required', Rule::in(['presensi', 'jurnal'])],
            'format' => ['required', Rule::in(['xls', 'pdf'])],
        ];
    }
}