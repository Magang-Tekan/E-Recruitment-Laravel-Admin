import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ApplicationInfo } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Eye, CheckCircle, Clock, XCircle, ChevronDown, ChevronUp, Download, FileText, User } from 'lucide-react';
import { format, formatDistanceStrict } from 'date-fns';
import { Pagination } from '@/components/ui/pagination';
import { RecruitmentHistoryTimeline } from '@/components/recruitment-history-timeline';
import { Separator } from '@/components/ui/separator';
import React, { useState } from 'react';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import StageActionDialog from '@/components/stage-action-dialog';
import { ThumbsUp, ThumbsDown } from 'lucide-react';


interface Props {
    candidates: {
        data: ApplicationInfo[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters?: {
        company?: string;
        vacancy?: string;
    };
    companyInfo?: {
        name: string;
    };
    periodInfo?: {
        name: string;
        start_date: string;
        end_date: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Recruitment',
        href: '/dashboard/recruitment',
    },
    {
        title: 'Administration',
        href: '/dashboard/recruitment/administration',
    },
];

export default function Administration({ candidates, filters, companyInfo, periodInfo }: Props) {
    const [expandedRows, setExpandedRows] = useState<Set<number>>(new Set());
    const [actionDialog, setActionDialog] = useState<{
        isOpen: boolean;
        candidateId: number;
        action: 'accept' | 'reject';
    } | null>(null);

    const toggleRow = (candidateId: number) => {
        setExpandedRows(prev => {
            const newSet = new Set(prev);
            if (newSet.has(candidateId)) {
                newSet.delete(candidateId);
            } else {
                newSet.add(candidateId);
            }
            return newSet;
        });
    };

    const formatDate = (date: string) => {
        try {
            return format(new Date(date), 'dd/MM/yyyy HH:mm');
        } catch (error) {
            return '-';
        }
    };

    const formatDateOnly = (date: string) => {
        try {
            return format(new Date(date), 'dd MMMM yyyy');
        } catch (error) {
            return '-';
        }
    };

    const handleExportCV = (candidateId: number) => {
        window.open(`/dashboard/candidates/${candidateId}/cv/download`, '_blank');
    };

    const handlePageChange = (page: number) => {
        router.get(
            '/dashboard/recruitment/administration',
            {
                ...(filters || {}),
                page,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const getReviewStatus = (candidate: ApplicationInfo) => {
        // PRIORITY 1: Fix Review Status Logic - hanya cek hasScore (konsisten dengan assessment)
        // Find specific administration history entry
        const administrationHistory = candidate.history?.find(h => 
            h.stage === 'administrative_selection' || h.status_code === 'admin_selection'
        );
        
        // Check if candidate is rejected
        const isRejected = candidate.status?.code === 'rejected';
        
        // Check if admin has given score (this is the indicator that admin has reviewed)
        // Score must exist and be a valid number (not null, not undefined, not empty)
        const hasScore = administrationHistory?.score !== null && 
            administrationHistory?.score !== undefined &&
            administrationHistory?.score !== '' &&
            !isNaN(Number(administrationHistory.score));
        
        // Check if administration has been started
        const hasStarted = administrationHistory?.processed_at !== null && 
            administrationHistory?.processed_at !== undefined;
        
        // Logic:
        // 1. If rejected -> Rejected
        // 2. If has score -> Reviewed (admin has given score/nilai, meaning reviewed)
        // 3. If started but no score -> Pending Review (waiting for admin to give score)
        // 4. If no history or not started -> Not Started
        
        if (isRejected) {
            return { status: 'rejected', icon: XCircle, color: 'bg-red-100 text-red-800 border-red-200' };
        } else if (hasScore) {
            return { status: 'reviewed', icon: CheckCircle, color: 'bg-green-100 text-green-800 border-green-200' };
        } else if (hasStarted) {
            return { status: 'pending', icon: Clock, color: 'bg-yellow-100 text-yellow-800 border-yellow-200' };
        } else {
            return { status: 'not_started', icon: XCircle, color: 'bg-gray-100 text-gray-800 border-gray-200' };
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Administration" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Navigation Menu */}
                <div className="flex w-full border-b">
                    <button
                        className="flex-1 border-b-2 border-primary px-4 py-2 text-sm font-medium text-primary"
                        onClick={() => router.visit('/dashboard/recruitment/administration', {
                            data: { company: filters?.company, vacancy: filters?.vacancy }
                        })}
                    >
                        Administration
                    </button>
                    <button
                        className="flex-1 px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                        onClick={() => router.visit('/dashboard/recruitment/assessment', {
                            data: { company: filters?.company, period: filters?.period, vacancy: filters?.vacancy }
                        })}
                    >
                        Assessment
                    </button>
                    <button
                        className="flex-1 px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                        onClick={() => router.visit('/dashboard/recruitment/interview', {
                            data: { company: filters?.company, period: filters?.period, vacancy: filters?.vacancy }
                        })}
                    >
                        Interview
                    </button>
                    <button
                        className="flex-1 px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                        onClick={() => router.visit('/dashboard/recruitment/reports', {
                            data: { company: filters?.company, period: filters?.period, vacancy: filters?.vacancy }
                        })}
                    >
                        Reports
                    </button>
                </div>

                {/* Company and Period Info */}
                {(companyInfo || periodInfo) && (
                    <Card>
                        <CardContent className="pt-6">
                            <div className="space-y-2">
                                {companyInfo && (
                                    <h2 className="text-2xl font-semibold text-gray-800">
                                        {companyInfo.name}
                                    </h2>
                                )}
                                {periodInfo && (
                                    <div className="text-sm text-gray-600">
                                        <p className="font-medium">{periodInfo.name}</p>
                                        <p>
                                            Period: {format(new Date(periodInfo.start_date), 'dd MMM yyyy')} 
                                            {' - '} 
                                            {format(new Date(periodInfo.end_date), 'dd MMM yyyy')}
                                        </p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Main Content */}
                <div>
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-2xl font-semibold">Administration Stage</h2>
                        <div className="text-sm text-muted-foreground">
                            Total: {candidates.total} candidates
                        </div>
                    </div>

                    {/* Summary Cards */}
                    <div className="mb-6 grid grid-cols-1 gap-4 md:grid-cols-4">
                        {(() => {
                            const reviewed = candidates.data.filter(c => {
                                const status = getReviewStatus(c);
                                return status.status === 'reviewed';
                            }).length;
                            const pending = candidates.data.filter(c => {
                                const status = getReviewStatus(c);
                                return status.status === 'pending';
                            }).length;
                            const notStarted = candidates.data.filter(c => {
                                const status = getReviewStatus(c);
                                return status.status === 'not_started';
                            }).length;

                            return (
                                <>
                                    <Card>
                                        <CardContent className="p-4">
                                            <div className="flex items-center space-x-2">
                                                <CheckCircle className="h-5 w-5 text-green-600" />
                                                <div>
                                                    <p className="text-sm font-medium text-muted-foreground">Reviewed</p>
                                                    <p className="text-2xl font-bold text-green-600">{reviewed}</p>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                    <Card>
                                        <CardContent className="p-4">
                                            <div className="flex items-center space-x-2">
                                                <Clock className="h-5 w-5 text-yellow-600" />
                                                <div>
                                                    <p className="text-sm font-medium text-muted-foreground">Pending Review</p>
                                                    <p className="text-2xl font-bold text-yellow-600">{pending}</p>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                    <Card>
                                        <CardContent className="p-4">
                                            <div className="flex items-center space-x-2">
                                                <XCircle className="h-5 w-5 text-gray-600" />
                                                <div>
                                                    <p className="text-sm font-medium text-muted-foreground">Not Started</p>
                                                    <p className="text-2xl font-bold text-gray-600">{notStarted}</p>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                    <Card>
                                        <CardContent className="p-4">
                                            <div className="flex items-center space-x-2">
                                                <Eye className="h-5 w-5 text-blue-600" />
                                                <div>
                                                    <p className="text-sm font-medium text-muted-foreground">Total</p>
                                                    <p className="text-2xl font-bold text-blue-600">{candidates.total}</p>
                                                </div>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </>
                            );
                        })()}
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Candidates List</CardTitle>
                            <CardDescription>View and manage candidates in administration stage</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="relative overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-muted text-muted-foreground">
                                        <tr>
                                            <th className="p-4 font-medium">No</th>
                                            <th className="p-4 font-medium">Foto</th>
                                            <th className="p-4 font-medium">Nama</th>
                                            <th className="p-4 font-medium">Umur</th>
                                            <th className="p-4 font-medium">Gender</th>
                                            <th className="p-4 font-medium">Position</th>
                                            <th className="p-4 font-medium">Pendidikan</th>
                                            <th className="p-4 font-medium">Alamat</th>
                                            <th className="p-4 font-medium">Score</th>
                                            <th className="p-4 font-medium">Review Status</th>
                                            <th className="p-4 font-medium">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {candidates.data.length > 0 ? (
                                            candidates.data.map((candidate, index) => {
                                                // Find specific administration history entry
                                                const administrationHistory = candidate.history?.find(h => 
                                                    h.stage === 'administrative_selection' || h.status_code === 'admin_selection'
                                                );
                                                const startedAt = administrationHistory?.processed_at;
                                                const completedAt = administrationHistory?.completed_at;
                                                const duration = startedAt && completedAt ? 
                                                    formatDistanceStrict(new Date(completedAt), new Date(startedAt)) : 
                                                    '-';
                                                const reviewStatus = getReviewStatus(candidate);
                                                const StatusIcon = reviewStatus.icon;
                                                
                                                // Get highest education and address
                                                const highestEdu = candidate.candidate?.highest_education;
                                                const educationText = highestEdu 
                                                    ? `${highestEdu.level || '-'} - ${highestEdu.major || highestEdu.faculty || '-'}`
                                                    : '-';
                                                const address = candidate.candidate?.profile?.address || '-';
                                                const profileImage = candidate.candidate?.profile?.profile_image;
                                                const age = candidate.candidate?.age;
                                                const gender = candidate.candidate?.profile?.gender;
                                                
                                                // Get initials for avatar fallback
                                                const getInitials = (name: string) => {
                                                    return name
                                                        .split(' ')
                                                        .map(n => n[0])
                                                        .join('')
                                                        .toUpperCase()
                                                        .slice(0, 2);
                                                };
                                                
                                                return (
                                                    <React.Fragment key={candidate.id}>
                                                        <tr className="border-b hover:bg-muted/50">
                                                            <td className="p-4">{(candidates.current_page - 1) * candidates.per_page + index + 1}</td>
                                                            <td className="p-4">
                                                                <Avatar className="h-10 w-10">
                                                                    {profileImage ? (
                                                                        <AvatarImage 
                                                                            src={profileImage.startsWith('http') || profileImage.startsWith('/') 
                                                                                ? profileImage 
                                                                                : `/storage/${profileImage}`} 
                                                                            alt={candidate.user.name}
                                                                        />
                                                                    ) : null}
                                                                    <AvatarFallback className="bg-gray-200 text-gray-600">
                                                                        {getInitials(candidate.user.name)}
                                                                    </AvatarFallback>
                                                                </Avatar>
                                                            </td>
                                                            <td className="p-4 font-medium">{candidate.user.name}</td>
                                                            <td className="p-4">{age ? `${age} tahun` : '-'}</td>
                                                            <td className="p-4">
                                                                {gender ? (
                                                                    <Badge variant="outline" className="text-xs">
                                                                        {gender === 'Laki-laki' || gender === 'Male' || gender === 'L' ? 'L' : 
                                                                         gender === 'Perempuan' || gender === 'Female' || gender === 'P' ? 'P' : gender}
                                                                    </Badge>
                                                                ) : '-'}
                                                            </td>
                                                            <td className="p-4">{candidate.vacancy_period.vacancy.title}</td>
                                                            <td className="p-4">
                                                                <div className="text-sm">
                                                                    {educationText}
                                                                </div>
                                                            </td>
                                                            <td className="p-4">
                                                                <div className="text-sm max-w-xs truncate" title={address}>
                                                                    {address}
                                                                </div>
                                                            </td>
                                                            <td className="p-4 font-medium">
                                                                {candidate.score ? (
                                                                    <span className="text-green-700">
                                                                        {Number(candidate.score).toFixed(2)}
                                                                    </span>
                                                                ) : '-'}
                                                            </td>
                                                            <td className="p-4">
                                                                <Badge variant="outline" className={reviewStatus.color}>
                                                                    <StatusIcon className="mr-1 h-3 w-3" />
                                                                    {reviewStatus.status === 'reviewed' ? 'Reviewed' : 
                                                                     reviewStatus.status === 'pending' ? 'Pending Review' : 
                                                                     reviewStatus.status === 'rejected' ? 'Rejected' : 'Not Started'}
                                                                </Badge>
                                                            </td>
                                                            <td className="p-4">
                                                                <div className="flex items-center gap-2">
                                                                    <Button
                                                                        variant="outline"
                                                                        size="icon"
                                                                        onClick={() => toggleRow(candidate.id)}
                                                                    >
                                                                        {expandedRows.has(candidate.id) ? (
                                                                            <ChevronUp className="h-4 w-4" />
                                                                        ) : (
                                                                            <ChevronDown className="h-4 w-4" />
                                                                        )}
                                                                    </Button>
                                                                    {/* Show action buttons only if not reviewed and not rejected */}
                                                                    {reviewStatus.status !== 'reviewed' && reviewStatus.status !== 'rejected' && (
                                                                        <div className="flex items-center gap-1">
                                                                            <Button
                                                                                variant="outline"
                                                                                size="sm"
                                                                                className="bg-green-50 text-green-700 border-green-200 hover:bg-green-100 hover:text-green-800"
                                                                                onClick={() => setActionDialog({ isOpen: true, candidateId: candidate.id, action: 'accept' })}
                                                                            >
                                                                                <ThumbsUp className="h-4 w-4 mr-1" />
                                                                                Lolos
                                                                            </Button>
                                                                            <Button
                                                                                variant="outline"
                                                                                size="sm"
                                                                                className="bg-red-50 text-red-700 border-red-200 hover:bg-red-100 hover:text-red-800"
                                                                                onClick={() => setActionDialog({ isOpen: true, candidateId: candidate.id, action: 'reject' })}
                                                                            >
                                                                                <ThumbsDown className="h-4 w-4 mr-1" />
                                                                                Tolak
                                                                            </Button>
                                                                        </div>
                                                                    )}
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        {expandedRows.has(candidate.id) && (
                                                            <tr>
                                                                <td colSpan={11} className="p-0">
                                                                <div className="bg-gray-50 p-6">
                                                                    <div className="flex items-center justify-between mb-4">
                                                                        <h3 className="text-lg font-semibold">Detail Pelamar</h3>
                                                                        <div className="flex items-center gap-2">
                                                                            {/* Show action buttons in detail section only if not reviewed and not rejected */}
                                                                            {reviewStatus.status !== 'reviewed' && reviewStatus.status !== 'rejected' && (
                                                                                <>
                                                                                    <Button
                                                                                        variant="outline"
                                                                                        size="sm"
                                                                                        className="bg-green-50 text-green-700 border-green-200 hover:bg-green-100 hover:text-green-800"
                                                                                        onClick={() => setActionDialog({ isOpen: true, candidateId: candidate.id, action: 'accept' })}
                                                                                    >
                                                                                        <ThumbsUp className="mr-2 h-4 w-4" />
                                                                                        Lolos
                                                                                    </Button>
                                                                                    <Button
                                                                                        variant="outline"
                                                                                        size="sm"
                                                                                        className="bg-red-50 text-red-700 border-red-200 hover:bg-red-100 hover:text-red-800"
                                                                                        onClick={() => setActionDialog({ isOpen: true, candidateId: candidate.id, action: 'reject' })}
                                                                                    >
                                                                                        <ThumbsDown className="mr-2 h-4 w-4" />
                                                                                        Tolak
                                                                                    </Button>
                                                                                </>
                                                                            )}
                                                                            <Button
                                                                                variant="outline"
                                                                                size="sm"
                                                                                onClick={() => handleExportCV(candidate.id)}
                                                                            >
                                                                                <Download className="mr-2 h-4 w-4" />
                                                                                Export CV
                                                                            </Button>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                                        {/* Personal Information */}
                                                                        <Card>
                                                                            <CardHeader>
                                                                                <CardTitle className="text-base">Informasi Pribadi</CardTitle>
                                                                            </CardHeader>
                                                                            <CardContent className="space-y-3">
                                                                                {candidate.candidate?.profile ? (
                                                                                    <>
                                                                                        <div>
                                                                                            <dt className="text-sm font-medium text-gray-600">Nama Lengkap</dt>
                                                                                            <dd className="text-sm">{candidate.candidate.profile.full_name || candidate.user?.name || '-'}</dd>
                                                                                        </div>
                                                                                        <div>
                                                                                            <dt className="text-sm font-medium text-gray-600">Email</dt>
                                                                                            <dd className="text-sm">{candidate.user?.email || '-'}</dd>
                                                                                        </div>
                                                                                        <div>
                                                                                            <dt className="text-sm font-medium text-gray-600">No. Telepon</dt>
                                                                                            <dd className="text-sm">{candidate.candidate.profile.phone_number || '-'}</dd>
                                                                                        </div>
                                                                                        <div>
                                                                                            <dt className="text-sm font-medium text-gray-600">Alamat</dt>
                                                                                            <dd className="text-sm">{candidate.candidate.profile.address || '-'}</dd>
                                                                                        </div>
                                                                                        <div>
                                                                                            <dt className="text-sm font-medium text-gray-600">Tempat & Tanggal Lahir</dt>
                                                                                            <dd className="text-sm">
                                                                                                {candidate.candidate.profile.place_of_birth || '-'}, {' '}
                                                                                                {candidate.candidate.profile.date_of_birth ? 
                                                                                                    formatDateOnly(candidate.candidate.profile.date_of_birth) : '-'}
                                                                                            </dd>
                                                                                        </div>
                                                                                        <div>
                                                                                            <dt className="text-sm font-medium text-gray-600">Jenis Kelamin</dt>
                                                                                            <dd className="text-sm">{candidate.candidate.profile.gender || '-'}</dd>
                                                                                        </div>
                                                                                    </>
                                                                                ) : (
                                                                                    <>
                                                                                        <div>
                                                                                            <dt className="text-sm font-medium text-gray-600">Nama</dt>
                                                                                            <dd className="text-sm">{candidate.user?.name || '-'}</dd>
                                                                                        </div>
                                                                                        <div>
                                                                                            <dt className="text-sm font-medium text-gray-600">Email</dt>
                                                                                            <dd className="text-sm">{candidate.user?.email || '-'}</dd>
                                                                                        </div>
                                                                                        <p className="text-sm text-gray-500 mt-2">Data profil lengkap belum tersedia</p>
                                                                                    </>
                                                                                )}
                                                                            </CardContent>
                                                                        </Card>

                                                                        {/* Education */}
                                                                        <Card>
                                                                            <CardHeader>
                                                                                <CardTitle className="text-base">Pendidikan</CardTitle>
                                                                            </CardHeader>
                                                                            <CardContent>
                                                                                {candidate.candidate?.education && candidate.candidate.education.length > 0 ? (
                                                                                    <div className="space-y-4">
                                                                                        {candidate.candidate.education.map((edu: any, index: number) => (
                                                                                            <div key={index} className="space-y-1">
                                                                                                <h4 className="font-medium text-sm">{edu.institution}</h4>
                                                                                                <p className="text-xs text-gray-600">
                                                                                                    {edu.level || 'Unknown'} - {edu.faculty}
                                                                                                </p>
                                                                                                <p className="text-xs text-gray-600">
                                                                                                    {edu.major || 'General'} • {edu.start_year} - {edu.end_year || 'Present'} • GPA: {edu.gpa}
                                                                                                </p>
                                                                                                {index < candidate.candidate.education.length - 1 && (
                                                                                                    <Separator className="my-2" />
                                                                                                )}
                                                                                            </div>
                                                                                        ))}
                                                                                    </div>
                                                                                ) : (
                                                                                    <p className="text-sm text-gray-500">Tidak ada data pendidikan</p>
                                                                                )}
                                                                            </CardContent>
                                                                        </Card>

                                                                        {/* Work Experience */}
                                                                        <Card>
                                                                            <CardHeader>
                                                                                <CardTitle className="text-base">Pengalaman Kerja</CardTitle>
                                                                            </CardHeader>
                                                                            <CardContent>
                                                                                {candidate.candidate?.work_experiences && candidate.candidate.work_experiences.length > 0 ? (
                                                                                    <div className="space-y-4">
                                                                                        {candidate.candidate.work_experiences.map((exp: any, index: number) => (
                                                                                            <div key={index} className="space-y-1">
                                                                                                <h4 className="font-medium text-sm">{exp.position}</h4>
                                                                                                <p className="text-xs text-gray-600">{exp.company}</p>
                                                                                                <p className="text-xs text-gray-600">
                                                                                                    {exp.start_date ? formatDateOnly(exp.start_date) : '-'} - {exp.end_date ? formatDateOnly(exp.end_date) : 'Present'}
                                                                                                </p>
                                                                                                {exp.description && (
                                                                                                    <p className="text-xs mt-1">{exp.description}</p>
                                                                                                )}
                                                                                                {index < candidate.candidate.work_experiences.length - 1 && (
                                                                                                    <Separator className="my-2" />
                                                                                                )}
                                                                                            </div>
                                                                                        ))}
                                                                                    </div>
                                                                                ) : (
                                                                                    <p className="text-sm text-gray-500">Tidak ada pengalaman kerja</p>
                                                                                )}
                                                                            </CardContent>
                                                                        </Card>

                                                                        {/* Skills & Languages */}
                                                                        <Card>
                                                                            <CardHeader>
                                                                                <CardTitle className="text-base">Skills & Bahasa</CardTitle>
                                                                            </CardHeader>
                                                                            <CardContent>
                                                                                <div className="grid grid-cols-2 gap-4">
                                                                                    <div>
                                                                                        <h4 className="text-sm font-medium mb-2">Skills</h4>
                                                                                        {candidate.candidate?.skills && Array.isArray(candidate.candidate.skills) && candidate.candidate.skills.length > 0 ? (
                                                                                            <div className="space-y-1">
                                                                                                {candidate.candidate.skills.map((skill: string, index: number) => (
                                                                                                    <div key={index} className="text-xs">{skill}</div>
                                                                                                ))}
                                                                                            </div>
                                                                                        ) : (
                                                                                            <p className="text-xs text-gray-500">Tidak ada skills</p>
                                                                                        )}
                                                                                    </div>
                                                                                    <div>
                                                                                        <h4 className="text-sm font-medium mb-2">Bahasa</h4>
                                                                                        {candidate.candidate?.languages && Array.isArray(candidate.candidate.languages) && candidate.candidate.languages.length > 0 ? (
                                                                                            <div className="space-y-1">
                                                                                                {candidate.candidate.languages.map((lang: any, index: number) => (
                                                                                                    <div key={index} className="text-xs">
                                                                                                        {lang.name || lang} {lang.level ? `(${lang.level})` : ''}
                                                                                                    </div>
                                                                                                ))}
                                                                                            </div>
                                                                                        ) : (
                                                                                            <p className="text-xs text-gray-500">Tidak ada bahasa</p>
                                                                                        )}
                                                                                    </div>
                                                                                </div>
                                                                            </CardContent>
                                                                        </Card>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        )}
                                                    </React.Fragment>
                                                );
                                            })
                                        ) : (
                                            <tr>
                                                <td colSpan={10} className="p-4 text-center text-muted-foreground">
                                                    No candidates found in administration stage
                                                </td>
                                            </tr>
                                        )}
                                    </tbody>
                                </table>
                            </div>
                            {candidates.last_page > 1 && (
                                <div className="mt-4 flex justify-center">
                                    <Pagination
                                        currentPage={candidates.current_page}
                                        totalPages={candidates.last_page}
                                        onPageChange={handlePageChange}
                                    />
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>

            {/* Stage Action Dialog */}
            {actionDialog && (
                <StageActionDialog
                    isOpen={actionDialog.isOpen}
                    onClose={() => setActionDialog(null)}
                    applicationId={actionDialog.candidateId}
                    stage="administration"
                    action={actionDialog.action}
                    title={actionDialog.action === 'accept' ? 'Lolos ke Stage Selanjutnya' : 'Tolak Kandidat'}
                    description={
                        actionDialog.action === 'accept'
                            ? 'Berikan nilai (10-99) dan catatan untuk kandidat ini. Kandidat akan melanjutkan ke tahap assessment.'
                            : 'Berikan alasan penolakan. Kandidat akan ditolak dari proses rekrutmen.'
                    }
                />
            )}
        </AppLayout>
    );
} 