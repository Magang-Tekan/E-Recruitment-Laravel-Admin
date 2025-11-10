<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kandidat - {{ $candidate['name'] }}</title>
    <style>
        @page {
            margin: 40mm 30mm 30mm 40mm; /* top right bottom left - increased top margin to 4cm */
            size: A4 portrait;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.6;
            color: #2c3e50;
            padding: 0;
            margin: 0;
            position: relative;
            min-height: 100%;
        }
        
        .header-image {
            width: 100%;
            max-width: 100%;
            height: auto;
            margin-bottom: 25px;
            display: block;
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding: 0; /* No padding - margin is handled by @page */
        }
        
        
        /* CV Style - Clean and Professional */
        .section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 13px;
            font-weight: bold;
            color: #155e75; /* Darker teal color */
            margin-bottom: 12px;
            padding-bottom: 6px;
            border-bottom: 2px solid #047857; /* Darker green color */
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Personal Info Section - CV Style */
        .personal-info {
            margin-bottom: 25px;
        }
        
        .personal-info-item {
            margin-bottom: 8px;
            line-height: 1.8;
        }
        
        .personal-info-label {
            font-weight: bold;
            color: #155e75; /* Darker teal */
            display: inline-block;
            width: 140px;
        }
        
        .personal-info-value {
            color: #2c3e50;
        }
        
        /* Education & Experience - CV Style */
        .cv-item {
            margin-bottom: 18px;
            padding-left: 0;
        }
        
        .cv-item-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 4px;
        }
        
        .cv-item-subtitle {
            font-size: 10px;
            color: #047857; /* Darker green */
            margin-bottom: 4px;
            font-weight: 500;
        }
        
        .cv-item-meta {
            font-size: 9px;
            color: #64748b;
            margin-bottom: 6px;
            font-style: italic;
        }
        
        .cv-item-description {
            font-size: 10px;
            color: #475569;
            line-height: 1.6;
            margin-top: 6px;
        }
        
        /* Skills & Languages - CV Style */
        .skills-languages {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        
        .skills-column, .languages-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 15px;
        }
        
        .skill-tag, .language-tag {
            display: inline-block;
            background-color: #e0f2fe; /* Light teal background */
            color: #155e75; /* Darker teal text */
            padding: 4px 10px;
            margin: 3px 5px 3px 0;
            border-radius: 12px;
            font-size: 9px;
            border: 1px solid #155e75; /* Darker teal border */
        }
        
        .skill-level, .language-proficiency {
            color: #047857; /* Darker green */
            font-weight: 500;
            margin-left: 5px;
        }
        
        /* Social Media - CV Style */
        .social-media-item {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 8px;
            font-size: 10px;
        }
        
        .social-media-label {
            font-weight: 500;
            color: #155e75; /* Darker teal */
        }
        
        .social-media-link {
            color: #047857; /* Darker green */
            text-decoration: none;
        }
        
        /* Certifications, Courses, Organizations, Achievements - CV Style */
        .cert-item, .course-item, .org-item, .achievement-item {
            margin-bottom: 15px;
            padding-bottom: 12px;
            border-bottom: 1px dotted #cbd5e1;
        }
        
        .cert-item:last-child, .course-item:last-child, .org-item:last-child, .achievement-item:last-child {
            border-bottom: none;
        }
        
        /* History Table - Simplified */
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 9px;
        }
        
        .history-table th {
            background-color: #155e75; /* Darker teal */
            color: white;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }
        
        .history-table td {
            padding: 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
        }
        
        .history-table tr:nth-child(even) {
            background-color: #f0f9ff; /* Very light blue */
        }
        
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 8px;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #d1fae5; /* Light green */
            color: #065f46; /* Dark green */
        }
        
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .badge-default {
            background-color: #e0f2fe; /* Light teal */
            color: #155e75; /* Darker teal */
        }
        
        .content-wrapper {
            position: relative;
            min-height: 100vh;
            padding: 0; /* No padding - margin is handled by @page */
        }
        .main-content {
            padding-bottom: 0;
            margin-bottom: 0;
        }
        
        .footer-wrapper {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            margin-top: auto;
        }
        
        .footer-image {
            width: 100%;
            max-width: 100%;
            height: auto;
            display: block;
        }
        
        .footer {
            padding: 10px 0;
            text-align: center;
            color: #64748b;
            font-size: 9px;
            border-top: 1px solid #e2e8f0;
            background-color: white;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        @media print {
            body {
                font-size: 9px;
            }
            
            .section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    @if(isset($headerImage) && $headerImage)
        <img src="{{ $headerImage }}" alt="Header" class="header-image" />
    @else
        <div class="header">
            <h1>DATA KANDIDAT</h1>
            <h2>{{ $candidate['company'] }}</h2>
        </div>
    @endif
    
    <div class="content-wrapper">
        <div class="main-content">
        <!-- Personal Information - CV Style -->
        <div class="section">
            <div class="section-title">Informasi Pribadi</div>
            <div class="personal-info">
                <div class="personal-info-item">
                    <span class="personal-info-label">Nama Lengkap</span>
                    <span class="personal-info-value">: {{ $candidate['full_name'] }}</span>
                </div>
                <div class="personal-info-item">
                    <span class="personal-info-label">Email</span>
                    <span class="personal-info-value">: {{ $candidate['email'] }}</span>
                </div>
                <div class="personal-info-item">
                    <span class="personal-info-label">No. Telepon</span>
                    <span class="personal-info-value">: {{ $candidate['phone'] }}</span>
                </div>
                <div class="personal-info-item">
                    <span class="personal-info-label">Alamat</span>
                    <span class="personal-info-value">: {{ $candidate['address'] }}</span>
                </div>
                <div class="personal-info-item">
                    <span class="personal-info-label">Tempat & Tanggal Lahir</span>
                    <span class="personal-info-value">: {{ $candidate['birth_place'] }}, {{ $candidate['birth_date'] && $candidate['birth_date'] !== '-' ? (is_string($candidate['birth_date']) ? \Carbon\Carbon::parse($candidate['birth_date'])->format('d F Y') : $candidate['birth_date']->format('d F Y')) : '-' }}</span>
                </div>
                <div class="personal-info-item">
                    <span class="personal-info-label">Jenis Kelamin</span>
                    <span class="personal-info-value">: {{ $candidate['gender'] }}</span>
                </div>
                <div class="personal-info-item">
                    <span class="personal-info-label">Posisi yang Dilamar</span>
                    <span class="personal-info-value">: {{ $candidate['position'] }}</span>
                </div>
                <div class="personal-info-item">
                    <span class="personal-info-label">Periode</span>
                    <span class="personal-info-value">: {{ $candidate['period'] }}</span>
                </div>
                <div class="personal-info-item">
                    <span class="personal-info-label">Tanggal Melamar</span>
                    <span class="personal-info-value">: {{ $candidate['applied_at'] && $candidate['applied_at'] !== '-' ? (is_string($candidate['applied_at']) ? \Carbon\Carbon::parse($candidate['applied_at'])->format('d F Y, H:i') : $candidate['applied_at']->format('d F Y, H:i')) : '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Social Media -->
        @if(count($social_media) > 0)
        <div class="section">
            <div class="section-title">Media Sosial</div>
            <div class="section-content">
                @foreach($social_media as $social)
                <div class="social-media-item">
                    <span class="social-media-label">{{ $social['platform'] }}:</span>
                    <span class="social-media-link">{{ $social['url'] }}</span>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Education -->
        @if(count($education) > 0)
        <div class="section">
            <div class="section-title">Pendidikan</div>
            <div class="section-content">
                @foreach($education as $edu)
                <div class="cv-item">
                    <div class="cv-item-title">{{ $edu['institution'] }}</div>
                    <div class="cv-item-subtitle">
                        {{ $edu['level'] ?? 'Tidak Diketahui' }} - {{ $edu['faculty'] }}
                        @if($edu['major'])
                            | {{ $edu['major'] }}
                        @endif
                    </div>
                    <div class="cv-item-meta">
                        {{ $edu['start_year'] }} - {{ $edu['end_year'] ?? 'Sekarang' }}
                        @if($edu['gpa'])
                            | IPK/GPA: {{ $edu['gpa'] }}
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Work Experience -->
        @if(count($work_experiences) > 0)
        <div class="section">
            <div class="section-title">Pengalaman Kerja</div>
            <div class="section-content">
                @foreach($work_experiences as $exp)
                <div class="cv-item">
                    <div class="cv-item-title">{{ $exp['position'] }}</div>
                    <div class="cv-item-subtitle">{{ $exp['company'] }}</div>
                    <div class="cv-item-meta">
                        {{ $exp['start_date'] && $exp['start_date'] !== '-' ? (is_string($exp['start_date']) ? \Carbon\Carbon::parse($exp['start_date'])->format('d F Y') : $exp['start_date']->format('d F Y')) : '-' }} - 
                        {{ $exp['end_date'] && $exp['end_date'] !== '-' ? (is_string($exp['end_date']) ? \Carbon\Carbon::parse($exp['end_date'])->format('d F Y') : $exp['end_date']->format('d F Y')) : 'Sekarang' }}
                    </div>
                    @if($exp['description'])
                    <div class="cv-item-description">{{ $exp['description'] }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Skills & Languages -->
        @if(count($skills) > 0 || count($languages) > 0)
        <div class="section">
            <div class="section-title">Keterampilan & Bahasa</div>
            <div class="section-content">
                <div class="skills-languages">
                    @if(count($skills) > 0)
                    <div class="skills-column">
                        <div style="font-weight: bold; margin-bottom: 8px; color: #1e293b; font-size: 10px;">Keterampilan</div>
                        @foreach($skills as $skill)
                        <div class="skill-tag">
                            {{ $skill['name'] }}<span class="skill-level">{{ $skill['level'] }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                    @if(count($languages) > 0)
                    <div class="languages-column">
                        <div style="font-weight: bold; margin-bottom: 8px; color: #1e293b; font-size: 10px;">Bahasa</div>
                        @foreach($languages as $lang)
                        <div class="language-tag">
                            {{ $lang['name'] }}<span class="language-proficiency">{{ $lang['proficiency'] }}</span>
                        </div>
                        @endforeach
                    </div>
                    @endif
                </div>
            </div>
        </div>
        @endif

        <!-- Certifications -->
        @if(count($certifications) > 0)
        <div class="section">
            <div class="section-title">Sertifikasi</div>
            <div class="section-content">
                @foreach($certifications as $cert)
                <div class="cert-item">
                    <div class="cv-item-title">{{ $cert['name'] }}</div>
                    <div class="cv-item-subtitle">{{ $cert['issuer'] }}</div>
                    <div class="cv-item-meta">
                        {{ $cert['date'] && $cert['date'] !== '-' ? (is_string($cert['date']) ? \Carbon\Carbon::parse($cert['date'])->format('d F Y') : $cert['date']->format('d F Y')) : '-' }}
                        @if($cert['expiry_date'] && $cert['expiry_date'] !== '-')
                            - {{ is_string($cert['expiry_date']) ? \Carbon\Carbon::parse($cert['expiry_date'])->format('d F Y') : $cert['expiry_date']->format('d F Y') }}
                        @endif
                        @if($cert['credential_id'])
                            | ID: {{ $cert['credential_id'] }}
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Courses -->
        @if(count($courses) > 0)
        <div class="section">
            <div class="section-title">Pelatihan & Kursus</div>
            <div class="section-content">
                @foreach($courses as $course)
                <div class="course-item">
                    <div class="cv-item-title">{{ $course['name'] }}</div>
                    <div class="cv-item-subtitle">{{ $course['institution'] }}</div>
                    <div class="cv-item-meta">
                        Selesai: {{ $course['completion_date'] && $course['completion_date'] !== '-' ? (is_string($course['completion_date']) ? \Carbon\Carbon::parse($course['completion_date'])->format('d F Y') : $course['completion_date']->format('d F Y')) : '-' }}
                    </div>
                    @if($course['description'])
                    <div class="cv-item-description">{{ $course['description'] }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Organizations -->
        @if(count($organizations) > 0)
        <div class="section">
            <div class="section-title">Organisasi</div>
            <div class="section-content">
                @foreach($organizations as $org)
                <div class="org-item">
                    <div class="cv-item-title">{{ $org['name'] }}</div>
                    <div class="cv-item-subtitle">{{ $org['position'] }}</div>
                    <div class="cv-item-meta">{{ $org['start_year'] }} - {{ $org['end_year'] ?? 'Sekarang' }}</div>
                    @if($org['description'])
                    <div class="cv-item-description">{{ $org['description'] }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Achievements -->
        @if(count($achievements) > 0)
        <div class="section">
            <div class="section-title">Pencapaian</div>
            <div class="section-content">
                @foreach($achievements as $achievement)
                <div class="achievement-item">
                    <div class="cv-item-title">{{ $achievement['title'] }}</div>
                    <div class="cv-item-subtitle">{{ $achievement['issuer'] }}</div>
                    <div class="cv-item-meta">
                        {{ $achievement['date'] && $achievement['date'] !== '-' ? (is_string($achievement['date']) ? \Carbon\Carbon::parse($achievement['date'])->format('d F Y') : $achievement['date']->format('d F Y')) : '-' }}
                    </div>
                    @if($achievement['description'])
                    <div class="cv-item-description">{{ $achievement['description'] }}</div>
                    @endif
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Application History -->
        @if(count($history) > 0)
        <div class="section page-break">
            <div class="section-title">Riwayat Aplikasi</div>
            <div class="section-content">
                <table class="history-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Tanggal Diproses</th>
                            <th>Reviewer</th>
                            <th>Skor</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($history as $hist)
                        <tr>
                            <td>
                                <span class="badge 
                                    @if($hist->status->code === 'approved') badge-success
                                    @elseif($hist->status->code === 'rejected') badge-danger
                                    @else badge-default
                                    @endif">
                                    {{ $hist->status->name }}
                                </span>
                            </td>
                            <td>
                                {{ $hist->processed_at ? (is_string($hist->processed_at) ? \Carbon\Carbon::parse($hist->processed_at)->format('d F Y, H:i') : $hist->processed_at->format('d F Y, H:i')) : '-' }}
                            </td>
                            <td>{{ $hist->reviewer ? $hist->reviewer->name : '-' }}</td>
                            <td>{{ $hist->score ?? '-' }}</td>
                            <td>{{ $hist->notes ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
        </div>
        
        <div class="footer-wrapper">
        @if(isset($footerImage) && $footerImage)
            <img src="{{ $footerImage }}" alt="Footer" class="footer-image" />
        @else
            <div class="footer">
                <p>Dokumen ini dihasilkan secara otomatis pada {{ $export_date->format('d F Y, H:i') }}</p>
                <p>Sistem E-Recruitment - {{ $candidate['company'] }}</p>
            </div>
        @endif
    </div>
</body>
</html>
