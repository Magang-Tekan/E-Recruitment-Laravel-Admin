import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { PageProps as InertiaPageProps } from '@inertiajs/core';
import { router, usePage } from '@inertiajs/react';
import { CheckCircle, Clock, XCircle, AlertCircle } from 'lucide-react';
import { useEffect } from 'react';

interface Application {
    id: number;
    vacancy: {
        title: string;
        company: string;
        psychotest_name?: string;
    };
    status: string;
    applied_at: string;
}

interface TestStatus {
    is_completed: boolean;
    completed_at?: string;
    score?: number;
    can_retake: boolean;
}

interface History {
    status: string;
    date: string;
    score?: number;
    notes?: string;
}

type PageProps = InertiaPageProps & {
    application: Application;
    testStatus: TestStatus;
    history: History[];
};

export default function ApplicationStatus() {
    const { application, testStatus, history } = usePage<PageProps>().props;

    // Prevent back navigation after test completion
    useEffect(() => {
        if (testStatus.is_completed) {
            const handleBeforeUnload = (e: BeforeUnloadEvent) => {
                e.preventDefault();
                e.returnValue = 'You have completed the test and cannot go back.';
                return e.returnValue;
            };

            const handlePopState = (e: PopStateEvent) => {
                e.preventDefault();
                window.history.pushState(null, '', window.location.href);
                alert('You cannot go back to the test after completion.');
            };

            window.addEventListener('beforeunload', handleBeforeUnload);
            window.addEventListener('popstate', handlePopState);

            // Push current state to prevent back navigation
            window.history.pushState(null, '', window.location.href);

            return () => {
                window.removeEventListener('beforeunload', handleBeforeUnload);
                window.removeEventListener('popstate', handlePopState);
            };
        }
    }, [testStatus.is_completed]);

    const handleBackToDashboard = () => {
        router.visit('/candidate');
    };

    const getStatusIcon = (status: string) => {
        switch (status.toLowerCase()) {
            case 'completed':
            case 'passed':
            case 'accepted':
                return <CheckCircle className="h-5 w-5 text-green-500" />;
            case 'rejected':
            case 'failed':
                return <XCircle className="h-5 w-5 text-red-500" />;
            case 'pending':
            case 'in_progress':
            case 'psychotest':
            case 'assessment':
            case 'interview':
                return <Clock className="h-5 w-5 text-yellow-500" />;
            default:
                return <AlertCircle className="h-5 w-5 text-gray-500" />;
        }
    };

    const getStatusColor = (status: string) => {
        switch (status.toLowerCase()) {
            case 'completed':
            case 'passed':
            case 'accepted':
                return 'bg-green-100 text-green-800';
            case 'rejected':
            case 'failed':
                return 'bg-red-100 text-red-800';
            case 'pending':
            case 'in_progress':
            case 'psychotest':
            case 'assessment':
            case 'interview':
                return 'bg-yellow-100 text-yellow-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <div className="min-h-screen bg-slate-50">
            {/* Header */}
            <header className="bg-white border-b border-slate-200">
                <div className="max-w-4xl mx-auto px-4 py-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900">Application Status</h1>
                            <p className="text-slate-600 mt-1">{application.vacancy.title} at {application.vacancy.company}</p>
                        </div>
                        <Button onClick={handleBackToDashboard} variant="outline">
                            Back to Dashboard
                        </Button>
                    </div>
                </div>
            </header>

            {/* Content */}
            <main className="max-w-4xl mx-auto px-4 py-8">
                <div className="space-y-6">
                    {/* Current Status */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                {getStatusIcon(application.status)}
                                Current Status
                            </CardTitle>
                            <CardDescription>
                                Your current application status and progress
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <Badge className={getStatusColor(application.status)}>
                                        {application.status}
                                    </Badge>
                                    <span className="text-slate-600">
                                        Applied on {formatDate(application.applied_at)}
                                    </span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Test Status */}
                    {testStatus && (
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    {testStatus.is_completed ? (
                                        <CheckCircle className="h-5 w-5 text-green-500" />
                                    ) : (
                                        <Clock className="h-5 w-5 text-yellow-500" />
                                    )}
                                    {application.vacancy.psychotest_name || 'Psychotest'} Status
                                </CardTitle>
                                <CardDescription>
                                    Your {(application.vacancy.psychotest_name || 'psychological test').toLowerCase()} completion status
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div className="flex items-center justify-between">
                                        <span className="font-medium">Status:</span>
                                        <Badge className={testStatus.is_completed ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}>
                                            {testStatus.is_completed ? 'Completed' : 'Not Completed'}
                                        </Badge>
                                    </div>
                                    
                                    {testStatus.is_completed && testStatus.completed_at && (
                                        <div className="flex items-center justify-between">
                                            <span className="font-medium">Completed At:</span>
                                            <span className="text-slate-600">
                                                {formatDate(testStatus.completed_at)}
                                            </span>
                                        </div>
                                    )}
                                    
                                    {testStatus.is_completed && testStatus.score !== undefined && (
                                        <div className="flex items-center justify-between">
                                            <span className="font-medium">Score:</span>
                                            <span className="text-slate-600 font-mono">
                                                {testStatus.score}
                                            </span>
                                        </div>
                                    )}

                                    {testStatus.is_completed && (
                                        <div className="p-4 bg-green-50 border border-green-200 rounded-lg">
                                            <div className="flex items-center gap-2">
                                                <CheckCircle className="h-5 w-5 text-green-500" />
                                                <p className="text-green-800 font-medium">
                                                    Test Completed Successfully
                                                </p>
                                            </div>
                                            <p className="text-green-700 text-sm mt-1">
                                                You have successfully completed the psychological test. 
                                                For security reasons, you cannot retake this test.
                                            </p>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Application History */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Application History</CardTitle>
                            <CardDescription>
                                Timeline of your application progress
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {history.map((item, index) => (
                                    <div key={index} className="flex items-start gap-4 p-4 border border-slate-200 rounded-lg">
                                        <div className="flex-shrink-0">
                                            {getStatusIcon(item.status)}
                                        </div>
                                        <div className="flex-grow">
                                            <div className="flex items-center justify-between">
                                                <h4 className="font-medium text-slate-900">{item.status}</h4>
                                                <span className="text-sm text-slate-500">
                                                    {formatDate(item.date)}
                                                </span>
                                            </div>
                                            {item.score !== undefined && (
                                                <p className="text-sm text-slate-600 mt-1">
                                                    Score: {item.score}
                                                </p>
                                            )}
                                            {item.notes && (
                                                <p className="text-sm text-slate-600 mt-1">
                                                    {item.notes}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                                {history.length === 0 && (
                                    <div className="text-center py-8 text-slate-500">
                                        No history available yet.
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </main>
        </div>
    );
}