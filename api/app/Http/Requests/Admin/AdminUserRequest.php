<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class AdminUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $normalized = [];

        if (! $this->exists('nama_tampilan') && $this->exists('nama')) {
            $normalized['nama_tampilan'] = $this->input('nama');
        }

        if (is_string($this->input('status'))) {
            $normalized['status'] = strtolower(trim($this->input('status')));
        }

        if ($normalized !== []) {
            $this->merge($normalized);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');
        $userId = $this->route('user');

        $usernameRule = Rule::unique('users', 'username');
        $emailRule = Rule::unique('users', 'email');

        if (! $isCreate && is_numeric($userId)) {
            $usernameRule->ignore((int) $userId);
            $emailRule->ignore((int) $userId);
        }

        return [
            'nama_tampilan' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:120'],
            'username' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:60', $usernameRule],
            'email' => ['sometimes', 'nullable', 'email', 'max:150', $emailRule],
            'password' => $isCreate
                ? ['required', 'string', 'min:8', 'max:255']
                : ['sometimes', 'nullable', 'string', 'min:8', 'max:255'],
            'status' => ['sometimes', 'required', Rule::in(['aktif', 'nonaktif', 'ditangguhkan'])],
        ];
    }

    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => 'Data akun Admin tidak valid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
