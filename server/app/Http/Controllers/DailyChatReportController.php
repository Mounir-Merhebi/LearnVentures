<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDailyChatReportRequest;
use App\Models\DailyChatReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DailyChatReportController extends Controller
{
    /**
     * Upsert a daily chat report for a student and date.
     */
    public function upsert(StoreDailyChatReportRequest $request): JsonResponse
    {
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
            ]
        );

        return response()->json($report, 200);
    }

    /**
     * List reports with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $q = DailyChatReport::query()->with('user:id,name');

        if ($request->filled('student_id')) {
            $q->where('student_id', (int) $request->integer('student_id'));
        }

        if ($request->filled('date')) {
            $q->whereDate('report_date', $request->date('date'));
        }

        if ($request->filled('date_from')) {
            $q->whereDate('report_date', '>=', $request->date('date_from'));
        }

        if ($request->filled('date_to')) {
            $q->whereDate('report_date', '<=', $request->date('date_to'));
        }

        return response()->json($q->orderByDesc('report_date')->paginate(50));
    }
}

