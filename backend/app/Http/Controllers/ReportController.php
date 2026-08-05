<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateReportRequest;
use App\Http\Resources\ReportResource;
use App\Models\Report;
use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService) {}

    public function index(Request $request)
    {
        return ReportResource::collection(
            Report::with('project')
                ->orderByDesc('id')
                ->paginate($request->integer('per_page', 15)),
        );
    }

    public function generate(GenerateReportRequest $request): JsonResponse
    {
        $report = $this->reportService->generate($request->validated());

        return (new ReportResource($report))
            ->response()
            ->setStatusCode(JsonResponse::HTTP_ACCEPTED);
    }

    public function show(Report $report): JsonResponse
    {
        return response()->json(['data' => new ReportResource($report->load('project'))]);
    }

    public function download(Report $report)
    {
        abort_unless($report->status === 'ready', JsonResponse::HTTP_CONFLICT, 'Report not ready yet');

        return Storage::disk('local')->download($report->file_path, "laporan-{$report->id}.pdf");
    }

    public function destroy(Report $report): JsonResponse
    {
        $this->reportService->delete($report);

        return response()->json(['message' => 'Report deleted successfully']);
    }
}
