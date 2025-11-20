import { Head, router } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Download, Search, Loader2 } from 'lucide-react';
import { useState } from 'react';

interface Candidate {
    no: number;
    id: number;
    name: string;
    period: string;
    date_of_birth: string;
    address: string;
    position: string;
    last_stage: string;
    status: string;
}

interface Props {
    company: {
        id: number;
        name: string;
    };
    periods: Array<{
        id: number;
        name: string;
        status: string;
    }>;
    selectedPeriod?: number | string;
    candidates: Candidate[];
    pagination: {
        total: number;
        per_page: number;
        current_page: number;
        last_page: number;
    };
    filters: {
        search?: string;
        period?: number | string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Kandidat',
        href: '#',
    },
];

export default function CompanyCandidates({ 
    company = { id: 0, name: '' }, 
    periods = [], 
    selectedPeriod, 
    candidates = [], 
    pagination = { total: 0, per_page: 10, current_page: 1, last_page: 1 },
    filters = {}
}: Props) {
    const [searchTerm, setSearchTerm] = useState(filters?.search || '');
    const [selectedPeriodId, setSelectedPeriodId] = useState<string>(selectedPeriod?.toString() || '');
    const [isExporting, setIsExporting] = useState(false);
    const [searchTimeout, setSearchTimeout] = useState<NodeJS.Timeout | null>(null);

    const handleSearchChange = (value: string) => {
        setSearchTerm(value);
        
        // Clear existing timeout
        if (searchTimeout) {
            clearTimeout(searchTimeout);
        }
        
        // Set new timeout for debounced search
        const timeout = setTimeout(() => {
            router.get(
                `/dashboard/companies/${company.id}/candidates`,
                {
                    period: selectedPeriodId || undefined,
                    search: value || undefined,
                    page: 1,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                }
            );
        }, 500);
        
        setSearchTimeout(timeout);
    };

    const handlePeriodChange = (value: string) => {
        setSelectedPeriodId(value);
        router.get(
            `/dashboard/companies/${company.id}/candidates`,
            {
                period: value ? value : undefined,
                search: searchTerm || undefined,
                page: 1,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const handlePageChange = (page: number) => {
        router.get(
            `/dashboard/companies/${company.id}/candidates`,
            {
                period: selectedPeriodId || undefined,
                search: searchTerm || undefined,
                page,
            },
            {
                preserveState: true,
                preserveScroll: true,
            }
        );
    };

    const handleExport = async () => {
        setIsExporting(true);
        try {
            const params = new URLSearchParams({
                period: selectedPeriodId || '',
                format: 'csv',
            });
            
            window.location.href = `/dashboard/companies/${company.id}/candidates/export?${params.toString()}`;
        } catch (error) {
            console.error('Export failed:', error);
        } finally {
            setIsExporting(false);
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'Accepted':
                return <Badge className="bg-green-100 text-green-800 border-green-200">Accepted</Badge>;
            case 'Rejected':
                return <Badge className="bg-red-100 text-red-800 border-red-200">Rejected</Badge>;
            case 'Proses Recruitment':
                return <Badge className="bg-blue-100 text-blue-800 border-blue-200">Proses Recruitment</Badge>;
            default:
                return <Badge className="bg-gray-100 text-gray-800 border-gray-200">{status}</Badge>;
        }
    };

    // Show error if exists
    const error = (filters as any)?.error;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Kandidat - ${company.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {error && (
                    <div className="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                        {error}
                    </div>
                )}
                {/* Header */}
                <div className="bg-blue-600 rounded-lg p-6 text-white">
                    <h1 className="text-3xl font-bold mb-2">Kandidat - {company.name}</h1>
                    <p className="text-blue-100">Daftar kandidat yang melamar di perusahaan ini</p>
                </div>

                {/* Filters Card */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filter Data</CardTitle>
                        <CardDescription>Pilih periode dan cari kandidat</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="space-y-2">
                                <Label htmlFor="period">Periode</Label>
                                <Select value={selectedPeriodId || undefined} onValueChange={handlePeriodChange}>
                                    <SelectTrigger id="period">
                                        <SelectValue placeholder="Pilih Periode" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {periods.map((period) => (
                                            <SelectItem key={period.id} value={period.id.toString()}>
                                                {period.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="search">Cari Kandidat</Label>
                                <div className="relative">
                                    <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
                                    <Input
                                        id="search"
                                        placeholder="Cari nama atau posisi..."
                                        value={searchTerm}
                                        onChange={(e) => handleSearchChange(e.target.value)}
                                        className="pl-10"
                                    />
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Candidates Table Card */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle>Daftar Kandidat</CardTitle>
                            <CardDescription>
                                Total {pagination.total} kandidat
                            </CardDescription>
                        </div>
                        <Button
                            onClick={handleExport}
                            disabled={isExporting}
                            variant="outline"
                            className="gap-2"
                        >
                            {isExporting ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    Mengekspor...
                                </>
                            ) : (
                                <>
                                    <Download className="h-4 w-4" />
                                    Export Excel/CSV
                                </>
                            )}
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-16">No</TableHead>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Periode</TableHead>
                                        <TableHead>Tanggal Lahir</TableHead>
                                        <TableHead>Alamat</TableHead>
                                        <TableHead>Posisi yang Dilamar</TableHead>
                                        <TableHead>Tahap Terakhir</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {candidates.length > 0 ? (
                                        candidates.map((candidate) => (
                                            <TableRow key={candidate.id}>
                                                <TableCell>{candidate.no}</TableCell>
                                                <TableCell className="font-medium">{candidate.name}</TableCell>
                                                <TableCell>{candidate.period}</TableCell>
                                                <TableCell>{candidate.date_of_birth}</TableCell>
                                                <TableCell className="max-w-xs truncate" title={candidate.address}>
                                                    {candidate.address}
                                                </TableCell>
                                                <TableCell>{candidate.position}</TableCell>
                                                <TableCell>{candidate.last_stage}</TableCell>
                                                <TableCell>{getStatusBadge(candidate.status)}</TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={8} className="text-center py-8 text-muted-foreground">
                                                {selectedPeriodId 
                                                    ? 'Tidak ada kandidat ditemukan untuk periode yang dipilih'
                                                    : periods.length === 0 
                                                        ? 'Tidak ada periode yang terbuka untuk perusahaan ini'
                                                        : 'Pilih periode untuk melihat kandidat'}
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Pagination */}
                        {pagination.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-between">
                                <div className="text-sm text-muted-foreground">
                                    Menampilkan {((pagination.current_page - 1) * pagination.per_page) + 1} sampai{' '}
                                    {Math.min(pagination.current_page * pagination.per_page, pagination.total)} dari{' '}
                                    {pagination.total} kandidat
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(pagination.current_page - 1)}
                                        disabled={pagination.current_page === 1}
                                    >
                                        Sebelumnya
                                    </Button>
                                    <div className="flex items-center gap-1">
                                        {Array.from({ length: pagination.last_page }, (_, i) => i + 1)
                                            .filter((page) => {
                                                // Show first page, last page, current page, and pages around current
                                                return (
                                                    page === 1 ||
                                                    page === pagination.last_page ||
                                                    (page >= pagination.current_page - 1 &&
                                                        page <= pagination.current_page + 1)
                                                );
                                            })
                                            .map((page, index, array) => {
                                                // Add ellipsis if needed
                                                const prevPage = array[index - 1];
                                                const showEllipsisBefore = prevPage && page - prevPage > 1;
                                                
                                                return (
                                                    <div key={page} className="flex items-center gap-1">
                                                        {showEllipsisBefore && (
                                                            <span className="px-2 text-muted-foreground">...</span>
                                                        )}
                                                        <Button
                                                            variant={pagination.current_page === page ? "default" : "outline"}
                                                            size="sm"
                                                            onClick={() => handlePageChange(page)}
                                                            className="min-w-[2.5rem]"
                                                        >
                                                            {page}
                                                        </Button>
                                                    </div>
                                                );
                                            })}
                                    </div>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(pagination.current_page + 1)}
                                        disabled={pagination.current_page === pagination.last_page}
                                    >
                                        Selanjutnya
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

