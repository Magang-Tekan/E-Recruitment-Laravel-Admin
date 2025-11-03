<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lembar Jawaban Test Psikologi</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            line-height: 1.5;
            color: #333;
            margin: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #2c5282;
            padding-bottom: 20px;
        }
        
        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #2c5282;
            text-transform: uppercase;
        }
        
        .header .company-name {
            font-size: 16px;
            color: #4a5568;
            margin-bottom: 5px;
        }
        
        .header .export-date {
            font-size: 10px;
            color: #718096;
        }
        
        .candidate-info {
            margin-bottom: 25px;
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }
        
        .candidate-info h3 {
            color: #2c5282;
            font-size: 14px;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-grid {
            display: table;
            width: 100%;
        }
        
        .info-row {
            display: table-row;
        }
        
        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 180px;
            padding: 8px 15px 8px 0;
            color: #4a5568;
            border-bottom: 1px dotted #cbd5e0;
        }
        
        .info-value {
            display: table-cell;
            padding: 8px 0;
            color: #2d3748;
            border-bottom: 1px dotted #cbd5e0;
        }
        
        .test-summary {
            margin-bottom: 25px;
            background-color: #ebf8ff;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #3182ce;
        }
        
        .summary-grid {
            display: table;
            width: 100%;
        }
        
        .summary-item {
            display: table-cell;
            text-align: center;
            padding: 0 15px;
        }
        
        .summary-label {
            font-size: 10px;
            color: #4a5568;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .summary-value {
            font-size: 16px;
            font-weight: bold;
            color: #2c5282;
            margin-top: 5px;
        }
        
        .questions-section h3 {
            color: #2c5282;
            font-size: 14px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 8px;
        }
        
        .question-item {
            margin-bottom: 20px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 18px;
            page-break-inside: avoid;
            background-color: #fff;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .question-header {
            display: table;
            width: 100%;
            margin-bottom: 12px;
        }
        
        .question-number {
            display: table-cell;
            font-weight: bold;
            color: #3182ce;
            font-size: 12px;
            width: 60px;
            background-color: #ebf8ff;
            padding: 8px 12px;
            border-radius: 20px;
            text-align: center;
        }
        
        .question-status {
            display: table-cell;
            text-align: right;
            vertical-align: middle;
        }
        
        .status-correct {
            background-color: #c6f6d5;
            color: #22543d;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .status-incorrect {
            background-color: #fed7d7;
            color: #742a2a;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .question-text {
            margin: 15px 0;
            font-weight: 500;
            line-height: 1.6;
            color: #2d3748;
        }
        
        .answer-section {
            background-color: #f7fafc;
            padding: 12px;
            border-radius: 6px;
            margin-top: 12px;
        }
        
        .selected-answer {
            font-weight: bold;
            color: #3182ce;
            margin-bottom: 8px;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
            text-align: center;
            color: #718096;
            font-size: 10px;
        }
        
        @media print {
            body { margin: 15px; }
            .question-item { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lembar Jawaban Test Psikologi</h1>
        <div class="company-name">{{ $candidate['company'] }}</div>
        <div class="export-date">Diekspor pada: {{ $export_date->format('d F Y, H:i') }} WIB</div>
    </div>

    <div class="candidate-info">
        <h3>Informasi Kandidat</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nama Lengkap</div>
                <div class="info-value">{{ $candidate['name'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Email</div>
                <div class="info-value">{{ $candidate['email'] }}</div>
            </div>
            @if($candidate['phone'])
            <div class="info-row">
                <div class="info-label">No. Telepon</div>
                <div class="info-value">{{ $candidate['phone'] }}</div>
            </div>
            @endif
            <div class="info-row">
                <div class="info-label">Posisi yang Dilamar</div>
                <div class="info-value">{{ $candidate['position'] }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Periode Rekrutmen</div>
                <div class="info-value">{{ $candidate['period'] }}</div>
            </div>
        </div>
    </div>

    @if($test_date)
    <div class="test-summary">
        <div class="summary-grid">
            <div class="summary-item">
                <div class="summary-label">Tanggal Test</div>
                <div class="summary-value">{{ $test_date->format('d/m/Y H:i') }}</div>
            </div>
            <div class="summary-item">
                <div class="summary-label">Total Soal</div>
                <div class="summary-value">{{ count($answers) }}</div>
            </div>
            @if($manual_score)
            <div class="summary-item">
                <div class="summary-label">Skor Manual</div>
                <div class="summary-value">{{ $manual_score }}</div>
            </div>
            @endif
            @if($status)
            <div class="summary-item">
                <div class="summary-label">Status</div>
                <div class="summary-value">{{ $status }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="questions-section">
        <h3>Jawaban Test Psikologi</h3>
        @foreach($answers as $answer)
        <div class="question-item">
            <div class="question-header">
                <div class="question-number">
                    {{ $answer['number'] }}
                </div>
                <div class="question-status">
                    @if(isset($answer['is_correct']) && $answer['is_correct'])
                        <span class="status-correct">✓ BENAR</span>
                    @elseif(isset($answer['is_correct']) && !$answer['is_correct'])
                        <span class="status-incorrect">✗ SALAH</span>
                    @endif
                </div>
            </div>
            
            <div class="question-text">
                {{ $answer['question'] }}
            </div>
            
            <div class="answer-section">
                <div class="selected-answer">
                    <strong>Jawaban Dipilih:</strong> {{ $answer['selected_answer'] }}
                </div>
            </div>
        </div>
        @endforeach
    </div>

    @if($psychological_history && ($psychological_history->notes || $reviewer_name))
    <div class="page-break"></div>
    <div class="candidate-info">
        <h3>Evaluasi Reviewer</h3>
        <div class="info-grid">
            @if($reviewer_name)
            <div class="info-row">
                <div class="info-label">Nama Reviewer</div>
                <div class="info-value">{{ $reviewer_name }}</div>
            </div>
            @endif
            @if($review_date)
            <div class="info-row">
                <div class="info-label">Tanggal Review</div>
                <div class="info-value">{{ $review_date->format('d F Y, H:i') }} WIB</div>
            </div>
            @endif
            @if($psychological_history->notes)
            <div class="info-row">
                <div class="info-label">Catatan</div>
                <div class="info-value">{{ $psychological_history->notes }}</div>
            </div>
            @endif
        </div>
    </div>
    @endif

    <div class="footer">
        <p>Dokumen ini dibuat secara otomatis oleh sistem E-Recruitment</p>
        <p>{{ $candidate['company'] }} - {{ now()->format('Y') }}</p>
    </div>
</body>
</html>
        
        .selected-answer {
            margin-bottom: 12px;
        }
        
        .selected-answer-label {
            font-weight: bold;
            color: #28a745;
        }
        
        .selected-answer-text {
            background-color: #d4edda;
            padding: 8px;
            border-radius: 3px;
            border-left: 4px solid #28a745;
        }
        
        .scoring-section {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        
        .manual-score {
            width: 150px;
        }
        
        .score-label {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .score-box {
            border: 2px solid #333;
            padding: 10px;
            text-align: center;
            min-height: 40px;
            background-color: #fff;
        }
        
        .notes-section {
            width: calc(100% - 170px);
        }
        
        .notes-box {
            border: 2px solid #333;
            padding: 10px;
            min-height: 40px;
            background-color: #fff;
        }
        
        .footer-section {
            margin-top: 40px;
            border-top: 2px solid #000;
            padding-top: 20px;
        }
        
        .evaluation-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .evaluation-table th,
        .evaluation-table td {
            border: 2px solid #333;
            padding: 12px;
            text-align: left;
        }
        
        .evaluation-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .final-decision {
            text-align: center;
            margin-top: 20px;
        }
        
        .decision-options {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin: 15px 0;
        }
        
        .decision-option {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid #333;
            display: inline-block;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            body {
                font-size: 11px;
            }
            
            .question-item {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LEMBAR JAWABAN TEST PSIKOLOGI</h1>
        <h2>{{ $candidate['company'] }}</h2>
    </div>

    <div class="candidate-info">
        <table>
            <tr>
                <td>Nama Kandidat</td>
                <td>: {{ $candidate['name'] }}</td>
            </tr>
            <tr>
                <td>Email</td>
                <td>: {{ $candidate['email'] }}</td>
            </tr>
            <tr>
                <td>No. Telepon</td>
                <td>: {{ $candidate['phone'] ?? '-' }}</td>
            </tr>
            <tr>
                <td>Posisi yang Dilamar</td>
                <td>: {{ $candidate['position'] }}</td>
            </tr>
            <tr>
                <td>Periode</td>
                <td>: {{ $candidate['period'] }}</td>
            </tr>
            <tr>
                <td>Tanggal Test</td>
                <td>: {{ $test_date ? $test_date->format('d F Y, H:i') : '-' }}</td>
            </tr>
            <tr>
                <td>Tanggal Export</td>
                <td>: {{ $export_date->format('d F Y, H:i') }}</td>
            </tr>
            @if($psychological_history)
            <tr>
                <td>Status Penilaian</td>
                <td>: {{ $status }}</td>
            </tr>
            @if($manual_score)
            <tr>
                <td>Skor Manual</td>
                <td>: {{ $manual_score }}</td>
            </tr>
            @endif
            @if($reviewer_name)
            <tr>
                <td>Reviewer</td>
                <td>: {{ $reviewer_name }}</td>
            </tr>
            @endif
            @if($review_date)
            <tr>
                <td>Tanggal Review</td>
                <td>: {{ $review_date->format('d F Y, H:i') }}</td>
            </tr>
            @endif
            @if($manual_score === null)
            <tr>
                <td>Status Manual Scoring</td>
                <td>: <span style="color: #f59e0b;">Belum dilakukan manual scoring</span></td>
            </tr>
            @endif
            @endif
        </table>
    </div>

    <div class="questions-section">
        <h3 style="margin-bottom: 20px; border-bottom: 1px solid #ddd; padding-bottom: 5px;">JAWABAN KANDIDAT</h3>
        
        @foreach($answers as $answer)
            <div class="question-item">
                <div class="question-number">
                    Pertanyaan {{ $answer['number'] }}
                </div>
                
                <div class="question-text">
                    {{ $answer['question'] }}
                </div>
                
                <div class="choices">
                    <div class="choices-title">Pilihan Jawaban:</div>
                    @foreach(explode("\n", $answer['all_choices']) as $choice)
                        <div class="choice-item">{{ $choice }}</div>
                    @endforeach
                </div>
                
                <div class="selected-answer">
                    <div class="selected-answer-label">Jawaban yang Dipilih:</div>
                    <div class="selected-answer-text">
                        {{ $answer['selected_answer'] }}
                    </div>
                </div>
                
                <div class="scoring-section">
                    <div class="manual-score">
                        <div class="score-label">Nilai Manual:</div>
                        <div class="score-box"></div>
                    </div>
                    <div class="notes-section">
                        <div class="score-label">Catatan Evaluator:</div>
                        <div class="notes-box"></div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="page-break"></div>

    <div class="footer-section">
        <h3 style="text-align: center; margin-bottom: 20px;">PENILAIAN AKHIR</h3>
        
        <table class="evaluation-table">
            <tr>
                <th style="width: 200px;">Aspek Penilaian</th>
                <th>Catatan/Skor</th>
            </tr>
            <tr>
                <td>Total Skor Manual</td>
                <td style="height: 40px;">{{ $manual_score ?? '' }}</td>
            </tr>
            <tr>
                <td>Kemampuan Analitis</td>
                <td style="height: 40px;"></td>
            </tr>
            <tr>
                <td>Kemampuan Logika</td>
                <td style="height: 40px;"></td>
            </tr>
            <tr>
                <td>Stabilitas Emosi</td>
                <td style="height: 40px;"></td>
            </tr>
            <tr>
                <td>Kesesuaian dengan Posisi</td>
                <td style="height: 40px;"></td>
            </tr>
            <tr>
                <td>Catatan Tambahan</td>
                <td style="height: 80px;">{{ $review_notes ?? '' }}</td>
            </tr>
        </table>

        <div class="final-decision">
            <h4>KEPUTUSAN AKHIR</h4>
            @if($psychological_history && $psychological_history->reviewed_at && $manual_score !== null)
                {{-- Only show decision if manually reviewed and scored --}}
                @if($manual_score >= 60)
                    <div class="decision-options">
                        <div class="decision-option">
                            <span class="checkbox" style="background-color: #28a745;">✓</span>
                            <strong>LULUS</strong>
                        </div>
                        <div class="decision-option">
                            <span class="checkbox"></span>
                            <strong>TIDAK LULUS</strong>
                        </div>
                    </div>
                @else
                    <div class="decision-options">
                        <div class="decision-option">
                            <span class="checkbox"></span>
                            <strong>LULUS</strong>
                        </div>
                        <div class="decision-option">
                            <span class="checkbox" style="background-color: #dc3545;">✓</span>
                            <strong>TIDAK LULUS</strong>
                        </div>
                    </div>
                @endif
            @else
                {{-- No decision made yet - leave both checkboxes empty --}}
                <div class="decision-options">
                    <div class="decision-option">
                        <span class="checkbox"></span>
                        <strong>LULUS</strong>
                    </div>
                    <div class="decision-option">
                        <span class="checkbox"></span>
                        <strong>TIDAK LULUS</strong>
                    </div>
                </div>
                <div style="margin-top: 10px; font-style: italic; color: #666;">
                    * Keputusan akan ditentukan setelah manual scoring
                </div>
            @endif
        </div>

        <table style="width: 100%; margin-top: 30px;">
            <tr>
                <td style="width: 50%; text-align: center; vertical-align: bottom;">
                    <div style="margin-bottom: 60px;">Tanggal Evaluasi:</div>
                    <div style="border-top: 1px solid #333; padding-top: 5px;">
                        {{ $review_date ? $review_date->format('d F Y') : '_____________' }}
                    </div>
                </td>
                <td style="width: 50%; text-align: center; vertical-align: bottom;">
                    <div style="margin-bottom: 60px;">Evaluator</div>
                    <div style="border-top: 1px solid #333; padding-top: 5px;">
                        ( {{ $reviewer_name ?? '_________________' }} )
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>