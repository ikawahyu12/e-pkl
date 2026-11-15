<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class LaporanFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('jurusan'))) {
            $this->merge(['jurusan' => strtoupper(trim($this->input('jurusan')))]);
        }
    }

    public function rules(): array
    {
        return [
            'periode_id' => ['nullable', 'integer', 'min:1', 'exists:periode_pkl,id'],
            'jurusan' => ['nullable', 'string', 'max:20', 'exists:jurusan,kode'],
        ];
    }

    public function periodeId(): ?int
    {
        $value = $this->validated('periode_id');

        return $value === null ? null : (int) $value;
    }

    public function jurusanKode(): ?string
    {
        $value = $this->validated('jurusan');

        return $value === null || $value === '' ? null : (string) $value;
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Filter laporan tidak valid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}