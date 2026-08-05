<?php

namespace App\Services;

use App\Jobs\GenerateReport;
use App\Models\Report;

class ReportService
{
    public function generate(array $data): Report
    {
        $report = Report::create([
            'tenant_id' => TenantContext::id(),
            'project_id' => $data['project_id'],
            'title' => $data['title'],
            'status' => 'pending',
        ]);

        GenerateReport::dispatch($report);

        return $report;
    }

    public function delete(Report $report): void
    {
        $report->delete();
    }
}
