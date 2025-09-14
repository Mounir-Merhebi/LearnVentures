<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyChatReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:users,id'],
            'report_date' => ['required', 'date'],
            'tldr' => ['nullable', 'string', 'max:500'],
            'key_topics' => ['nullable', 'array'],
            'misconceptions' => ['nullable', 'array'],
            'next_actions' => ['nullable', 'array'],
            'stats' => ['nullable', 'array'],
            'full_summary' => ['nullable', 'array'],
        ];
    }
}

