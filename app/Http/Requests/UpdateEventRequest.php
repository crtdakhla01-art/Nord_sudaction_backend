<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateEventRequest extends FormRequest
{
    private function normalizeLinkValue(mixed $value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if (!preg_match('~^[a-z][a-z0-9+\-.]*://~i', $trimmed)) {
            return 'https://' . ltrim($trimmed, '/');
        }

        return $trimmed;
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $gallery = collect($this->input('gallery', []))
            ->map(function ($item) {
                if (!is_array($item)) {
                    return $item;
                }

                if (array_key_exists('link', $item)) {
                    $item['link'] = $this->normalizeLinkValue($item['link']);
                }

                return $item;
            })
            ->values()
            ->all();

        $this->merge([
            'gallery' => $gallery,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'date' => ['sometimes', 'required', 'date'],
            'location' => ['sometimes', 'required', 'string', 'max:255'],
            'is_it_passed' => ['nullable', 'boolean'],
            'gallery' => ['nullable', 'array'],
            'gallery.*.image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif,bmp,svg,tiff,tif,ico,avif,heic,heif,jfif', 'max:20480'],
            'gallery.*.existing_image' => ['nullable', 'string', 'max:2048'],
            'gallery.*.video' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg,video/quicktime', 'max:51200'],
            'gallery.*.existing_video' => ['nullable', 'string', 'max:2048'],
            'gallery.*.link' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
