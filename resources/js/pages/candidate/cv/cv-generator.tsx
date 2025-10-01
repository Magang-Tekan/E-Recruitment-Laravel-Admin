import { useState, useEffect } from 'react';
import { Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Download, FileText, Trash2, Eye, Loader2 } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/candidate',
    },
    {
        title: 'CV Generator',
        href: '/candidate/cv',
    },
];

interface CV {
    id: number;
    cv_filename: string;
    download_count: number;
    last_downloaded_at: string | null;
    created_at: string;
}

interface Props {
    cvs?: CV[];
}

export default function CVGenerator({ cvs = [] }: Props) {
    const [cvList, setCvList] = useState<CV[]>(cvs);
    const [isGenerating, setIsGenerating] = useState(false);
    const [isLoading, setIsLoading] = useState(false);

    const generateCV = async () => {
        setIsGenerating(true);
        try {
            const response = await fetch('/candidate/cv/generate', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message);
                // Add new CV to list
                setCvList(prev => [data.data, ...prev]);
                // Open download link
                window.open(data.data.download_url, '_blank');
            } else {
                toast.error(data.message);
            }
        } catch (error) {
            toast.error('Gagal generate CV');
        } finally {
            setIsGenerating(false);
        }
    };

    const downloadCV = (cvId: number) => {
        window.open(`/candidate/cv/download/${cvId}`, '_blank');
    };

    const deleteCV = async (cvId: number) => {
        if (!confirm('Apakah Anda yakin ingin menghapus CV ini?')) return;

        setIsLoading(true);
        try {
            const response = await fetch(`/candidate/cv/${cvId}`, {
                method: 'DELETE',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                toast.success(data.message);
                setCvList(prev => prev.filter(cv => cv.id !== cvId));
            } else {
                toast.error(data.message);
            }
        } catch (error) {
            toast.error('Gagal menghapus CV');
        } finally {
            setIsLoading(false);
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="CV Generator" />
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-3xl font-bold">CV Generator</h1>
                        <p className="text-muted-foreground">
                            Generate dan kelola CV profesional Anda
                        </p>
                    </div>
                    <Button 
                        onClick={generateCV} 
                        disabled={isGenerating}
                        className="gap-2"
                    >
                        {isGenerating ? (
                            <Loader2 className="h-4 w-4 animate-spin" />
                        ) : (
                            <FileText className="h-4 w-4" />
                        )}
                        {isGenerating ? 'Generating...' : 'Generate CV'}
                    </Button>
                </div>

                {/* CV List */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            CV Saya ({cvList.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {cvList.length === 0 ? (
                            <div className="text-center py-8">
                                <FileText className="h-12 w-12 mx-auto text-muted-foreground mb-4" />
                                <h3 className="text-lg font-semibold mb-2">Belum ada CV</h3>
                                <p className="text-muted-foreground mb-4">
                                    Generate CV pertama Anda untuk memulai
                                </p>
                                <Button onClick={generateCV} disabled={isGenerating}>
                                    {isGenerating ? 'Generating...' : 'Generate CV Pertama'}
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {cvList.map((cv) => (
                                    <div
                                        key={cv.id}
                                        className="flex items-center justify-between p-4 border rounded-lg hover:bg-muted/50 transition-colors"
                                    >
                                        <div className="flex items-center gap-4">
                                            <div className="p-2 bg-primary/10 rounded-lg">
                                                <FileText className="h-5 w-5 text-primary" />
                                            </div>
                                            <div>
                                                <h3 className="font-semibold">{cv.cv_filename}</h3>
                                                <p className="text-sm text-muted-foreground">
                                                    Dibuat: {formatDate(cv.created_at)}
                                                </p>
                                                <div className="flex items-center gap-4 mt-1">
                                                    <Badge variant="secondary">
                                                        {cv.download_count} download
                                                    </Badge>
                                                    {cv.last_downloaded_at && (
                                                        <span className="text-xs text-muted-foreground">
                                                            Terakhir diunduh: {formatDate(cv.last_downloaded_at)}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => downloadCV(cv.id)}
                                                className="gap-2"
                                            >
                                                <Download className="h-4 w-4" />
                                                Download
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => deleteCV(cv.id)}
                                                disabled={isLoading}
                                                className="gap-2 text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                                Hapus
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Tips */}
                <Card>
                    <CardHeader>
                        <CardTitle>Tips CV yang Baik</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ul className="space-y-2 text-sm">
                            <li>• Pastikan data profil Anda lengkap dan akurat</li>
                            <li>• Tambahkan pengalaman kerja yang relevan</li>
                            <li>• Cantumkan keahlian dan sertifikasi yang Anda miliki</li>
                            <li>• Update CV secara berkala untuk mencerminkan perkembangan Anda</li>
                            <li>• Gunakan foto profil yang profesional</li>
                        </ul>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

