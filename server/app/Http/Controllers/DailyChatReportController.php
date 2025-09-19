<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyChatReportRequest;
use App\Models\DailyChatReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DailyChatReportController extends Controller
{
    /**
     * Upsert a daily chat report for a student and date.
     */
    public function upsert(StoreDailyChatReportRequest $request): JsonResponse
    {
        try {
            $data = $request->validated();

            $report = DailyChatReport::updateOrCreate(
                [
                    'student_id' => $data['student_id'],
                    'report_date' => $data['report_date'],
                ],
                [
                    'tldr' => $data['tldr'] ?? null,
                    'key_topics' => $data['key_topics'] ?? null,
                    'misconceptions' => $data['misconceptions'] ?? null,
                    'next_actions' => $data['next_actions'] ?? null,
                    'stats' => $data['stats'] ?? null,
                    'full_summary' => $data['full_summary'] ?? null,
                    'analyzed_at' => isset($data['analyzed_at']) ? $data['analyzed_at'] : null,
                ]
            );

            Log::info('Daily chat report upserted', [
                'student_id' => $data['student_id'],
                'report_date' => $data['report_date'],
                'is_new' => $report->wasRecentlyCreated
            ]);

            return response()->json([
                'success' => true,
                'data' => $report,
                'message' => $report->wasRecentlyCreated ? 'Report created successfully' : 'Report updated successfully'
            ], $report->wasRecentlyCreated ? 201 : 200);

        } catch (\Exception $e) {
            Log::error('Failed to upsert daily chat report', [
                'error' => $e->getMessage(),
                'data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save daily chat report',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * List reports with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $q = DailyChatReport::query()
                ->leftJoin('users', 'users.id', '=', 'daily_chat_reports.user_id')
                ->select('daily_chat_reports.*', 'users.name as student_name');

            if ($request->filled('student_id')) {
                $q->where('daily_chat_reports.user_id', (int) $request->integer('student_id'));
            }
            if ($request->filled('user_id')) {
                $q->where('daily_chat_reports.user_id', (int) $request->integer('user_id'));
            }

            if ($request->filled('date')) {
                $q->whereDate('daily_chat_reports.report_date', $request->date('date'));
            }

            if ($request->filled('date_from')) {
                $q->whereDate('daily_chat_reports.report_date', '>=', $request->date('date_from'));
            }

            if ($request->filled('date_to')) {
                $q->whereDate('daily_chat_reports.report_date', '<=', $request->date('date_to'));
            }

            if ($request->filled('analyzed')) {
                if ($request->boolean('analyzed')) {
                    $q->whereNotNull('daily_chat_reports.analyzed_at');
                } else {
                    $q->whereNull('daily_chat_reports.analyzed_at');
                }
            }

            // Optional name filter
            if ($request->filled('student_name')) {
                $term = $request->get('student_name');
                $q->where('users.name', 'like', "%{$term}%");
            }
            if ($request->filled('name')) {
                $term = $request->get('name');
                $q->where('users.name', 'like', "%{$term}%");
            }

            $reports = $q->orderByDesc('daily_chat_reports.report_date')->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $reports
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch daily chat reports', [
                'error' => $e->getMessage(),
                'filters' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch daily chat reports',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get a specific report.
     */
    public function show(Request $request, $id): JsonResponse
    {
        try {
            $report = DailyChatReport::with('user:id,name')->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $report
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch daily chat report', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Daily chat report not found',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mark a report as analyzed.
     */
    public function markAnalyzed(Request $request, $id): JsonResponse
    {
        try {
            $report = DailyChatReport::findOrFail($id);
            $report->markAsAnalyzed();

            return response()->json([
                'success' => true,
                'message' => 'Report marked as analyzed',
                'data' => $report
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to mark report as analyzed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark report as analyzed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
