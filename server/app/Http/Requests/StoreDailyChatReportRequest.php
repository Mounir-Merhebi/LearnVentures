<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreDailyChatReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only allow admins/moderators to create reports, or the system itself
        return true; // We'll handle authorization in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'student_id' => 'required|exists:users,id',
            'report_date' => 'required|date|before_or_equal:today',
            'tldr' => 'nullable|string|max:500',
            'key_topics' => 'nullable|array',
            'key_topics.*' => 'string|max:100',
            'misconceptions' => 'nullable|array',
            'misconceptions.*' => 'string|max:255',
            'next_actions' => 'nullable|array',
            'next_actions.*' => 'string|max:255',
            'stats' => 'nullable|array',
            'stats.messages_count' => 'nullable|integer|min:0',
            'stats.sessions_count' => 'nullable|integer|min:0',
            'stats.avg_response_time' => 'nullable|numeric|min:0',
            'full_summary' => 'nullable|string|max:10000',
            'analyzed_at' => 'nullable|date',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Student ID is required',
            'student_id.exists' => 'Invalid student ID',
            'report_date.required' => 'Report date is required',
            'report_date.before_or_equal' => 'Report date cannot be in the future',
            'tldr.max' => 'TL;DR summary must be less than 500 characters',
            'key_topics.*.max' => 'Each key topic must be less than 100 characters',
            'misconceptions.*.max' => 'Each misconception must be less than 255 characters',
            'next_actions.*.max' => 'Each next action must be less than 255 characters',
            'full_summary.max' => 'Full summary must be less than 10,000 characters',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure arrays are properly formatted
        if ($this->key_topics && is_string($this->key_topics)) {
            $this->merge([
                'key_topics' => json_decode($this->key_topics, true) ?? []
            ]);
        }

        if ($this->misconceptions && is_string($this->misconceptions)) {
            $this->merge([
                'misconceptions' => json_decode($this->misconceptions, true) ?? []
            ]);
        }

        if ($this->next_actions && is_string($this->next_actions)) {
            $this->merge([
                'next_actions' => json_decode($this->next_actions, true) ?? []
            ]);
        }

        if ($this->stats && is_string($this->stats)) {
            $this->merge([
                'stats' => json_decode($this->stats, true) ?? []
            ]);
        }
    }
}
