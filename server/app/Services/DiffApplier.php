<?php

namespace App\Services;

use App\Models\Subject;
use App\Models\Chapter;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class DiffApplier
{
    protected array $allowedTables = ['subjects', 'chapters', 'lessons'];
    protected array $allowedFields = [
        'subjects' => ['title', 'description', 'instructor_id', 'grade_id'],
        'chapters' => ['title', 'order', 'subject_id'],
        'lessons' => ['title', 'content', 'order', 'chapter_id', 'version'],
    ];

    public function apply(array $diffJson): void
    {
        $this->validateDiffJson($diffJson);

        DB::transaction(function () use ($diffJson) {
            // Apply in safe order: create, update, delete
            $this->applyCreates($diffJson);
            $this->applyUpdates($diffJson);
            $this->applyDeletes($diffJson);
        });
    }

    protected function validateDiffJson(array $diffJson): void
    {
        foreach ($diffJson as $table => $operations) {
            if (!in_array($table, $this->allowedTables)) {
                throw new Exception("Table '{$table}' is not allowed for modification");
            }

            if (!is_array($operations)) {
                throw new Exception("Operations for table '{$table}' must be an array");
            }

            foreach (['create', 'update', 'delete'] as $operation) {
                if (isset($operations[$operation]) && !is_array($operations[$operation])) {
                    throw new Exception("{$operation} operations for table '{$table}' must be an array");
                }
            }
        }
    }

    protected function applyCreates(array $diffJson): void
    {
        foreach ($diffJson as $table => $operations) {
            if (!isset($operations['create'])) {
                continue;
            }

            $model = $this->getModelClass($table);
            $allowedFields = $this->allowedFields[$table];

            foreach ($operations['create'] as $record) {
                // Filter only allowed fields
                $filteredRecord = array_intersect_key($record, array_flip($allowedFields));

                // Validate required fields for creation
                $this->validateCreateRecord($table, $filteredRecord);

                try {
                    $model::create($filteredRecord);
                } catch (Exception $e) {
                    throw new Exception("Failed to create record in {$table}: " . $e->getMessage());
                }
            }
        }
    }

    protected function applyUpdates(array $diffJson): void
    {
        foreach ($diffJson as $table => $operations) {
            if (!isset($operations['update'])) {
                continue;
            }

            $model = $this->getModelClass($table);
            $allowedFields = $this->allowedFields[$table];

            foreach ($operations['update'] as $record) {
                if (!isset($record['id'])) {
                    throw new Exception("Update record must have an 'id' field");
                }

                // Filter only allowed fields
                $filteredRecord = array_intersect_key($record, array_flip($allowedFields));

                try {
                    $existingRecord = $model::findOrFail($record['id']);
                    $existingRecord->update($filteredRecord);
                } catch (Exception $e) {
                    throw new Exception("Failed to update record in {$table} with id {$record['id']}: " . $e->getMessage());
                }
            }
        }
    }

    protected function applyDeletes(array $diffJson): void
    {
        foreach ($diffJson as $table => $operations) {
            if (!isset($operations['delete'])) {
                continue;
            }

            $model = $this->getModelClass($table);

            foreach ($operations['delete'] as $record) {
                if (!isset($record['id'])) {
                    throw new Exception("Delete record must have an 'id' field");
                }

                try {
                    $existingRecord = $model::findOrFail($record['id']);
                    $existingRecord->delete();
                } catch (Exception $e) {
                    throw new Exception("Failed to delete record in {$table} with id {$record['id']}: " . $e->getMessage());
                }
            }
        }
    }

    protected function getModelClass(string $table): string
    {
        return match ($table) {
            'subjects' => Subject::class,
            'chapters' => Chapter::class,
            'lessons' => Lesson::class,
            default => throw new Exception("Unsupported table: {$table}")
        };
    }

    protected function validateCreateRecord(string $table, array $record): void
    {
        // Add specific validation rules for each table
        switch ($table) {
            case 'subjects':
                if (!isset($record['title']) || !isset($record['grade_id'])) {
                    throw new Exception("Subject creation requires 'title' and 'grade_id'");
                }
                break;

            case 'chapters':
                if (!isset($record['title']) || !isset($record['subject_id'])) {
                    throw new Exception("Chapter creation requires 'title' and 'subject_id'");
                }
                break;

            case 'lessons':
                if (!isset($record['title']) || !isset($record['chapter_id'])) {
                    throw new Exception("Lesson creation requires 'title' and 'chapter_id'");
                }
                break;
        }
    }
}
