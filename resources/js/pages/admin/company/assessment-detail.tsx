import { useState, useEffect } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ArrowLeft, Download, FileText, BookOpen, CheckCircle2, XCircle } from 'lucide-react';
import { format } from 'date-fns';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import StageActionDialog from '@/components/stage-action-dialog';

interface Props {
    candidate: any;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Assessment', href: '/dashboard/recruitment/assessment' },
];

export default function AssessmentDetail({ candidate }: Props) {
    const [manualScoringDialog, setManualScoringDialog] = useState(false);
    const [manualScore, setManualScore] = useState<string>('');
    const [manualStatus, setManualStatus] = useState<'passed' | 'rejected'>('passed');
    const [manualNotes, setManualNotes] = useState('');
    const [zoomLink, setZoomLink] = useState('');
    const [scheduledAt, setScheduledAt] = useState('');
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [isExporting, setIsExporting] = useState(false);
    const [actionDialog, setActionDialog] = useState<{
        isOpen: boolean;
        action: 'accept' | 'reject';
    } | null>(null);
    // Initialize essay scores from candidate data
    const initialEssayScores = candidate?.stages?.psychological_test?.answers
        ?.filter((answer: any) => answer.question.type === 'essay' && answer.score !== null && answer.score !== undefined)
        ?.reduce((acc: Record<number, number>, answer: any) => {
            if (typeof answer.score === 'number') {
                acc[answer.id] = answer.score;
            }
            return acc;
        }, {}) || {};
    
    const [essayScores, setEssayScores] = useState<Record<number, string>>({});
    const [savedEssayScores, setSavedEssayScores] = useState<Record<number, number>>(initialEssayScores);
    const [isSavingScore, setIsSavingScore] = useState<Record<number, boolean>>({});

    // Load essay scores from candidate data when component mounts or data changes
    useEffect(() => {
        const answers = candidate?.stages?.psychological_test?.answers || [];
        const essayAnswers = answers.filter((answer: any) => answer.question.type === 'essay');
        
        const loadedScores: Record<number, number> = {};
        const loadedInputScores: Record<number, string> = {};
        
        essayAnswers.forEach((answer: any) => {
            // Check if score exists (could be number, string, or null)
            const scoreValue = answer.score;
            if (scoreValue !== null && scoreValue !== undefined && scoreValue !== '') {
                const numScore = typeof scoreValue === 'number' ? scoreValue : parseFloat(String(scoreValue));
                if (!isNaN(numScore) && numScore >= 0 && numScore <= 100) {
                    loadedScores[answer.id] = numScore;
                    loadedInputScores[answer.id] = numScore.toString();
                }
            }
        });
        
        // Always update saved scores from loaded data (prioritize loaded data over existing)
        if (Object.keys(loadedScores).length > 0) {
            setSavedEssayScores(loadedScores);
            setEssayScores(loadedInputScores);
        } else {
            // Clear scores if no data found
            setSavedEssayScores({});
            setEssayScores({});
        }
    }, [candidate?.stages?.psychological_test?.answers]);

    const isPsychologicalTest = (): boolean => {
        // Check both question pack test_type AND vacancy psychotest_name
        const questionPack = candidate?.vacancy?.question_pack ?? candidate?.user?.question_pack ?? null;
        const vacancy = candidate?.vacancy ?? candidate?.vacancy_period?.vacancy ?? null;
        
        // Debug log to see what we're checking
        console.log('🔍 isPsychologicalTest Debug:', {
            questionPack_test_type: questionPack?.test_type,
            vacancy_psychotest_name: vacancy?.psychotest_name,
            candidate_test_type: getTestType(),
            vacancy_title: vacancy?.title
        });
        
        // Check question pack test type - ONLY psychology/psychological, NOT general or technical
        let isPackPsychological = false;
        if (questionPack && questionPack.test_type) {
            const psychologicalTestTypes = ['psychological', 'psychology', 'psikologi']; // Remove 'general'
            isPackPsychological = psychologicalTestTypes.includes(String(questionPack.test_type).toLowerCase());
        }
        
        // Check vacancy psychotest_name - ONLY explicit psychology terms
        let isVacancyPsychological = false;
        if (vacancy && vacancy.psychotest_name) {
            const psychologicalNames = ['psikologi', 'psychology', 'psychological', 'tes psikologi', 'test psikologi'];
            isVacancyPsychological = psychologicalNames.some(name => 
                String(vacancy.psychotest_name).toLowerCase().includes(name)
            );
        }
        
        const result = isPackPsychological || isVacancyPsychological;
        console.log('🎯 isPsychologicalTest Result:', result);
        
        // Return true ONLY if EITHER question pack OR vacancy clearly indicate psychological test
        return result;
    };

    const getTestType = (): string => {
        const questionPack = candidate?.vacancy?.question_pack ?? candidate?.user?.question_pack ?? null;
        const vacancy = candidate?.vacancy ?? candidate?.vacancy_period?.vacancy ?? null;
        
        if (!questionPack) return 'No test assigned';
        
        const testType = String(questionPack.test_type || '').toLowerCase();
        const packName = questionPack.pack_name || 'Assessment';
        const psychotestName = vacancy?.psychotest_name || '';
        
        // If psychotest_name is set, use it as the primary display
        if (psychotestName) {
            return `${psychotestName} - ${packName}`;
        }
        
        // Fallback to test_type mapping
        switch (testType) {
            case 'psychological':
            case 'psychology':
            case 'psikologi':
                return `Psychological Test - ${packName}`;
            case 'technical':
                return `Technical Test - ${packName}`;
            case 'general':
                return `General Assessment - ${packName}`;
            case 'leadership':
                return `Leadership Assessment - ${packName}`;
            default:
                return `${packName} (${testType || 'Unknown'})`;
        }
    };

    const getTestDescription = (): string => {
        const questionPack = candidate?.vacancy?.question_pack ?? candidate?.user?.question_pack ?? null;
        if (!questionPack) return 'No test description available';
        
        const testType = String(questionPack.test_type).toLowerCase();
        
        switch (testType) {
            case 'psychological':
            case 'psychology':
            case 'psikologi':
                return 'Psychological evaluation to assess personality traits, cognitive abilities, and behavioral patterns relevant to the position.';
            case 'technical':
                return 'Technical assessment to evaluate job-specific skills and knowledge required for the position.';
            case 'general':
                return 'General aptitude test covering logical reasoning, numerical ability, and verbal comprehension.';
            case 'leadership':
                return 'Leadership assessment to evaluate management potential, decision-making skills, and team leadership capabilities.';
            default:
                return questionPack.description || 'Assessment to evaluate candidate suitability for the position.';
        }
    };

    const handleExportPsychologicalTest = async (format: 'pdf' | 'excel') => {
        console.log('=== EXPORT STARTING ===');
        console.log('Format:', format);
        console.log('Candidate ID:', candidate.id);
        console.log('isExporting:', isExporting);
        
        if (isExporting) {
            console.log('Already exporting, skipping');
            return;
        }
        
        setIsExporting(true);
        
        const url = `/dashboard/recruitment/assessment/${candidate.id}/export-psychological-test?format=${format}`;
        console.log('Export URL:', url);
        
        try {
            // Simple test: just try window.open first
            console.log('Attempting window.open...');
            const newWindow = window.open(url, '_blank');
            
            if (newWindow) {
                console.log('Window opened successfully');
                // Don't do anything else for now, just see if this works
            } else {
                console.log('Window.open failed, probably blocked');
                alert('Popup diblokir! Silakan allow popup untuk website ini dan coba lagi.');
            }
            
        } catch (error) {
            console.error('Export error:', error);
            alert(`Error: ${error instanceof Error ? error.message : 'Unknown error'}`);
        } finally {
            console.log('Resetting isExporting');
            setIsExporting(false);
        }
    };

    const downloadFile = async (url: string, format: 'pdf' | 'excel') => {
        console.log('downloadFile called with:', { url, format });
        
        try {
            // Use fetch to get the file as blob
            console.log('Making fetch request...');
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': format === 'pdf' ? 'application/pdf' : 'text/csv',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            
            console.log('Response status:', response.status);
            console.log('Response headers:', [...response.headers.entries()]);
            
            if (!response.ok) {
                const errorText = await response.text();
                console.error('Response error:', errorText);
                throw new Error(`HTTP error! status: ${response.status}, message: ${errorText}`);
            }
            
            // Get the filename from response headers or create default
            const contentDisposition = response.headers.get('Content-Disposition');
            let filename = `psychological-test-${candidate.id}.${format === 'pdf' ? 'pdf' : 'csv'}`;
            
            console.log('Content-Disposition:', contentDisposition);
            
            if (contentDisposition) {
                const filenameMatch = contentDisposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/);
                if (filenameMatch) {
                    filename = filenameMatch[1].replace(/['"]/g, '');
                }
            }
            
            console.log('Final filename:', filename);
            
            // Convert response to blob
            console.log('Converting to blob...');
            const blob = await response.blob();
            console.log('Blob size:', blob.size, 'type:', blob.type);
            
            // Create download link
            console.log('Creating download link...');
            const downloadUrl = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = filename;
            
            // Trigger download
            console.log('Triggering download...');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Clean up
            window.URL.revokeObjectURL(downloadUrl);
            console.log('Download completed successfully');
            
        } catch (error) {
            console.error('downloadFile error:', error);
            throw error;
        }
    };

    const handleManualScoring = async () => {
        if (isSubmitting) return;
        
        // Validate Zoom link and schedule if candidate passed
        if (manualStatus === 'passed') {
            if (!zoomLink.trim()) {
                alert('Link Zoom interview wajib diisi untuk candidate yang lolos!');
                return;
            }
            if (!scheduledAt.trim()) {
                alert('Jadwal interview wajib diisi untuk candidate yang lolos!');
                return;
            }
        }
        
        setIsSubmitting(true);
        try {
            await router.post(`/dashboard/recruitment/assessment/${candidate.id}/update-psychological-score`, {
                status: manualStatus,
                manual_score: manualScore ? parseFloat(manualScore) : null,
                notes: manualNotes,
                zoom_link: manualStatus === 'passed' ? zoomLink : null,
                scheduled_at: manualStatus === 'passed' ? scheduledAt : null,
            });
            setManualScoringDialog(false);
            setManualScore('');
            setManualNotes('');
            setZoomLink('');
            setScheduledAt('');
        } catch (error) {
            console.error('Failed to update psychological test score:', error);
        } finally {
            setIsSubmitting(false);
        }
    };

    const startedAt = candidate?.application_started_at ?? candidate?.stages?.psychological_test?.started_at ?? candidate?.history?.[0]?.processed_at ?? null;
    
    // Get the psychological test history specifically, not just the first history
    const psychologicalTestHistory = candidate?.history?.find((h: any) => 
        h?.status?.code === 'psychotest' || 
        h?.status?.code === 'psychological_test' || 
        h?.status?.name?.toLowerCase()?.includes('psychological') ||
        h?.status?.name?.toLowerCase()?.includes('assessment')
    );
    
    const completedAt = candidate?.stages?.psychological_test?.completed_at ?? psychologicalTestHistory?.completed_at ?? null;
    
    // Get saved score from history if available
    const savedHistoryScore = psychologicalTestHistory?.score;
    
    // Check if assessment has been completed (decision already made)
    // Assessment is considered completed if:
    // 1. There's a completed_at date, OR
    // 2. The status is not 'pending' and there's a score or notes
    const isAssessmentCompleted = completedAt !== null || 
        (psychologicalTestHistory && 
         psychologicalTestHistory.status?.code !== 'pending' && 
         (psychologicalTestHistory.score !== null || psychologicalTestHistory.notes !== null));
    
    // Also check if application has moved to next stage (interview)
    const hasMovedToNextStage = candidate?.history?.some((h: any) => 
        h.status?.code === 'interview' || 
        h.status?.stage === 'interview'
    ) || false;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Assessment Detail" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Button variant="ghost" size="icon" onClick={() => window.history.back()}>
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                        <h1 className="text-2xl font-semibold">Assessment Detail</h1>
                    </div>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Candidate</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div>
                                <p className="text-sm text-muted-foreground">Name</p>
                                <div className="font-medium">{candidate?.user?.name ?? '-'}</div>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Position</p>
                                <div className="font-medium">{candidate?.vacancy?.title ?? (candidate?.vacancy_period?.vacancy?.title ?? '-')}</div>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Test Type</p>
                                <div className="font-medium text-blue-700">{getTestType()}</div>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Test Status</p>
                                <div className="font-medium">
                                    {completedAt ? (
                                        <span className="text-green-600">Completed</span>
                                    ) : startedAt ? (
                                        <span className="text-yellow-600">In Progress</span>
                                    ) : (
                                        <span className="text-gray-600">Not Started</span>
                                    )}
                                </div>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Started At</p>
                                <div className="font-medium">{startedAt ? format(new Date(startedAt), 'dd MMM yyyy HH:mm') : '-'}</div>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Completed At</p>
                                <div className="font-medium">{completedAt ? format(new Date(completedAt), 'dd MMM yyyy HH:mm') : '-'}</div>
                            </div>
                            {/* Only show score for non-psychological tests */}
                            {!isPsychologicalTest() && (
                                <div>
                                    <p className="text-sm text-muted-foreground">Score</p>
                                    <div className="font-medium">
                                        {(() => {
                                            // Calculate score from answers
                                            const answers = candidate?.stages?.psychological_test?.answers || [];
                                            if (answers.length > 0) {
                                                const correctAnswers = answers.filter((answer: any) => answer.selected_answer?.is_correct).length;
                                                const totalAnswers = answers.length;
                                                const score = (correctAnswers / totalAnswers) * 100;
                                                return (
                                                    <span className="text-blue-700 font-semibold">
                                                        {score.toFixed(2)}% ({correctAnswers}/{totalAnswers})
                                                    </span>
                                                );
                                            }
                                            return '-';
                                        })()}
                                    </div>
                                </div>
                            )}
                        </div>
                        
                        {/* Test Description */}
                        <div className="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <h4 className="font-semibold text-blue-900 mb-2">About This Test</h4>
                            <p className="text-sm text-blue-800">{getTestDescription()}</p>
                        </div>
                        <div className="mt-4 flex items-center gap-3">
                            {isPsychologicalTest() ? (
                                <div className="flex gap-2">
                                    {/* Export buttons only show if candidate has answers AND test is completed */}
                                    {candidate?.stages?.psychological_test?.answers?.length > 0 && completedAt && (
                                        <>
                                            <Button 
                                                variant="outline" 
                                                className="gap-2" 
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    handleExportPsychologicalTest('pdf');
                                                }}
                                                disabled={isExporting}
                                                type="button"
                                            >
                                                <FileText className="h-4 w-4" />
                                                {isExporting ? 'Opening...' : 'Export PDF'}
                                            </Button>
                                            <Button 
                                                variant="outline" 
                                                className="gap-2" 
                                                onClick={(e) => {
                                                    e.preventDefault();
                                                    handleExportPsychologicalTest('excel');
                                                }}
                                                disabled={isExporting}
                                                type="button"
                                            >
                                                <Download className="h-4 w-4" />
                                                {isExporting ? 'Opening...' : 'Export Excel'}
                                            </Button>
                                        </>
                                    )}
                                    
                                    {/* Manual scoring only available if test is completed by candidate AND assessment decision not made yet */}
                                    {completedAt && !isAssessmentCompleted && !hasMovedToNextStage ? (
                                        <Button variant="secondary" className="gap-2" onClick={() => setManualScoringDialog(true)}>
                                            <BookOpen className="h-4 w-4" />
                                            Manual Scoring
                                        </Button>
                                    ) : completedAt && (isAssessmentCompleted || hasMovedToNextStage) ? (
                                        /* Show status if assessment decision already made */
                                        <div className="text-sm text-blue-600 bg-blue-50 dark:bg-blue-900/20 px-3 py-2 rounded-md border border-blue-200 dark:border-blue-800">
                                            ✓ Assessment sudah selesai. Keputusan sudah diberikan.
                                        </div>
                                    ) : (
                                        /* Show status if test not completed yet */
                                        <div className="text-sm text-amber-600 bg-amber-50 px-3 py-2 rounded-md border border-amber-200">
                                            ⏳ Candidate has not completed the psychological test yet
                                        </div>
                                    )}
                                </div>
                            ) : (
                                // NON-PSYCHOLOGICAL / TECHNICAL tests: show answers + accept/reject actions
                                <div className="w-full">
                                    {/* Score Summary Card */}
                                    <div className="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                        <h4 className="font-semibold text-blue-900 mb-2">Assessment Results</h4>
                                        {(() => {
                                            const answers = candidate?.stages?.psychological_test?.answers || [];
                                            if (answers.length > 0) {
                                                // Separate multiple choice and essay
                                                const multipleChoiceAnswers = answers.filter((answer: any) => 
                                                    answer.question.type === 'multiple_choice' || !answer.question.type
                                                );
                                                const essayAnswers = answers.filter((answer: any) => 
                                                    answer.question.type === 'essay'
                                                );
                                                
                                                // Calculate multiple choice score
                                                const correctMultipleChoice = multipleChoiceAnswers.filter((answer: any) => 
                                                    answer.selected_answer?.is_correct
                                                ).length;
                                                const multipleChoiceScore = multipleChoiceAnswers.length > 0 
                                                    ? (correctMultipleChoice / multipleChoiceAnswers.length) * 100 
                                                    : 0;
                                                
                                                // Calculate essay score (from manual scores)
                                                const essayScoreValues = essayAnswers
                                                    .map((answer: any) => {
                                                        // Use saved score if available, otherwise use original score
                                                        return savedEssayScores[answer.id] !== undefined 
                                                            ? savedEssayScores[answer.id] 
                                                            : (answer.score !== null && answer.score !== undefined && typeof answer.score === 'number' 
                                                                ? answer.score 
                                                                : null);
                                                    })
                                                    .filter((score: any) => score !== null && score !== undefined && typeof score === 'number')
                                                    .map((score: any) => Number(score));
                                                const totalEssayScore = essayScoreValues.reduce((sum: number, score: number) => sum + score, 0);
                                                const essayScore = essayScoreValues.length > 0 ? totalEssayScore / essayScoreValues.length : 0;
                                                
                                                // Calculate weighted average
                                                const totalQuestions = answers.length;
                                                let finalScore = 0;
                                                if (multipleChoiceAnswers.length > 0) {
                                                    finalScore += (multipleChoiceScore * multipleChoiceAnswers.length);
                                                }
                                                if (essayAnswers.length > 0 && essayScoreValues.length === essayAnswers.length) {
                                                    finalScore += (essayScore * essayAnswers.length);
                                                }
                                                finalScore = totalQuestions > 0 ? finalScore / totalQuestions : 0;
                                                
                                                // Use saved score from history if available (when admin has already given decision)
                                                // If hasUnscoredEssay, don't show final score (show "Pending" instead)
                                                const hasUnscoredEssay = essayAnswers.length > 0 && essayScoreValues.length < essayAnswers.length;
                                                
                                                const displayScore = hasUnscoredEssay && savedHistoryScore === null
                                                    ? null // Don't show score if essay is not scored and no saved score
                                                    : (savedHistoryScore !== null && savedHistoryScore !== undefined 
                                                        ? Number(savedHistoryScore) 
                                                        : finalScore);
                                                
                                                const scoreColor = displayScore === null 
                                                    ? 'text-amber-700' 
                                                    : (displayScore >= 70 ? 'text-green-700' : displayScore >= 50 ? 'text-yellow-700' : 'text-red-700');
                                                
                                                return (
                                                    <div>
                                                        <div className="grid grid-cols-3 gap-4 text-center mb-3">
                                                            <div>
                                                                <p className="text-sm text-gray-600">Total Questions</p>
                                                                <p className="text-2xl font-bold text-blue-700">{totalQuestions}</p>
                                                            </div>
                                                            <div>
                                                                <p className="text-sm text-gray-600">Multiple Choice</p>
                                                                <p className="text-xl font-bold text-gray-700">
                                                                    {correctMultipleChoice}/{multipleChoiceAnswers.length}
                                                                </p>
                                                                <p className="text-xs text-gray-500">{multipleChoiceScore.toFixed(1)}%</p>
                                                            </div>
                                                            <div>
                                                                <p className="text-sm text-gray-600">Final Score</p>
                                                                <p className={`text-2xl font-bold ${scoreColor}`}>
                                                                    {displayScore === null || (hasUnscoredEssay && savedHistoryScore === null) 
                                                                        ? 'Pending' 
                                                                        : displayScore.toFixed(2) + '%'}
                                                                </p>
                                                            </div>
                                                        </div>
                                                        {hasUnscoredEssay && (
                                                            <div className="mt-2 p-2 bg-amber-100 border border-amber-300 rounded text-sm text-amber-800 text-center">
                                                                ⚠️ Please score all essay questions to calculate final score
                                                            </div>
                                                        )}
                                                        {essayAnswers.length > 0 && (
                                                            <div className="mt-2 text-sm text-gray-600 text-center">
                                                                Essay Questions: {essayScoreValues.length}/{essayAnswers.length} scored
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            }
                                            return <p className="text-gray-500">No assessment data available</p>;
                                        })()}
                                    </div>

                                    <div className="space-y-4 mb-4">
                                        <h3 className="font-medium">Detailed Answers</h3>
                                        {candidate?.stages?.psychological_test?.answers && candidate.stages.psychological_test.answers.length > 0 ? (
                                            candidate.stages.psychological_test.answers.map((answer: any, idx: number) => {
                                                const isEssay = answer.question.type === 'essay';
                                                const isMultipleChoice = answer.question.type === 'multiple_choice' || !answer.question.type;
                                                
                                                // Get score from saved state or original answer
                                                const currentScore = savedEssayScores[answer.id] !== undefined 
                                                    ? savedEssayScores[answer.id] 
                                                    : (answer.score !== null && answer.score !== undefined && typeof answer.score === 'number' 
                                                        ? answer.score 
                                                        : null);
                                                
                                                return (
                                                    <div key={idx} className="rounded-lg border p-4">
                                                        <div className="flex items-start justify-between mb-3">
                                                            <div className="flex-1">
                                                                <div className="flex items-center gap-2 mb-2">
                                                                    <p className="font-medium">Question {idx + 1}</p>
                                                                    {isEssay && (
                                                                        <span className="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded">Essay</span>
                                                                    )}
                                                                    {isMultipleChoice && (
                                                                        <span className="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">Multiple Choice</span>
                                                                    )}
                                                                </div>
                                                                <p className="mt-2">{answer.question.text}</p>
                                                            </div>
                                                            {isMultipleChoice && (
                                                                answer.selected_answer.is_correct ? (
                                                                    <CheckCircle2 className="h-6 w-6 text-green-500" />
                                                                ) : (
                                                                    <XCircle className="h-6 w-6 text-red-500" />
                                                                )
                                                            )}
                                                        </div>
                                                        
                                                        {isMultipleChoice ? (
                                                            <div className="mt-4 space-y-2">
                                                                {answer.question.choices && answer.question.choices.length > 0 ? (
                                                                    answer.question.choices.map((choice: any, choiceIndex: number) => {
                                                                        let className = "flex items-center gap-2 rounded-lg p-2 ";
                                                                        if (choice.text === answer.selected_answer.text) {
                                                                            className += answer.selected_answer.is_correct 
                                                                                ? "bg-green-50 text-green-700 font-medium" 
                                                                                : "bg-red-50 text-red-700 font-medium";
                                                                        } else if (choice.is_correct) {
                                                                            className += "bg-green-50 text-green-700";
                                                                        }

                                                                        return (
                                                                            <div key={choiceIndex} className={className}>
                                                                                {choice.text === answer.selected_answer.text && (
                                                                                    <div className={`h-2 w-2 rounded-full ${choice.is_correct ? 'bg-green-500' : 'bg-red-500'}`} />
                                                                                )}
                                                                                <span>{choice.text}</span>
                                                                            </div>
                                                                        );
                                                                    })
                                                                ) : (
                                                                    <div className="text-gray-500 italic">No choices available</div>
                                                                )}
                                                            </div>
                                                        ) : (
                                                            <div className="mt-4 space-y-3">
                                                                <div className="bg-gray-50 p-3 rounded-lg border">
                                                                    <p className="text-sm font-medium text-gray-700 mb-1">Candidate's Answer:</p>
                                                                    <p className="text-gray-900 whitespace-pre-wrap">{answer.answer_text || answer.selected_answer.text || 'No answer provided'}</p>
                                                                </div>
                                                                <div className="flex items-center gap-3">
                                                                    <Label htmlFor={`essay-score-${answer.id}`} className="text-sm font-medium">
                                                                        Score (0-100):
                                                                    </Label>
                                                                    <Input
                                                                        id={`essay-score-${answer.id}`}
                                                                        type="number"
                                                                        min="0"
                                                                        max="100"
                                                                        step="0.01"
                                                                        value={essayScores[answer.id] !== undefined && essayScores[answer.id] !== ''
                                                                            ? essayScores[answer.id] 
                                                                            : (currentScore !== null && currentScore !== undefined
                                                                                ? currentScore.toString() 
                                                                                : '')}
                                                                        onChange={(e) => {
                                                                            const value = e.target.value;
                                                                            setEssayScores(prev => ({
                                                                                ...prev,
                                                                                [answer.id]: value
                                                                            }));
                                                                        }}
                                                                        onBlur={(e) => {
                                                                            const scoreValue = parseFloat(e.target.value);
                                                                            if (!isNaN(scoreValue) && scoreValue >= 0 && scoreValue <= 100) {
                                                                                const score = Number(scoreValue);
                                                                                setIsSavingScore(prev => ({ ...prev, [answer.id]: true }));
                                                                                
                                                                                router.post(`/dashboard/recruitment/assessment/${candidate.id}/essay-score`, {
                                                                                    answer_id: answer.id,
                                                                                    score: score,
                                                                                }, {
                                                                                    preserveScroll: true,
                                                                                    onSuccess: (page) => {
                                                                                        // Update saved scores state immediately
                                                                                        setSavedEssayScores(prev => ({
                                                                                            ...prev,
                                                                                            [answer.id]: score
                                                                                        }));
                                                                                        setEssayScores(prev => ({
                                                                                            ...prev,
                                                                                            [answer.id]: score.toString()
                                                                                        }));
                                                                                        setIsSavingScore(prev => ({ ...prev, [answer.id]: false }));
                                                                                        
                                                                                        // Also update from the response data if available
                                                                                        if (page?.props?.candidate?.stages?.psychological_test?.answers) {
                                                                                            const updatedAnswers = page.props.candidate.stages.psychological_test.answers;
                                                                                            const updatedEssayAnswer = updatedAnswers.find((a: any) => a.id === answer.id);
                                                                                            if (updatedEssayAnswer && updatedEssayAnswer.score !== null && updatedEssayAnswer.score !== undefined) {
                                                                                                const updatedScore = typeof updatedEssayAnswer.score === 'number' 
                                                                                                    ? updatedEssayAnswer.score 
                                                                                                    : parseFloat(updatedEssayAnswer.score);
                                                                                                if (!isNaN(updatedScore)) {
                                                                                                    setSavedEssayScores(prev => ({
                                                                                                        ...prev,
                                                                                                        [answer.id]: updatedScore
                                                                                                    }));
                                                                                                    setEssayScores(prev => ({
                                                                                                        ...prev,
                                                                                                        [answer.id]: updatedScore.toString()
                                                                                                    }));
                                                                                                }
                                                                                            }
                                                                                        }
                                                                                    },
                                                                                    onError: (errors) => {
                                                                                        console.error('Error saving essay score:', errors);
                                                                                        setIsSavingScore(prev => ({ ...prev, [answer.id]: false }));
                                                                                        alert('Gagal menyimpan score. Silakan coba lagi.');
                                                                                    }
                                                                                });
                                                                            } else if (e.target.value === '') {
                                                                                // Clear the input if empty
                                                                                setEssayScores(prev => {
                                                                                    const newScores = { ...prev };
                                                                                    delete newScores[answer.id];
                                                                                    return newScores;
                                                                                });
                                                                            }
                                                                        }}
                                                                        disabled={isSavingScore[answer.id]}
                                                                        className="w-24"
                                                                        placeholder="0-100"
                                                                    />
                                                                    {isSavingScore[answer.id] && (
                                                                        <span className="text-xs text-gray-500">Saving...</span>
                                                                    )}
                                                                    {!isSavingScore[answer.id] && currentScore !== null && (
                                                                        <span className="text-sm text-gray-600">
                                                                            {Number(currentScore).toFixed(2)}%
                                                                        </span>
                                                                    )}
                                                                </div>
                                                            </div>
                                                        )}
                                                    </div>
                                                );
                                            })
                                        ) : (
                                            <div className="rounded-lg border p-4 text-center text-gray-500">
                                                <p>No answers available yet.</p>
                                            </div>
                                        )}
                                    </div>

                                    {/* Only show action buttons if assessment is not completed yet */}
                                    {!isAssessmentCompleted && !hasMovedToNextStage && (
                                        <div className="flex justify-end gap-3">
                                            <Button variant="outline" className="gap-2" onClick={() => setActionDialog({ isOpen: true, action: 'reject' })}>
                                                Reject
                                            </Button>
                                            <Button className="gap-2" onClick={() => setActionDialog({ isOpen: true, action: 'accept' })}>
                                                Pass to Interview
                                            </Button>
                                        </div>
                                    )}
                                    
                                    {/* Show status message if assessment is already completed */}
                                    {(isAssessmentCompleted || hasMovedToNextStage) && (
                                        <div className="flex justify-end">
                                            <div className="rounded-lg border p-4 bg-blue-50 dark:bg-blue-900/20">
                                                <p className="text-sm text-blue-800 dark:text-blue-200 font-medium">
                                                    {hasMovedToNextStage 
                                                        ? '✓ Assessment sudah selesai. Candidate telah dipindahkan ke tahap Interview.'
                                                        : '✓ Assessment sudah selesai. Keputusan sudah diberikan.'}
                                                </p>
                                                {completedAt && (
                                                    <p className="text-xs text-blue-600 dark:text-blue-300 mt-1">
                                                        Selesai pada: {format(new Date(completedAt), 'dd MMM yyyy HH:mm')}
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </CardContent>
                </Card>
                <Dialog open={manualScoringDialog} onOpenChange={setManualScoringDialog}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader>
                            <DialogTitle>Manual Psychological Test Evaluation</DialogTitle>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div className="bg-blue-50 p-3 rounded-lg">
                                <p className="text-sm text-blue-800">
                                    <strong>Candidate:</strong> {candidate?.user?.name ?? '-'}<br />
                                    <strong>Test Type:</strong> {getTestType()}<br />
                                    <strong>Total Questions:</strong> {candidate?.stages?.psychological_test?.answers?.length ?? 0}<br />
                                    <strong>Test Duration:</strong> {candidate?.vacancy?.question_pack?.duration ?? 'Not specified'} minutes
                                </p>
                            </div>
                            <div className="bg-gray-50 p-3 rounded-lg">
                                <p className="text-xs text-gray-600">
                                    <strong>Test Description:</strong><br />
                                    {getTestDescription()}
                                </p>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="evaluation-status">Evaluation Result</Label>
                                <Select value={manualStatus} onValueChange={(value: 'passed' | 'rejected') => setManualStatus(value)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="passed">PASS - Proceed to Interview</SelectItem>
                                        <SelectItem value="rejected">FAIL - Reject Application</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="manual-score">Manual Score (0-100, Optional)</Label>
                                <Input
                                    id="manual-score"
                                    type="number"
                                    min={0}
                                    max={100}
                                    step={0.1}
                                    placeholder="Enter psychological test score"
                                    value={manualScore}
                                    onChange={(e) => setManualScore(e.target.value)}
                                />
                                <p className="text-xs text-gray-500">This score is for reference only</p>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="evaluation-notes">Evaluator Notes</Label>
                                <Textarea
                                    id="evaluation-notes"
                                    placeholder="Add your evaluation notes..."
                                    rows={4}
                                    value={manualNotes}
                                    onChange={(e) => setManualNotes(e.target.value)}
                                />
                            </div>
                            {/* Zoom Link field - required when status is passed */}
                            {manualStatus === 'passed' && (
                                <>
                                    <div className="space-y-2">
                                        <Label htmlFor="zoom-link" className="text-red-600">
                                            Zoom Interview Link <span className="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            id="zoom-link"
                                            type="url"
                                            placeholder="https://zoom.us/j/..."
                                            value={zoomLink}
                                            onChange={(e) => setZoomLink(e.target.value)}
                                            className={!zoomLink.trim() ? 'border-red-300 focus:border-red-500' : ''}
                                            required
                                        />
                                        <p className="text-xs text-red-600">
                                            Wajib diisi untuk candidate yang lolos ke tahap interview
                                        </p>
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="scheduled-at" className="text-red-600">
                                            Jadwal Interview <span className="text-red-500">*</span>
                                        </Label>
                                        <Input
                                            id="scheduled-at"
                                            type="datetime-local"
                                            value={scheduledAt}
                                            onChange={(e) => setScheduledAt(e.target.value)}
                                            className={!scheduledAt.trim() ? 'border-red-300 focus:border-red-500' : ''}
                                            required
                                        />
                                        <p className="text-xs text-red-600">
                                            Tentukan tanggal dan jam interview untuk candidate
                                        </p>
                                    </div>
                                </>
                            )}
                        </div>
                        <DialogFooter>
                            <Button variant="outline" onClick={() => setManualScoringDialog(false)} disabled={isSubmitting}>
                                Cancel
                            </Button>
                            <Button onClick={handleManualScoring} disabled={isSubmitting} className={manualStatus === 'passed' ? '' : 'bg-red-600 hover:bg-red-700'}>
                                {isSubmitting ? 'Processing...' : `${manualStatus === 'passed' ? 'Pass' : 'Reject'} Candidate`}
                            </Button>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
                    {/* Stage Action Dialog for non-psych assessments */}
                    {actionDialog && (
                        <StageActionDialog
                            isOpen={actionDialog.isOpen}
                            onClose={() => setActionDialog(null)}
                            applicationId={candidate.id}
                            stage="psychological_test"
                            action={actionDialog.action}
                            title={actionDialog.action === 'accept' ? 'Pass to Interview Stage' : 'Reject Application'}
                            description={actionDialog.action === 'accept'
                                ? 'The candidate will proceed to the interview stage. You may add optional notes.'
                                : 'The candidate will be rejected from the recruitment process. Please provide a reason for rejection.'
                            }
                            noScore={false}
                        />
                    )}
            </div>
        </AppLayout>
    );
}
