<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // A lecturer's name is kept on the qualification page; the account follows it.
            'name' => [$this->user()->dosen ? 'prohibited' : 'required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'alamat' => ['nullable', 'string', 'max:500'],
            'no_telp' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]+$/'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tgl_lahir' => ['nullable', 'date', 'before:today'],
            'jenis_kelamin' => ['nullable', Rule::in(array_keys(User::JENIS_KELAMIN))],
            'status_pernikahan' => ['nullable', Rule::in(array_keys(User::STATUS_PERNIKAHAN))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'no_telp.regex' => 'Nomor telepon hanya berisi angka, spasi, +, -, atau tanda kurung.',
        ];
    }
}
