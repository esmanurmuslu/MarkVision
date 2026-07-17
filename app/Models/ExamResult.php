<?php

namespace App\Jobs;

use App\Models\ExamResult;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessExamImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $examId;
    protected $imagePath;

    public function __construct($examId, $imagePath)
    {
        $this->examId = $examId;
        $this->imagePath = $imagePath;
    }

    public function handle(): void
    {
        $scriptPath = base_path('omr_scripts/pipeline_main.py');
        $command = escapeshellcmd("python " . escapeshellarg($scriptPath) . " " . escapeshellarg($this->imagePath));
        $output = shell_exec($command);

        $data = json_decode($output, true);

        if ($data && isset($data['student_no']) && isset($data['score'])) {
            ExamResult::create([
                'exam_id'    => $this->examId,
                'student_no' => $data['student_no'],
                'score'      => $data['score'],
            ]);
            Log::info("Sınav sonucu kaydedildi: " . $this->examId);
        } else {
            Log::error("Python çıktısı hatalı: " . $output);
        }
    }
}