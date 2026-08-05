<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'keywords' => ['required', 'array', 'min:1'],
            'keywords.*' => ['required', 'string', 'max:255'],
            'source_ids' => ['nullable', 'array'],
            'source_ids.*' => ['exists:sources,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama proyek wajib diisi',
            'keywords.min' => 'Minimal 1 keyword',
        ];
    }
}
