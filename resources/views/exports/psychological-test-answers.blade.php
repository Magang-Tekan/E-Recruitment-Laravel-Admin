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
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
        }
        
        .header h1 {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header h2 {
            font-size: 14px;
            color: #666;
        }
        
        .candidate-info {
            margin-bottom: 25px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        
        .candidate-info table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .candidate-info td {
            padding: 5px 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .candidate-info td:first-child {
            font-weight: bold;
            width: 150px;
        }
        
        .questions-section {
            margin-bottom: 30px;
        }
        
        .question-item {
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            padding: 15px;
            page-break-inside: avoid;
        }
        
        .question-number {
            font-weight: bold;
            color: #007bff;
            margin-bottom: 8px;
        }
        
        .question-text {
            margin-bottom: 12px;
            font-weight: 500;
            line-height: 1.5;
        }
        
        .choices {
            margin-bottom: 12px;
            background-color: #f8f9fa;
            padding: 10px;
            border-radius: 3px;
        }
        
        .choices-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #666;
        }
        
        .choice-item {
            margin-bottom: 3px;
            padding: 2px 0;
        }
        
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
                <td style="height: 40px;"></td>
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
                <td style="height: 80px;"></td>
            </tr>
        </table>

        <div class="final-decision">
            <h4>KEPUTUSAN AKHIR</h4>
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
        </div>

        <table style="width: 100%; margin-top: 30px;">
            <tr>
                <td style="width: 50%; text-align: center; vertical-align: bottom;">
                    <div style="margin-bottom: 60px;">Tanggal Evaluasi:</div>
                    <div style="border-top: 1px solid #333; padding-top: 5px;">
                        _____________
                    </div>
                </td>
                <td style="width: 50%; text-align: center; vertical-align: bottom;">
                    <div style="margin-bottom: 60px;">Evaluator</div>
                    <div style="border-top: 1px solid #333; padding-top: 5px;">
                        ( _________________ )
                    </div>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>