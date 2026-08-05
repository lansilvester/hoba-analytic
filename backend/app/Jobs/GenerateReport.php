<?php

namespace App\Jobs;

use App\Models\Report;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GenerateReport implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300;

    public int $tries = 2;

    public function __construct(public Report $report) {}

    public function handle(): void
    {
        $this->report->update(['status' => 'processing']);

        $filePath = "reports/{$this->report->id}.pdf";
        Storage::disk('local')->put($filePath, $this->placeholderContent());

        $this->report->update([
            'status' => 'ready',
            'file_path' => $filePath,
        ]);
    }

    public function failed(\Throwable $e): void
    {
        $this->report->update(['status' => 'failed']);

        Log::error('Report generation failed', [
            'report_id' => $this->report->id,
            'error' => $e->getMessage(),
        ]);
    }

    protected function placeholderContent(): string
    {
        return implode("\n", [
            '%PDF-1.4',
            '1 0 obj',
            '<< /Type /Catalog /Pages 2 0 R >>',
            'endobj',
            '2 0 obj',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            'endobj',
            '3 0 obj',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
            'endobj',
            '4 0 obj',
            '<< /Length 60 >>',
            'stream',
            'BT /F1 18 Tf 50 800 Td (Report '.$this->report->title.') Tj ET',
            'endstream',
            'endobj',
            '5 0 obj',
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
            'endobj',
            'trailer',
            '<< /Root 1 0 R >>',
            '%%EOF',
            '',
        ]);
    }
}
