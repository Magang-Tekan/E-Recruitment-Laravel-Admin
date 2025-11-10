<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Kandidat - <?php echo e($candidate['name']); ?></title>
    <style>
        @page {
            margin: 20mm 15mm 20mm 20mm; /* top right bottom left - reduced margins */
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
            font-family: 'Times New Roman', 'DejaVu Serif', serif !important;
            font-size: 10px;
            line-height: 1.4;
            color: #000000;
            padding-top: 10mm;
            margin: 0;
            position: relative;
            min-height: 100%;
        }
        
        * {
            font-family: 'Times New Roman', 'DejaVu Serif', serif !important;
        }
        
        .header-image {
            width: 100%;
            max-width: 100%;
            height: auto;
            bottom: 20mm;
            margin-bottom: 10mm;
            padding:0 ; 
            display: block;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            padding-bottom: 20mm;
        }
        
        
        /* CV Style - Clean and Professional */
        .section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 8px;
            padding-bottom: 4px;
            border-bottom: 2px solid #000000;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        /* Personal Info Section - Grid Layout */
        .personal-info {
            margin-bottom: 15px;
            
        }
        
        .personal-info-grid {
            display: table;
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }
        
        .personal-info-row {
            display: table-row;
        }
        
        .personal-info-label {
            font-weight: bold;
            color: #000000;
            display: table-cell;
            width: 35%;
            padding: 4px 10px 4px 0;
            vertical-align: top;
        }
        
        .personal-info-value {
            color: #000000;
            display: table-cell;
            padding: 4px 0;
            vertical-align: top;
        }
        
        /* Education & Experience - CV Style */
        .cv-item {
            margin-bottom: 12px;
            padding-left: 0;
        }
        
        .cv-item-title {
            font-size: 11px;
            font-weight: bold;
            color: #000000;
            margin-bottom: 2px;
        }
        
        .cv-item-subtitle {
            font-size: 10px;
            color: #64748b;
            margin-bottom: 2px;
            font-weight: 500;
        }
        
        .cv-item-meta {
            font-size: 9px;
            color: #64748b;
            margin-bottom: 4px;
            font-style: italic;
        }
        
        .cv-item-description {
            font-size: 10px;
            color: #000000;
            line-height: 1.4;
            margin-top: 4px;
        }
        
        /* Skills & Languages - CV Style */
        .skills-languages {
            display: table;
            width: 100%;
            margin-bottom: 10px;
        }
        
        .skills-column, .languages-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding-right: 10px;
        }
        
        .skill-tag, .language-tag {
            display: inline-block;
            background-color: #f5f5f5;
            color: #000000;
            padding: 2px 6px;
            margin: 2px 3px 2px 0;
            border-radius: 8px;
            font-size: 9px;
            border: 1px solid #000000;
        }
        
        .skill-level, .language-proficiency {
            color: #64748b;
            font-weight: 500;
            margin-left: 3px;
        }
        
        /* Social Media - CV Style */
        .social-media-item {
            display: inline-block;
            margin-right: 10px;
            margin-bottom: 5px;
            font-size: 10px;
        }
        
        .social-media-label {
            font-weight: 500;
            color: #000000;
        }
        
        .social-media-link {
            color: #000000;
            text-decoration: none;
        }
        
        /* Certifications, Courses, Organizations, Achievements - CV Style */
        .cert-item, .course-item, .org-item, .achievement-item {
            margin-bottom: 10px;
            padding-bottom: 8px;
            border-bottom: 1px dotted #cbd5e1;
        }
        
        .cert-item:last-child, .course-item:last-child, .org-item:last-child, .achievement-item:last-child {
            border-bottom: none;
        }
        
        /* History Table - Simplified */
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 9px;
        }
        
        .history-table th {
            background-color: #000000;
            color: white;
            padding: 5px 6px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
        }
        
        .history-table td {
            padding: 5px 6px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 9px;
        }
        
        .history-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 6px;
            font-size: 8px;
            font-weight: 500;
        }
        
        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }
        
        .badge-danger {
            background-color: #fee2e2;
            color: #991b1b;
        }
        
        .badge-default {
            background-color: #f5f5f5;
            color: #000000;
        }
        
        .content-wrapper {
            position: relative;
            min-height: calc(100vh - 20mm - 20mm); /* subtract top and bottom margin */
            padding-right:20mm;
            padding-left:20mm;
        }
        .main-content {
            padding-bottom: 0;
            margin-bottom: 0;
            width: 100%;
        }
        
        .footer-wrapper {
            position: absolute;
            bottom: 10mm;
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
            padding: 8px 0;
            text-align: center;
            color: #64748b;
            font-size: 9px;
            border-top: 1px solid #e2e8f0;
            background-color: white;
        }
        
        .page-break {
            page-break-before: always;
        }
        
        .section-content {
            margin-top: 6px;
        }
        
        @media print {
            body {
                font-size: 9px;
                font-family: 'Times New Roman', 'DejaVu Serif', serif !important;
            }
            
            * {
                font-family: 'Times New Roman', 'DejaVu Serif', serif !important;
            }
            
            .section {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body>
    <?php if(isset($headerImage) && $headerImage): ?>
        <img src="<?php echo e($headerImage); ?>" alt="Header" class="header-image" />
    <?php else: ?>
        <div class="header">
            <h1>DATA KANDIDAT</h1>
            <h2><?php echo e($candidate['company']); ?></h2>
        </div>
    <?php endif; ?>
    
    <div class="content-wrapper">
        <div class="main-content">
        <!-- Personal Information - Grid Layout -->
        <div class="section">
            <div class="section-title">Informasi Pribadi</div>
            <div class="personal-info">
                <div class="personal-info-grid">
                    <div class="personal-info-row">
                        <div class="personal-info-label">Nama Lengkap</div>
                        <div class="personal-info-value"><?php echo e($candidate['full_name']); ?></div>
                    </div>
                    <div class="personal-info-row">
                        <div class="personal-info-label">Email</div>
                        <div class="personal-info-value"><?php echo e($candidate['email']); ?></div>
                    </div>
                    <div class="personal-info-row">
                        <div class="personal-info-label">No. Telepon</div>
                        <div class="personal-info-value"><?php echo e($candidate['phone']); ?></div>
                    </div>
                    <div class="personal-info-row">
                        <div class="personal-info-label">Alamat</div>
                        <div class="personal-info-value"><?php echo e($candidate['address']); ?></div>
                    </div>
                    <div class="personal-info-row">
                        <div class="personal-info-label">Tempat & Tanggal Lahir</div>
                        <div class="personal-info-value"><?php echo e($candidate['birth_place']); ?>, <?php echo e($candidate['birth_date'] && $candidate['birth_date'] !== '-' ? (is_string($candidate['birth_date']) ? \Carbon\Carbon::parse($candidate['birth_date'])->format('d F Y') : $candidate['birth_date']->format('d F Y')) : '-'); ?></div>
                    </div>
                    <div class="personal-info-row">
                        <div class="personal-info-label">Jenis Kelamin</div>
                        <div class="personal-info-value"><?php echo e($candidate['gender']); ?></div>
                    </div>
                    <div class="personal-info-row">
                        <div class="personal-info-label">Posisi yang Dilamar</div>
                        <div class="personal-info-value"><?php echo e($candidate['position']); ?></div>
                    </div>
                    <div class="personal-info-row">
                        <div class="personal-info-label">Periode</div>
                        <div class="personal-info-value"><?php echo e($candidate['period']); ?></div>
                    </div>
                    <div class="personal-info-row">
                        <div class="personal-info-label">Tanggal Melamar</div>
                        <div class="personal-info-value"><?php echo e($candidate['applied_at'] && $candidate['applied_at'] !== '-' ? (is_string($candidate['applied_at']) ? \Carbon\Carbon::parse($candidate['applied_at'])->format('d F Y, H:i') : $candidate['applied_at']->format('d F Y, H:i')) : '-'); ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media -->
        <?php if(count($social_media) > 0): ?>
        <div class="section">
            <div class="section-title">Media Sosial</div>
            <div class="section-content">
                <?php $__currentLoopData = $social_media; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $social): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="social-media-item">
                    <span class="social-media-label"><?php echo e($social['platform']); ?>:</span>
                    <span class="social-media-link"><?php echo e($social['url']); ?></span>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Education -->
        <?php if(count($education) > 0): ?>
        <div class="section">
            <div class="section-title">Pendidikan</div>
            <div class="section-content">
                <?php $__currentLoopData = $education; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $edu): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="cv-item">
                    <div class="cv-item-title"><?php echo e($edu['institution']); ?></div>
                    <div class="cv-item-subtitle">
                        <?php echo e($edu['level'] ?? 'Tidak Diketahui'); ?> - <?php echo e($edu['faculty']); ?>

                        <?php if($edu['major']): ?>
                            | <?php echo e($edu['major']); ?>

                        <?php endif; ?>
                    </div>
                    <div class="cv-item-meta">
                        <?php echo e($edu['start_year']); ?> - <?php echo e($edu['end_year'] ?? 'Sekarang'); ?>

                        <?php if($edu['gpa']): ?>
                            | IPK/GPA: <?php echo e($edu['gpa']); ?>

                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Work Experience -->
        <?php if(count($work_experiences) > 0): ?>
        <div class="section">
            <div class="section-title">Pengalaman Kerja</div>
            <div class="section-content">
                <?php $__currentLoopData = $work_experiences; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $exp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="cv-item">
                    <div class="cv-item-title"><?php echo e($exp['position']); ?></div>
                    <div class="cv-item-subtitle"><?php echo e($exp['company']); ?></div>
                    <div class="cv-item-meta">
                        <?php echo e($exp['start_date'] && $exp['start_date'] !== '-' ? (is_string($exp['start_date']) ? \Carbon\Carbon::parse($exp['start_date'])->format('d F Y') : $exp['start_date']->format('d F Y')) : '-'); ?> - 
                        <?php echo e($exp['end_date'] && $exp['end_date'] !== '-' ? (is_string($exp['end_date']) ? \Carbon\Carbon::parse($exp['end_date'])->format('d F Y') : $exp['end_date']->format('d F Y')) : 'Sekarang'); ?>

                    </div>
                    <?php if($exp['description']): ?>
                    <div class="cv-item-description"><?php echo e($exp['description']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Skills & Languages -->
        <?php if(count($skills) > 0 || count($languages) > 0): ?>
        <div class="section">
            <div class="section-title">Keterampilan & Bahasa</div>
            <div class="section-content">
                <div class="skills-languages">
                    <?php if(count($skills) > 0): ?>
                    <div class="skills-column">
                        <div style="font-weight: bold; margin-bottom: 5px; color: #000000; font-size: 10px;">Keterampilan</div>
                        <?php $__currentLoopData = $skills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="skill-tag">
                            <?php echo e($skill['name']); ?><span class="skill-level"><?php echo e($skill['level']); ?></span>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php endif; ?>
                    <?php if(count($languages) > 0): ?>
                    <div class="languages-column">
                        <div style="font-weight: bold; margin-bottom: 5px; color: #000000; font-size: 10px;">Bahasa</div>
                        <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $lang): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="language-tag">
                            <?php echo e($lang['name']); ?><span class="language-proficiency"><?php echo e($lang['proficiency']); ?></span>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Certifications -->
        <?php if(count($certifications) > 0): ?>
        <div class="section">
            <div class="section-title">Sertifikasi</div>
            <div class="section-content">
                <?php $__currentLoopData = $certifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="cert-item">
                    <div class="cv-item-title"><?php echo e($cert['name']); ?></div>
                    <div class="cv-item-subtitle"><?php echo e($cert['issuer']); ?></div>
                    <div class="cv-item-meta">
                        <?php echo e($cert['date'] && $cert['date'] !== '-' ? (is_string($cert['date']) ? \Carbon\Carbon::parse($cert['date'])->format('d F Y') : $cert['date']->format('d F Y')) : '-'); ?>

                        <?php if($cert['expiry_date'] && $cert['expiry_date'] !== '-'): ?>
                            - <?php echo e(is_string($cert['expiry_date']) ? \Carbon\Carbon::parse($cert['expiry_date'])->format('d F Y') : $cert['expiry_date']->format('d F Y')); ?>

                        <?php endif; ?>
                        <?php if($cert['credential_id']): ?>
                            | ID: <?php echo e($cert['credential_id']); ?>

                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Courses -->
        <?php if(count($courses) > 0): ?>
        <div class="section">
            <div class="section-title">Pelatihan & Kursus</div>
            <div class="section-content">
                <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="course-item">
                    <div class="cv-item-title"><?php echo e($course['name']); ?></div>
                    <div class="cv-item-subtitle"><?php echo e($course['institution']); ?></div>
                    <div class="cv-item-meta">
                        Selesai: <?php echo e($course['completion_date'] && $course['completion_date'] !== '-' ? (is_string($course['completion_date']) ? \Carbon\Carbon::parse($course['completion_date'])->format('d F Y') : $course['completion_date']->format('d F Y')) : '-'); ?>

                    </div>
                    <?php if($course['description']): ?>
                    <div class="cv-item-description"><?php echo e($course['description']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Organizations -->
        <?php if(count($organizations) > 0): ?>
        <div class="section">
            <div class="section-title">Organisasi</div>
            <div class="section-content">
                <?php $__currentLoopData = $organizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $org): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="org-item">
                    <div class="cv-item-title"><?php echo e($org['name']); ?></div>
                    <div class="cv-item-subtitle"><?php echo e($org['position']); ?></div>
                    <div class="cv-item-meta"><?php echo e($org['start_year']); ?> - <?php echo e($org['end_year'] ?? 'Sekarang'); ?></div>
                    <?php if($org['description']): ?>
                    <div class="cv-item-description"><?php echo e($org['description']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Achievements -->
        <?php if(count($achievements) > 0): ?>
        <div class="section">
            <div class="section-title">Pencapaian</div>
            <div class="section-content">
                <?php $__currentLoopData = $achievements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $achievement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <div class="achievement-item">
                    <div class="cv-item-title"><?php echo e($achievement['title']); ?></div>
                    <div class="cv-item-subtitle"><?php echo e($achievement['issuer']); ?></div>
                    <div class="cv-item-meta">
                        <?php echo e($achievement['date'] && $achievement['date'] !== '-' ? (is_string($achievement['date']) ? \Carbon\Carbon::parse($achievement['date'])->format('d F Y') : $achievement['date']->format('d F Y')) : '-'); ?>

                    </div>
                    <?php if($achievement['description']): ?>
                    <div class="cv-item-description"><?php echo e($achievement['description']); ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Application History -->
        <?php if(count($history) > 0): ?>
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
                        <?php $__currentLoopData = $history; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $hist): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td>
                                <span class="badge 
                                    <?php if($hist->status->code === 'approved'): ?> badge-success
                                    <?php elseif($hist->status->code === 'rejected'): ?> badge-danger
                                    <?php else: ?> badge-default
                                    <?php endif; ?>">
                                    <?php echo e($hist->status->name); ?>

                                </span>
                            </td>
                            <td>
                                <?php echo e($hist->processed_at ? (is_string($hist->processed_at) ? \Carbon\Carbon::parse($hist->processed_at)->format('d F Y, H:i') : $hist->processed_at->format('d F Y, H:i')) : '-'); ?>

                            </td>
                            <td><?php echo e($hist->reviewer ? $hist->reviewer->name : '-'); ?></td>
                            <td><?php echo e($hist->score ?? '-'); ?></td>
                            <td><?php echo e($hist->notes ?? '-'); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
        </div>
        
        <div class="footer-wrapper">
        <?php if(isset($footerImage) && $footerImage): ?>
            <img src="<?php echo e($footerImage); ?>" alt="Footer" class="footer-image" />
        <?php else: ?>
            <div class="footer">
                <p>Dokumen ini dihasilkan secara otomatis pada <?php echo e($export_date->format('d F Y, H:i')); ?></p>
                <p>Sistem E-Recruitment - <?php echo e($candidate['company']); ?></p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
<?php /**PATH D:\projects\e-recruitment\E-Recruitment-Laravel-Admin\resources\views/exports/administration-candidate.blade.php ENDPATH**/ ?>