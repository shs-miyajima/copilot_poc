<?php

namespace App\Services;

use App\Models\Survey;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * CSV エクスポート（UTF-8 BOM付き、league/csv 不要のネイティブ実装）
     */
    public function exportCsv(Survey $survey): StreamedResponse
    {
        $filename = 'survey_' . $survey->id . '_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return response()->stream(function () use ($survey) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM（Excel で文字化けしないよう）
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            // ヘッダー行
            $questions = $survey->questions()->with('options')->orderBy('order')->get();
            $header    = ['回答ID', '回答日時'];
            foreach ($questions as $q) {
                $header[] = $q->label;
            }
            fputcsv($handle, $header);

            // データ行（N+1 回避のため JOIN で一括取得）
            $responses = $survey->responses()
                ->with(['answers.options'])
                ->whereNotNull('submitted_at')
                ->orderBy('submitted_at')
                ->get();

            foreach ($responses as $response) {
                $row = [
                    $response->id,
                    $response->submitted_at->format('Y-m-d H:i:s'),
                ];

                foreach ($questions as $question) {
                    $answer = $response->answers->firstWhere('question_id', $question->id);
                    $row[]  = $this->formatAnswerForExport($answer, $question);
                }

                fputcsv($handle, $row);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Excel エクスポート（phpoffice/phpspreadsheet が必要）
     *
     * パッケージ未インストール時は CSV にフォールバック
     */
    public function exportExcel(Survey $survey): StreamedResponse
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            // フォールバック: CSV で返す
            return $this->exportCsv($survey);
        }

        $filename = 'survey_' . $survey->id . '_' . now()->format('Ymd_His') . '.xlsx';

        $headers = [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Cache-Control'       => 'max-age=0',
        ];

        return response()->stream(function () use ($survey) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet       = $spreadsheet->getActiveSheet();
            $sheet->setTitle('回答データ');

            $questions = $survey->questions()->with('options')->orderBy('order')->get();

            // ヘッダー行
            $col  = 1;
            $sheet->setCellValueByColumnAndRow($col++, 1, '回答ID');
            $sheet->setCellValueByColumnAndRow($col++, 1, '回答日時');
            foreach ($questions as $q) {
                $sheet->setCellValueByColumnAndRow($col++, 1, $q->label);
            }

            // データ行
            $responses = $survey->responses()
                ->with(['answers.options'])
                ->whereNotNull('submitted_at')
                ->orderBy('submitted_at')
                ->get();

            $rowNum = 2;
            foreach ($responses as $response) {
                $col = 1;
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $response->id);
                $sheet->setCellValueByColumnAndRow($col++, $rowNum, $response->submitted_at->format('Y-m-d H:i:s'));

                foreach ($questions as $question) {
                    $answer = $response->answers->firstWhere('question_id', $question->id);
                    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $this->formatAnswerForExport($answer, $question));
                }
                $rowNum++;
            }

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, $headers);
    }

    /**
     * 回答データを文字列にフォーマットする
     */
    private function formatAnswerForExport($answer, $question): string
    {
        if (! $answer) {
            return '';
        }

        if (in_array($question->type, ['radio', 'dropdown'], true)) {
            $option = $question->options->firstWhere('id', $answer->value);
            return $option ? $option->label : ($answer->value ?? '');
        }

        if ($question->type === 'checkbox') {
            $selectedIds = $answer->options->pluck('option_id')->toArray();
            $labels      = $question->options
                ->filter(fn($o) => in_array($o->id, $selectedIds))
                ->pluck('label');
            return $labels->implode(', ');
        }

        return $answer->value ?? '';
    }
}
