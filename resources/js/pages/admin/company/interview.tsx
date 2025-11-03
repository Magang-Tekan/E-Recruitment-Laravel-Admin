import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type ApplicationInfo } from '@/types';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Eye, CheckCircle, Clock, XCircle } from 'lucide-react';
import { format, formatDistanceStrict } from 'date-fns';
import { Pagination } from '@/components/ui/pagination';

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
        title: 'Interview',
        href: '/dashboard/recruitment/interview',
    },
];

export default function Interview({ candidates, filters, companyInfo, periodInfo }: Props) {
    const handlePageChange = (page: number) => {
        router.visit('/dashboard/recruitment/interview', {
            data: { ...(filters || {}), page },
            preserveState: true,
            preserveScroll: true,
        });
    };

    const getReviewStatus = (candidate: ApplicationInfo) => {
        // Find specific interview history entry
        const interviewHistory = candidate.history?.find(h => 
            h.stage === 'interview' || h.status_code === 'interview'
        );
        
        // Check if interview is completed (has score OR completed_at)
        const hasScore = (
            (interviewHistory?.score !== null && interviewHistory?.score !== undefined) ||
            (candidate.stages?.interview?.score !== null && candidate.stages?.interview?.score !== undefined)
        );
        const isCompleted = interviewHistory?.completed_at || candidate.stages?.interview?.completed_at;
        const hasReviewer = interviewHistory?.reviewer_name !== null && interviewHistory?.reviewer_name !== undefined;
        
        // Logic:
        // 1. If has score OR completed -> Reviewed
        // 2. If scheduled but not completed -> Pending Review
        // 3. If no schedule -> Not Started
        
        if (hasScore || (isCompleted && hasReviewer)) {
            return { status: 'reviewed', icon: CheckCircle, color: 'bg-green-100 text-green-800 border-green-200' };
        } else if (interviewHistory?.processed_at || candidate.stages?.interview?.scheduled_at) {
            return { status: 'pending', icon: Clock, color: 'bg-yellow-100 text-yellow-800 border-yellow-200' };
        } else {
            return { status: 'not_started', icon: XCircle, color: 'bg-gray-100 text-gray-800 border-gray-200' };
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Interview" />
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
                        className="flex-1 px-4 py-2 text-sm font-medium text-gray-500 hover:text-gray-700"
                        onClick={() => router.visit('/dashboard/recruitment/assessment', {
                            data: { company: filters?.company, period: filters?.period }
                        })}
                    >
                        Assessment
                    </button>
                    <button
                        className="flex-1 border-b-2 border-primary px-4 py-2 text-sm font-medium text-primary"
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
                        <h2 className="text-2xl font-semibold">Interview Stage</h2>
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
                            <CardDescription>View and manage candidates in interview stage</CardDescription>
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
                                            <th className="p-4 font-medium">Scheduled At</th>
                                            <th className="p-4 font-medium">Completed At</th>
                                            <th className="p-4 font-medium">Score</th>
                                            <th className="p-4 font-medium">Review Status</th>
                                            <th className="p-4 font-medium">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {candidates.data.length > 0 ? (
                                            candidates.data.map((candidate, index) => {
                                                const scheduledAt = candidate.stages?.interview?.scheduled_at;
                                                const completedAt = candidate.stages?.interview?.completed_at;
                                                const reviewStatus = getReviewStatus(candidate);
                                                const StatusIcon = reviewStatus.icon;
                                                
                                                return (
                                                    <tr key={candidate.id} className="border-b hover:bg-muted/50">
                                                        <td className="p-4">{(candidates.current_page - 1) * candidates.per_page + index + 1}</td>
                                                        <td className="p-4 font-medium">{candidate.user.name}</td>
                                                        <td className="p-4">{candidate.user.email}</td>
                                                        <td className="p-4">{candidate.vacancy_period.vacancy.title}</td>
                                                        <td className="p-4">
                                                            {scheduledAt ? format(new Date(scheduledAt), 'dd MMM yyyy HH:mm') : '-'}
                                                        </td>
                                                        <td className="p-4">
                                                            {completedAt ? format(new Date(completedAt), 'dd MMM yyyy HH:mm') : '-'}
                                                        </td>
                                                        <td className="p-4 font-medium">
                                                            {candidate.stages?.interview?.score ? (
                                                                <span className="text-green-700">
                                                                    {Number(candidate.stages.interview.score).toFixed(2)}
                                                                </span>
                                                            ) : '-'}
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
                                                                    router.get(`/dashboard/recruitment/interview/${candidate.id}`);
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
                                                <td colSpan={9} className="p-4 text-center text-muted-foreground">
                                                    No candidates found in interview stage
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