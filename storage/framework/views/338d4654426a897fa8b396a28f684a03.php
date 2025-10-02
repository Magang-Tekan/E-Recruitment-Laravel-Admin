<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV - <?php echo e($user->name ?? 'CV'); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 10px 20px; /* Reduced top padding */
        }

        .header {
            text-align: center;
            margin-bottom: 20px; /* Reduced margin */
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px; /* Reduced padding */
        }

        .header h1 {
            font-size: 24px;
            color: #2563eb;
            margin-bottom: 3px; /* Reduced margin */
        }

        .header .contact-info {
            font-size: 11px;
            color: #666;
            line-height: 1.3; /* Added line height */
        }

        .section {
            margin-bottom: 15px; /* Reduced margin */
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #2563eb;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 3px; /* Reduced padding */
            margin-bottom: 10px; /* Reduced margin */
        }

        .item {
            margin-bottom: 10px; /* Reduced margin */
        }

        .item-title {
            font-weight: bold;
            font-size: 13px;
            margin-bottom: 2px; /* Added margin */
        }

        .item-subtitle {
            font-style: italic;
            color: #666;
            font-size: 11px;
            margin-bottom: 2px; /* Added margin */
        }

        .item-description {
            margin-top: 3px; /* Reduced margin */
            text-align: justify;
            font-size: 11px; /* Added font size */
        }

        .two-column {
            display: table;
            width: 100%;
            margin-top: -5px; /* Added negative margin to pull content up */
        }

        .left-column {
            display: table-cell;
            width: 65%;
            vertical-align: top;
            padding-right: 20px;
        }

        .right-column {
            display: table-cell;
            width: 35%;
            vertical-align: top;
        }

        .skills-list {
            list-style: none;
            margin-top: 5px; /* Added margin */
        }

        .skills-list li {
            background: #f3f4f6;
            padding: 2px 6px; /* Reduced padding */
            margin: 2px 0; /* Reduced margin */
            border-radius: 3px;
            font-size: 11px;
        }

        .about-me {
            background: #f9fafb;
            padding: 10px; /* Reduced padding */
            border-radius: 5px;
            margin-bottom: 15px; /* Reduced margin */
            text-align: justify;
            font-size: 11px; /* Added font size */
        }

        /* Added new style for profile image container */
        .profile-image-container {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px; /* Reduced margin */
        }

        .profile-image-container img {
            width: 70px; /* Reduced size */
            height: 70px; /* Reduced size */
            border-radius: 50%;
            object-fit: cover;
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="profile-image-container">
                <?php if($profile && $profile->profile_image && Storage::disk('public')->exists($profile->profile_image)): ?>
                    <img src="<?php echo e(Storage::disk('public')->path($profile->profile_image)); ?>" />
                <?php endif; ?>
                <div>
                    <h1><?php echo e($user->name ?? 'Nama Lengkap'); ?></h1>
                    <div class="contact-info">
                        <?php echo e($user->email ?? ''); ?>

                        <?php if($profile && $profile->phone_number): ?>
                            | <?php echo e($profile->phone_number); ?>

                        <?php endif; ?>
                        <?php if($profile && $profile->address): ?>
                            <br><?php echo e($profile->address); ?>

                            <?php if($profile->city): ?>, <?php echo e($profile->city); ?><?php endif; ?>
                            <?php if($profile->province): ?>, <?php echo e($profile->province); ?><?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="two-column">
            <!-- Left Column -->
            <div class="left-column">
                <!-- About Me -->
                <?php if($profile && $profile->about_me): ?>
                <div class="section">
                    <div class="section-title">TENTANG SAYA</div>
                    <div class="about-me">
                        <?php echo e($profile->about_me); ?>

                    </div>
                </div>
                <?php endif; ?>

                <!-- Work Experience -->
                <?php if(isset($workExperiences) && $workExperiences->count() > 0): ?>
                <div class="section">
                    <div class="section-title">PENGALAMAN KERJA</div>
                    <?php $__currentLoopData = $workExperiences; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $work): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="item">
                        <div class="item-title"><?php echo e($work->job_title ?? 'Job Title'); ?></div>
                        <div class="item-subtitle">
                            <?php echo e($work->company_name ?? 'Company Name'); ?> |
                            <?php echo e($work->employment_status ?? 'Full Time'); ?> |
                            <?php
                                $startMonth = '';
                                if ($work->start_month) {
                                    try {
                                        $startMonth = DateTime::createFromFormat('!m', $work->start_month)->format('M');
                                    } catch (Exception $e) {
                                        $startMonth = $work->start_month;
                                    }
                                }
                            ?>
                            <?php echo e($startMonth); ?> <?php echo e($work->start_year ?? ''); ?> -
                            <?php if($work->is_current_job): ?>
                                Sekarang
                            <?php else: ?>
                                <?php
                                    $endMonth = '';
                                    if ($work->end_month) {
                                        try {
                                            $endMonth = DateTime::createFromFormat('!m', $work->end_month)->format('M');
                                        } catch (Exception $e) {
                                            $endMonth = $work->end_month;
                                        }
                                    }
                                ?>
                                <?php echo e($endMonth); ?> <?php echo e($work->end_year ?? ''); ?>

                            <?php endif; ?>
                        </div>
                        <?php if($work->job_description): ?>
                        <div class="item-description"><?php echo e($work->job_description); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php endif; ?>

                <!-- Pendidikan -->
                <?php if(isset($educations) && $educations->count() > 0): ?>
                <div class="section">
                    <div class="section-title">PENDIDIKAN</div>
                    <?php $__currentLoopData = $educations->sortByDesc('year_in'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $education): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="item">
                        <div class="item-title"><?php echo e($education->educationLevel->name ?? '-'); ?></div>
                        <div class="item-subtitle">
                            <?php echo e($education->institution_name); ?> | <?php echo e($education->year_in ?? ''); ?> - 
                            <?php echo e($education->year_out ?? 'Sekarang'); ?>

                        </div>
                        <div class="item-description">
                            <?php
                                // Simple major name extraction
                                $majorName = 'N/A';
                                if($education->major) {
                                    if(is_object($education->major) && isset($education->major->name)) {
                                        $majorName = $education->major->name;
                                    } else if(is_string($education->major)) {
                                        $majorData = json_decode($education->major, true);
                                        if($majorData && isset($majorData['name'])) {
                                            $majorName = $majorData['name'];
                                        } else {
                                            $majorName = $education->major;
                                        }
                                    }
                                }
                            ?>
                            Fakultas <?php echo e($education->faculty); ?> - <?php echo e($majorName); ?><br>
                            IPK: <?php echo e($education->gpa); ?>

                        </div>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php endif; ?>

                <!-- Organizations -->
                <?php if(isset($organizations) && $organizations->count() > 0): ?>
                <div class="section">
                    <div class="section-title">ORGANISASI</div>
                    <?php $__currentLoopData = $organizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $org): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="item">
                        <div class="item-title"><?php echo e($org->position ?? 'Posisi'); ?></div>
                        <div class="item-subtitle">
                            <?php echo e($org->organization_name ?? 'Nama Organisasi'); ?> |
                            <?php echo e($org->start_month ?? ''); ?> <?php echo e($org->start_year ?? ''); ?> -
                            <?php if($org->is_active): ?>
                                Sekarang
                            <?php else: ?>
                                <?php echo e($org->end_month ?? ''); ?> <?php echo e($org->end_year ?? ''); ?>

                            <?php endif; ?>
                        </div>
                        <?php if($org->description): ?>
                        <div class="item-description"><?php echo e($org->description); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php endif; ?>

                <!-- Achievements -->
                <?php if(isset($achievements) && $achievements->count() > 0): ?>
                <div class="section">
                    <div class="section-title">PRESTASI</div>
                    <?php $__currentLoopData = $achievements; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $achievement): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="item">
                        <div class="item-title"><?php echo e($achievement->title ?? 'Judul Prestasi'); ?></div>
                        <div class="item-subtitle">
                            <?php echo e($achievement->level ?? 'Tingkat'); ?> | <?php echo e($achievement->month ?? ''); ?> <?php echo e($achievement->year ?? ''); ?>

                        </div>
                        <?php if($achievement->description): ?>
                        <div class="item-description"><?php echo e($achievement->description); ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right Column -->
            <div class="right-column">
                <!-- Personal Info -->
                <?php if($profile): ?>
                <div class="section">
                    <div class="section-title">INFORMASI PRIBADI</div>
                    <div style="font-size: 11px;">
                        <?php if($profile->date_of_birth): ?>
                            <strong>Tanggal Lahir:</strong><br>
                            <?php if($profile->place_of_birth): ?><?php echo e($profile->place_of_birth); ?>, <?php endif; ?>
                            <?php echo e(date('d M Y', strtotime($profile->date_of_birth))); ?><br><br>
                        <?php endif; ?>
                        
                        <?php if($profile->gender): ?>
                            <strong>Jenis Kelamin:</strong><br>
                            <?php echo e($profile->gender == 'male' ? 'Laki-laki' : ($profile->gender == 'female' ? 'Perempuan' : ucfirst($profile->gender))); ?><br><br>
                        <?php endif; ?>
                        
                        <?php if($profile->no_ektp): ?>
                            <strong>No. E-KTP:</strong><br>
                            <?php echo e($profile->no_ektp); ?><br><br>
                        <?php endif; ?>
                        
                        <?php if($profile->npwp): ?>
                            <strong>NPWP:</strong><br>
                            <?php echo e($profile->npwp); ?><br><br>
                        <?php endif; ?>

                        <?php if($profile->address): ?>
                            <strong>Alamat:</strong><br>
                            <?php echo e($profile->address); ?>

                            <?php if($profile->rt): ?>, RT <?php echo e($profile->rt); ?><?php endif; ?>
                            <?php if($profile->rw): ?>, RW <?php echo e($profile->rw); ?><?php endif; ?>
                            <?php if($profile->village): ?>, Kel. <?php echo e($profile->village); ?><?php endif; ?>
                            <?php if($profile->district): ?>, Kec. <?php echo e($profile->district); ?><?php endif; ?>
                            <?php if($profile->city): ?>, <?php echo e($profile->city); ?><?php endif; ?>
                            <?php if($profile->province): ?>, <?php echo e($profile->province); ?><?php endif; ?>
                            <br><br>
                        <?php endif; ?>

                        <?php if($profile->phone_number): ?>
                            <strong>No. Telepon:</strong><br>
                            <?php echo e($profile->phone_number); ?><br><br>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Skills -->
                <?php if(isset($skills) && $skills->count() > 0): ?>
                <div class="section">
                    <div class="section-title">KEAHLIAN</div>
                    <ul class="skills-list">
                        <?php $__currentLoopData = $skills; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $skill): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($skill->skill_name ?? 'Skill'); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Courses -->
                <?php if(isset($courses) && $courses->count() > 0): ?>
                <div class="section">
                    <div class="section-title">KURSUS</div>
                    <ul class="skills-list">
                        <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <?php echo e($course->course_name); ?>

                            <?php if($course->institution): ?>
                                - <?php echo e($course->institution); ?>

                            <?php endif; ?>
                            <?php if($course->completion_date): ?>
                                (<?php echo e(\Carbon\Carbon::parse($course->completion_date)->format('M Y')); ?>)
                            <?php endif; ?>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Certifications -->
                <?php if(isset($certifications) && $certifications->count() > 0): ?>
                <div class="section">
                    <div class="section-title">SERTIFIKASI</div>
                    <ul class="skills-list">
                        <?php $__currentLoopData = $certifications; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cert): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <?php echo e($cert->certification_name); ?>

                            <?php if($cert->issuing_organization): ?>
                                - <?php echo e($cert->issuing_organization); ?>

                            <?php endif; ?>
                            <?php if($cert->issue_date): ?>
                                (<?php echo e(\Carbon\Carbon::parse($cert->issue_date)->format('M Y')); ?>)
                            <?php endif; ?>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Languages -->
                <?php if(isset($languages) && $languages->count() > 0): ?>
                <div class="section">
                    <div class="section-title">BAHASA</div>
                    <ul class="skills-list">
                        <?php $__currentLoopData = $languages; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $language): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li>
                            <?php echo e($language->language_name); ?>

                            <?php if($language->proficiency_level): ?>
                                (<?php echo e($language->proficiency_level); ?>)
                            <?php endif; ?>
                        </li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Social Media -->
                <?php if(isset($socialMedia) && $socialMedia->count() > 0): ?>
                <div class="section">
                    <div class="section-title">MEDIA SOSIAL</div>
                    <div style="font-size: 10px;">
                        <?php $__currentLoopData = $socialMedia; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $social): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <strong><?php echo e($social->platform_name ?? 'Platform'); ?>:</strong><br>
                        <?php echo e($social->url ?? $social->profile_url ?? '#'); ?><br><br>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
<?php /**PATH /var/www/html/resources/views/cv/template.blade.php ENDPATH**/ ?>