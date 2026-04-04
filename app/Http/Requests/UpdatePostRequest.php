<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string'],
            'content' => ['sometimes', 'required', 'string'],
            'media' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,mp4,mov,avi,webm,mkv', 'max:102400'],
            'external_link' => ['nullable', 'url', 'max:2048'],
            'status' => ['sometimes', 'required', 'in:draft,published'],
            'is_featured' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
