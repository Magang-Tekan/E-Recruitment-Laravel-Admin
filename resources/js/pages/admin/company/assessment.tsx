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
        period?: string;
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
        title: 'Assessment',
        href: '/dashboard/recruitment/assessment',
    },
];

export default function Assessment({ candidates, filters, companyInfo, periodInfo }: Props) {
    const handlePageChange = (page: number) => {
        router.visit('/dashboard/recruitment/assessment', {
            data: { ...(filters || {}), page },
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getReviewStatus = (candidate: ApplicationInfo) => {
        // Find specific assessment/psychological test history entry
        const assessmentHistory = candidate.history?.find(h => 
            h.stage === 'psychological_test' || 
            h.status_code === 'psychotest' ||
            h.status_code === 'psychological_test'
        );
        
        // Debug untuk melihat data user baru
        if (candidate.user.name !== 'userbiasa') {
            console.log(`🔍 Review Status Debug for ${candidate.user.name}:`, {
                assessmentHistory,
                completed_at: assessmentHistory?.completed_at,
                score: assessmentHistory?.score,
                reviewer_name: assessmentHistory?.reviewer_name,
                processed_at: assessmentHistory?.processed_at
            });
        }
        
        // Check if assessment test is completed (candidate has taken the test)
        const testCompleted = assessmentHistory?.completed_at !== null && assessmentHistory?.completed_at !== undefined;
        
        // Check if manual review has been done (has score AND reviewer)
        const hasManualReview = (
            assessmentHistory?.score !== null && 
            assessmentHistory?.score !== undefined &&
            assessmentHistory?.reviewer_name !== null && 
            assessmentHistory?.reviewer_name !== undefined
        );
        
        // Logic:
        // 1. If manual review is done -> Reviewed
        // 2. If test completed but no manual review -> Pending Review  
        // 3. If test started but not completed -> In Progress (show as pending)
        // 4. If no test history -> Not Started
        
        if (hasManualReview) {
            return { status: 'reviewed', icon: CheckCircle, color: 'bg-green-100 text-green-800 border-green-200' };
        } else if (testCompleted) {
            return { status: 'pending', icon: Clock, color: 'bg-yellow-100 text-yellow-800 border-yellow-200' };
        } else if (assessmentHistory?.processed_at) {
            return { status: 'pending', icon: Clock, color: 'bg-yellow-100 text-yellow-800 border-yellow-200' };
        } else {
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
                            data: { company: filters?.company, period: filters?.period }
                        })}
                    >
                        Administration
                    </button>
                    <button
                        className="flex-1 border-b-2 border-primary px-4 py-2 text-sm font-medium text-primary"
                        onClick={() => router.visit('/dashboard/recruitment/assessment', {
                            data: { company: filters?.company, period: filters?.period }
                        })}
                    >
                        Assessment
                    </button>
                    <button
                        className="flex-1 px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                        onClick={() => router.visit('/dashboard/recruitment/interview', {
                            data: { company: filters?.company, period: filters?.period }
                        })}
                    >
                        Interview
                    </button>
                    <button
                        className="flex-1 px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                        onClick={() => router.visit('/dashboard/recruitment/reports', {
                            data: { company: filters?.company, period: filters?.period }
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
                                                
                                                // Temporary debug for userbiasa
                                                if (candidate.user.name === 'userbiasa') {
                                                    console.log('🔍 Assessment History for userbiasa:', {
                                                        assessmentHistory,
                                                        processed_at: assessmentHistory?.processed_at,
                                                        completed_at: assessmentHistory?.completed_at
                                                    });
                                                }
                                                
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
                                                                // Check if this is a psychological test using question pack data
                                                                const isPsychologicalTest = (() => {
                                                                    const questionPack = candidate?.vacancy_period?.vacancy?.question_pack;
                                                                    
                                                                    if (questionPack && questionPack.test_type) {
                                                                        const psychologicalTestTypes = ['psychological', 'psychology', 'psikologi'];
                                                                        return psychologicalTestTypes.includes(String(questionPack.test_type).toLowerCase());
                                                                    }
                                                                    
                                                                    // Fallback: check vacancy title for psychology keywords
                                                                    const vacancyTitle = candidate?.vacancy_period?.vacancy?.title || '';
                                                                    const psychologicalKeywords = ['psikologi', 'psychology', 'psychological'];
                                                                    
                                                                    return psychologicalKeywords.some(keyword => 
                                                                        vacancyTitle.toLowerCase().includes(keyword)
                                                                    );
                                                                })();

                                                                // For psychological tests, only show manual score (from history after manual scoring)
                                                                if (isPsychologicalTest) {
                                                                    // Find assessment history entry specifically
                                                                    const assessmentHistory = candidate.history?.find(h => 
                                                                        h.stage === 'psychological_test' || 
                                                                        h.status_code === 'psychotest' ||
                                                                        h.status_code === 'psychological_test'
                                                                    );
                                                                    const manualScore = assessmentHistory?.score;
                                                                    
                                                                    if (manualScore !== null && manualScore !== undefined) {
                                                                        return (
                                                                            <span className="text-blue-700">
                                                                                {Number(manualScore).toFixed(2)}
                                                                            </span>
                                                                        );
                                                                    }
                                                                    // No score shown until manual scoring is done
                                                                    return <span className="text-gray-400">-</span>;
                                                                }

                                                                // For non-psychological tests (technical/general), show calculated scores
                                                                // Check for manual score from history first
                                                                const manualScore = candidate.history?.[0]?.score;
                                                                if (manualScore !== null && manualScore !== undefined) {
                                                                    return (
                                                                        <span className="text-blue-700">
                                                                            {Number(manualScore).toFixed(2)}
                                                                        </span>
                                                                    );
                                                                }
                                                                // Check for calculated assessment score
                                                                if (candidate.assessment?.total_score !== null && candidate.assessment?.total_score !== undefined) {
                                                                    return (
                                                                        <span className="text-green-700">
                                                                            {Number(candidate.assessment.total_score).toFixed(2)}
                                                                        </span>
                                                                    );
                                                                }
                                                                // Fallback to overall score from report
                                                                if (candidate.report?.overall_score) {
                                                                    return (
                                                                        <span className="text-orange-700">
                                                                            {Number(candidate.report.overall_score).toFixed(2)}
                                                                        </span>
                                                                    );
                                                                }
                                                                return '-';
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