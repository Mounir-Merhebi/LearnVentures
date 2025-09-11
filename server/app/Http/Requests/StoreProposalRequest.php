<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProposalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && in_array(auth()->user()->role, ['Moderator', 'Admin']);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scope' => 'required|array',
            'scope.grade_id' => 'integer|exists:grades,id',
            'scope.tables' => 'required|array',
            'scope.tables.*' => 'string|in:users,subjects,chapters,lessons',

            'excel_hash' => 'required|string|size:64|regex:/^[a-f0-9]+$/i',

            'excel_snapshot' => 'required|array',
            'excel_snapshot.subjects' => 'array',
            'excel_snapshot.subjects.*.id' => 'integer',
            'excel_snapshot.subjects.*.title' => 'string',
            'excel_snapshot.subjects.*.grade_id' => 'integer',
            'excel_snapshot.subjects.*.instructor_id' => 'integer',
            'excel_snapshot.subjects.*.description' => 'string',
            'excel_snapshot.chapters' => 'array',
            'excel_snapshot.chapters.*.id' => 'integer',
            'excel_snapshot.chapters.*.subject_id' => 'integer',
            'excel_snapshot.chapters.*.title' => 'string',
            'excel_snapshot.chapters.*.order' => 'integer',
            'excel_snapshot.lessons' => 'array',
            'excel_snapshot.lessons.*.id' => 'integer',
            'excel_snapshot.lessons.*.chapter_id' => 'integer',
            'excel_snapshot.lessons.*.title' => 'string',
            'excel_snapshot.lessons.*.content' => 'string',
            'excel_snapshot.lessons.*.order' => 'integer',
            'excel_snapshot.lessons.*.version' => 'integer',

            'db_snapshot' => 'required|array',
            'db_snapshot.subjects' => 'array',
            'db_snapshot.chapters' => 'array',
            'db_snapshot.lessons' => 'array',

            'diff_json' => 'required|array',
            'diff_json.subjects' => 'array',
            'diff_json.subjects.create' => 'array',
            'diff_json.subjects.update' => 'array',
            'diff_json.subjects.delete' => 'array',
            'diff_json.chapters' => 'array',
            'diff_json.chapters.create' => 'array',
            'diff_json.chapters.update' => 'array',
            'diff_json.chapters.delete' => 'array',
            'diff_json.lessons' => 'array',
            'diff_json.lessons.create' => 'array',
            'diff_json.lessons.update' => 'array',
            'diff_json.lessons.delete' => 'array',

            'excel_path' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'excel_hash.regex' => 'Excel hash must be a valid SHA256 hex string',
            'scope.tables.*.in' => 'Only users, subjects, chapters, and lessons tables are allowed',
        ];
    }
}
