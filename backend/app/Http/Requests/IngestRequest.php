<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class IngestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'articles' => ['required', 'array', 'min:1'],
            'articles.*.source' => ['required', 'string', 'max:120'],
            'articles.*.title' => ['required', 'string', 'max:1000'],
            'articles.*.url' => ['required', 'url', 'max:1000', 'distinct'],
            'articles.*.content' => ['nullable', 'string'],
            'articles.*.published_at' => ['nullable', 'date'],
            'articles.*.type' => ['nullable', 'in:news,rss,social,cetak'],
        ];
    }
}
