import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { CheckCircle, Clock, XCircle, User, Calendar, Star, FileText } from 'lucide-react';
import { format } from 'date-fns';

interface HistoryItem {
    id: number;
    stage: string;
    status_name: string;
    status_code: string;
    score?: number;
    notes?: string;
    processed_at?: string;
    completed_at?: string;
    reviewed_at?: string;
    reviewer_name?: string;
    is_active: boolean;
    is_completed: boolean;
}

interface RecruitmentHistoryTimelineProps {
    history: HistoryItem[];
    candidateName: string;
    compact?: boolean; // For smaller display in tables
}

export function RecruitmentHistoryTimeline({ 
    history, 
    candidateName, 
    compact = false 
}: RecruitmentHistoryTimelineProps) {
    
    const formatDate = (dateString?: string) => {
        if (!dateString) return '-';
        try {
            return format(new Date(dateString), 'dd/MM/yyyy HH:mm');
        } catch {
            return '-';
        }
    };

    const getStageIcon = (stage: string, isCompleted: boolean, isActive: boolean) => {
        const iconClass = "h-4 w-4";
        
        if (isCompleted) {
            return <CheckCircle className={`${iconClass} text-green-600`} />;
        } else if (isActive) {
            return <Clock className={`${iconClass} text-blue-600`} />;
        } else {
            return <XCircle className={`${iconClass} text-gray-400`} />;
        }
    };

    const getStageTitle = (stage: string) => {
        const stageMap: { [key: string]: string } = {
            'administrative_selection': 'Seleksi Administrasi',
            'psychological_test': 'Tes Psikologi',
            'interview': 'Wawancara',
            'final': 'Keputusan Akhir'
        };
        return stageMap[stage] || stage.replace('_', ' ').toUpperCase();
    };

    const getStatusBadge = (statusCode: string, isCompleted: boolean, isActive: boolean) => {
        if (isCompleted) {
            return <Badge variant="outline" className="bg-green-100 text-green-800 border-green-200">Selesai</Badge>;
        } else if (isActive) {
            return <Badge variant="outline" className="bg-blue-100 text-blue-800 border-blue-200">Aktif</Badge>;
        } else if (statusCode === 'rejected') {
            return <Badge variant="outline" className="bg-red-100 text-red-800 border-red-200">Ditolak</Badge>;
        } else {
            return <Badge variant="outline" className="bg-gray-100 text-gray-800 border-gray-200">Pending</Badge>;
        }
    };

    if (compact) {
        // Compact view for tables - show only completed stages
        const completedStages = history.filter(h => h.is_completed);
        const currentStage = history.find(h => h.is_active);
        
        return (
            <div className="flex items-center gap-2">
                {completedStages.map((item, index) => (
                    <div key={item.id} className="flex items-center gap-1">
                        {getStageIcon(item.stage, true, false)}
                        <span className="text-xs text-green-600 font-medium">
                            {getStageTitle(item.stage)}
                        </span>
                        {item.score && (
                            <span className="text-xs text-gray-500">({item.score})</span>
                        )}
                        {index < completedStages.length - 1 && (
                            <span className="text-gray-300 mx-1">→</span>
                        )}
                    </div>
                ))}
                {currentStage && (
                    <div className="flex items-center gap-1 ml-2">
                        {getStageIcon(currentStage.stage, false, true)}
                        <span className="text-xs text-blue-600 font-medium">
                            {getStageTitle(currentStage.stage)}
                        </span>
                    </div>
                )}
            </div>
        );
    }

    // Full timeline view
    return (
        <Card>
            <CardHeader>
                <CardTitle className="flex items-center gap-2">
                    <User className="h-5 w-5" />
                    Riwayat Rekrutmen - {candidateName}
                </CardTitle>
            </CardHeader>
            <CardContent>
                <div className="space-y-4">
                    {history.length === 0 ? (
                        <p className="text-gray-500 text-center py-4">Belum ada riwayat rekrutmen</p>
                    ) : (
                        history.map((item, index) => (
                            <div key={item.id} className="relative">
                                {/* Timeline line */}
                                {index < history.length - 1 && (
                                    <div className="absolute left-6 top-8 bottom-0 w-0.5 bg-gray-200"></div>
                                )}
                                
                                <div className="flex gap-4">
                                    {/* Timeline dot */}
                                    <div className="flex-shrink-0 mt-1">
                                        {getStageIcon(item.stage, item.is_completed, item.is_active)}
                                    </div>
                                    
                                    {/* Content */}
                                    <div className="flex-1 space-y-2">
                                        <div className="flex items-center justify-between">
                                            <h4 className="font-medium text-gray-900">
                                                {getStageTitle(item.stage)}
                                            </h4>
                                            {getStatusBadge(item.status_code, item.is_completed, item.is_active)}
                                        </div>
                                        
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-600">
                                            <div className="flex items-center gap-2">
                                                <Calendar className="h-4 w-4" />
                                                <span>
                                                    Dimulai: {formatDate(item.processed_at)}
                                                </span>
                                            </div>
                                            
                                            {item.completed_at && (
                                                <div className="flex items-center gap-2">
                                                    <CheckCircle className="h-4 w-4" />
                                                    <span>
                                                        Selesai: {formatDate(item.completed_at)}
                                                    </span>
                                                </div>
                                            )}
                                            
                                            {item.score !== null && item.score !== undefined && (
                                                <div className="flex items-center gap-2">
                                                    <Star className="h-4 w-4" />
                                                    <span>
                                                        Skor: {item.score}
                                                    </span>
                                                </div>
                                            )}
                                            
                                            {item.reviewer_name && (
                                                <div className="flex items-center gap-2">
                                                    <User className="h-4 w-4" />
                                                    <span>
                                                        Reviewer: {item.reviewer_name}
                                                    </span>
                                                </div>
                                            )}
                                        </div>
                                        
                                        {item.notes && (
                                            <div className="flex gap-2 mt-2">
                                                <FileText className="h-4 w-4 text-gray-400 mt-0.5" />
                                                <p className="text-sm text-gray-600 italic">
                                                    "{item.notes}"
                                                </p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export default RecruitmentHistoryTimeline;