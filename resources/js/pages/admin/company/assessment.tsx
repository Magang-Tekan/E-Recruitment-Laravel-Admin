import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ApplicationInfo } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Eye, CheckCircle, Clock, XCircle } from 'lucide-react';
import { format, formatDistanceStrict } from 'date-fns';
import { Pagination } from '@/components/ui/pagination';
import { RecruitmentHistoryTimeline } from '@/components/recruitment-history-timeline';

interface Props {
    candidates: {
        data: ApplicationInfo[];
        current_page: number;
        per_page: number;
        last_page: number;
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
        title: 'Assessment',
        href: '/dashboard/recruitment/assessment',
    },
];

export default function Assessment({ candidates, filters, companyInfo, periodInfo }: Props) {
    const handlePageChange = (page: number) => {
        const params = new URLSearchParams();
        if (filters?.company) params.append('company', filters.company);
        if (filters?.vacancy) params.append('vacancy', filters.vacancy);
        params.append('page', page.toString());
        router.visit(`/dashboard/recruitment/assessment?${params.toString()}`, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getReviewStatus = (candidate: ApplicationInfo) => {
        // Find specific assessment/psychological test history entry
        // Get the most recent one (when test is submitted, status might be 'assessment', 'psychotest', etc.)
        const allAssessmentHistories = candidate.history?.filter(h => 
            h.stage === 'psychological_test' || 
            h.status_code === 'psychotest' ||
            h.status_code === 'psychological_test' ||
            h.status_code === 'assessment'
        ) || [];
        
        // Sort by processed_at descending to get the most recent one
        const sortedHistories = [...allAssessmentHistories].sort((a, b) => {
            const dateA = a.processed_at ? new Date(a.processed_at).getTime() : 0;
            const dateB = b.processed_at ? new Date(b.processed_at).getTime() : 0;
            return dateB - dateA; // Descending order (newest first)
        });
        
        const assessmentHistory = sortedHistories.length > 0 ? sortedHistories[0] : null;
        
        // Check if admin has given score (this is the indicator that admin has reviewed)
        // Score must exist and be a valid number (not null, not undefined, not empty)
        const hasScore = assessmentHistory?.score !== null && 
            assessmentHistory?.score !== undefined &&
            assessmentHistory?.score !== '' &&
            !isNaN(Number(assessmentHistory.score));
        
        // Check if test has been started
        const testStarted = assessmentHistory?.processed_at !== null && 
            assessmentHistory?.processed_at !== undefined;
        
        // PRIORITY 9: Fix "Not Started" Status - check if candidate is in assessment stage
        // Check if candidate has been assigned to assessment stage (has assessment history or status)
        const isInAssessmentStage = candidate.history?.some(h => 
            h.stage === 'psychological_test' || 
            h.status_code === 'psychotest' ||
            h.status_code === 'psychological_test'
        ) || candidate.status_code === 'psychotest';
        
        // Logic:
        // 1. If has score -> Reviewed (admin has given score/nilai, meaning reviewed)
        // 2. If test started but no score -> Pending Review (waiting for admin to give score)
        // 3. If in assessment stage but not started -> Not Started
        // 4. If not in assessment stage -> Not Started (shouldn't appear in list, but handle gracefully)
        
        if (hasScore) {
            return { status: 'reviewed', icon: CheckCircle, color: 'bg-green-100 text-green-800 border-green-200' };
        } else if (testStarted) {
            return { status: 'pending', icon: Clock, color: 'bg-yellow-100 text-yellow-800 border-yellow-200' };
        } else if (isInAssessmentStage) {
            return { status: 'not_started', icon: XCircle, color: 'bg-gray-100 text-gray-800 border-gray-200' };
        } else {
            // Not in assessment stage yet - should not appear in list, but handle gracefully
            return { status: 'not_started', icon: XCircle, color: 'bg-gray-100 text-gray-800 border-gray-200' };
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Assessment" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Navigation Menu */}
                <div className="flex w-full border-b">
                    <button
                        className="flex-1 px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                        onClick={() => router.visit('/dashboard/recruitment/administration', {
                            data: { company: filters?.company, vacancy: filters?.vacancy }
                        })}
                    >
                        Administration
                    </button>
                    <button
                        className="flex-1 border-b-2 border-primary px-4 py-2 text-sm font-medium text-primary"
                        onClick={() => {
                            const params = new URLSearchParams();
                            if (filters?.company) params.append('company', filters.company);
                            if (filters?.vacancy) params.append('vacancy', filters.vacancy);
                            router.visit(`/dashboard/recruitment/assessment?${params.toString()}`);
                        }}
                    >
                        Assessment
                    </button>
                    <button
                        className="flex-1 px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                        onClick={() => {
                            const params = new URLSearchParams();
                            if (filters?.company) params.append('company', filters.company);
                            if (filters?.vacancy) params.append('vacancy', filters.vacancy);
                            router.visit(`/dashboard/recruitment/interview?${params.toString()}`);
                        }}
                    >
                        Interview
                    </button>
                    <button
                        className="flex-1 px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                        onClick={() => router.visit('/dashboard/recruitment/reports', {
                            data: { company: filters?.company, vacancy: filters?.vacancy }
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
                        <h2 className="text-2xl font-semibold">Assessment Stage</h2>
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
                            <CardDescription>View and manage candidates in assessment stage</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="relative overflow-x-auto">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-muted text-muted-foreground">
                                        <tr>
                                            <th className="p-4 font-medium">No</th>
                                            <th className="p-4 font-medium">Name</th>
                                            <th className="p-4 font-medium">Email</th>
                                            <th className="p-4 font-medium">Position</th>
                                            <th className="p-4 font-medium">Started At</th>
                                            <th className="p-4 font-medium">Completed At</th>
                                            <th className="p-4 font-medium">Duration</th>
                                            <th className="p-4 font-medium">Score</th>
                                            <th className="p-4 font-medium">Review Status</th>
                                            <th className="p-4 font-medium">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {candidates.data.length > 0 ? (
                                            candidates.data.map((candidate, index) => {
                                                // Find assessment-specific history entry for dates
                                                const assessmentHistory = candidate.history?.find(h => 
                                                    h.stage === 'psychological_test' || 
                                                    h.status_code === 'psychotest' ||
                                                    h.status_code === 'psychological_test'
                                                );
                                                
                                                
                                                const startedAt = assessmentHistory?.processed_at;
                                                const completedAt = assessmentHistory?.completed_at;
                                                const duration = startedAt && completedAt ? 
                                                    formatDistanceStrict(new Date(completedAt), new Date(startedAt)) : 
                                                    '-';
                                                const reviewStatus = getReviewStatus(candidate);
                                                const StatusIcon = reviewStatus.icon;
                                                
                                                return (
                                                    <tr key={candidate.id} className="border-b hover:bg-muted/50">
                                                        <td className="p-4">{(candidates.current_page - 1) * candidates.per_page + index + 1}</td>
                                                        <td className="p-4 font-medium">{candidate.user.name}</td>
                                                        <td className="p-4">{candidate.user.email}</td>
                                                        <td className="p-4">{candidate.vacancy_period.vacancy.title}</td>
                                                        <td className="p-4">
                                                            {startedAt ? format(new Date(startedAt), 'dd MMM yyyy HH:mm') : '-'}
                                                        </td>
                                                        <td className="p-4">
                                                            {completedAt ? format(new Date(completedAt), 'dd MMM yyyy HH:mm') : '-'}
                                                        </td>
                                                        <td className="p-4">{duration}</td>
                                                        <td className="p-4 font-medium">
                                                            {(() => {
                                                                // PRIORITY 5: Fix Score Display - simplify logic, use single source of truth (history score)
                                                                // Find assessment history entry
                                                                const assessmentHistory = candidate.history?.find(h => 
                                                                    h.stage === 'psychological_test' || 
                                                                    h.status_code === 'psychotest' ||
                                                                    h.status_code === 'psychological_test'
                                                                );
                                                                
                                                                // Always use history score if available (single source of truth)
                                                                const historyScore = assessmentHistory?.score;
                                                                if (historyScore !== null && historyScore !== undefined && historyScore !== '' && !isNaN(Number(historyScore))) {
                                                                    return (
                                                                        <span className="text-blue-700">
                                                                            {Number(historyScore).toFixed(2)}
                                                                        </span>
                                                                    );
                                                                }
                                                                
                                                                // If no history score, check if test has been started
                                                                const testStarted = assessmentHistory?.processed_at !== null && 
                                                                    assessmentHistory?.processed_at !== undefined;
                                                                
                                                                if (testStarted) {
                                                                    // Test started but no score yet - show pending
                                                                    return (
                                                                        <span className="text-amber-600">
                                                                            Pending
                                                                        </span>
                                                                    );
                                                                }
                                                                
                                                                // No test started
                                                                return <span className="text-gray-400">-</span>;
                                                            })()}
                                                        </td>
                                                        <td className="p-4">
                                                            <Badge variant="outline" className={reviewStatus.color}>
                                                                <StatusIcon className="mr-1 h-3 w-3" />
                                                                {reviewStatus.status === 'reviewed' ? 'Reviewed' : 
                                                                 reviewStatus.status === 'pending' ? 'Pending Review' : 'Not Started'}
                                                            </Badge>
                                                        </td>
                                                        <td className="p-4">
                                                            <Button
                                                                variant="outline"
                                                                size="icon"
                                                                onClick={() => {
                                                                    router.get(`/dashboard/recruitment/assessment/${candidate.id}`);
                                                                }}
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </Button>
                                                        </td>
                                                    </tr>
                                                );
                                            })
                                        ) : (
                                            <tr>
                                                <td colSpan={10} className="p-4 text-center text-muted-foreground">
                                                    No candidates found in assessment stage
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
        </AppLayout>
    );
} 