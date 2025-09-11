<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Collection;

class BaselineSerializer
{
    protected array $allowedTables = ['users', 'subjects', 'chapters', 'lessons'];

    public function serialize(array $scope): array
    {
        $this->validateScope($scope);

        $result = [
            'scope' => $scope,
            'snapshot' => []
        ];

        foreach ($scope['tables'] as $table) {
            $result['snapshot'][$table] = $this->serializeTable($table, $scope);
        }

        return $result;
    }

    protected function validateScope(array $scope): void
    {
        if (!isset($scope['tables']) || !is_array($scope['tables'])) {
            throw new \InvalidArgumentException('Scope must contain a tables array');
        }

        foreach ($scope['tables'] as $table) {
            if (!in_array($table, $this->allowedTables)) {
                throw new \InvalidArgumentException("Table '{$table}' is not allowed");
            }
        }
    }

    protected function serializeTable(string $table, array $scope): array
    {
        return match ($table) {
            'subjects' => $this->serializeSubjects($scope),
            'chapters' => $this->serializeChapters($scope),
            'lessons' => $this->serializeLessons($scope),
            'users' => $this->serializeUsers($scope),
            default => throw new \InvalidArgumentException("Unsupported table: {$table}")
        };
    }

    protected function serializeSubjects(array $scope): array
    {
        $query = Subject::with(['grade', 'instructor']);

        if (isset($scope['grade_id'])) {
            $query->where('grade_id', $scope['grade_id']);
        }

        return $query->get()->map(function ($subject) {
            return [
                'id' => $subject->id,
                'title' => $subject->title,
                'grade_id' => $subject->grade_id,
                'instructor_id' => $subject->instructor_id,
                'description' => $subject->description,
            ];
        })->toArray();
    }

    protected function serializeChapters(array $scope): array
    {
        $query = Chapter::with(['subject']);

        if (isset($scope['grade_id'])) {
            $query->whereHas('subject', function ($q) use ($scope) {
                $q->where('grade_id', $scope['grade_id']);
            });
        }

        return $query->get()->map(function ($chapter) {
            return [
                'id' => $chapter->id,
                'subject_id' => $chapter->subject_id,
                'title' => $chapter->title,
                'order' => $chapter->order,
            ];
        })->toArray();
    }

    protected function serializeLessons(array $scope): array
    {
        $query = Lesson::with(['chapter']);

        if (isset($scope['grade_id'])) {
            $query->whereHas('chapter.subject', function ($q) use ($scope) {
                $q->where('grade_id', $scope['grade_id']);
            });
        }

        return $query->get()->map(function ($lesson) {
            return [
                'id' => $lesson->id,
                'chapter_id' => $lesson->chapter_id,
                'title' => $lesson->title,
                'content' => $lesson->content,
                'order' => $lesson->order,
                'version' => $lesson->version,
            ];
        })->toArray();
    }

    protected function serializeUsers(array $scope): array
    {
        // For users table, only include instructors if they're referenced in the scope
        $instructorIds = collect();

        if (isset($scope['grade_id'])) {
            // Get instructors who teach subjects in this grade
            $instructorIds = Subject::where('grade_id', $scope['grade_id'])
                ->pluck('instructor_id')
                ->unique();
        }

        if ($instructorIds->isEmpty()) {
            return [];
        }

        return User::whereIn('id', $instructorIds)
            ->where('role', 'Instructor')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                ];
            })
            ->toArray();
    }
}
