import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Skeleton } from '@/components/ui/skeleton';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ChevronLeft, ChevronRight, Edit, Eye, Trash2 } from 'lucide-react';

// Vacancy interface
export interface VacancyItem {
    id: number;
    title: string;
    department: string;
    company?: {
        id: number;
        name: string;
    };
}

// Company interface
export interface CompanyItem {
    id: number;
    name: string;
}

// Period interface aligned with index.tsx
export interface Period {
    id: string;
    name: string;
    startTime: string;
    endTime: string;
    status: string;
    description?: string;
    title?: string;
    department?: string;
    questionPack?: string;
    applicantsCount?: number;
    vacanciesList?: VacancyItem[];
    companies?: CompanyItem[];
}

interface PaginationData {
    total: number;
    per_page: number;
    current_page: number;
    last_page: number;
}

interface PeriodsTableProps {
    periods: Period[];
    pagination: PaginationData;
    onView: (periodId: string) => void;
    onEdit: (periodId: string) => void;
    onDelete: (periodId: string) => void;
    onSelect: (periodId: string) => void;
    selectedPeriodId: string | null;
    onPageChange: (page: number) => void;
    onPerPageChange: (perPage: number) => void;
    isLoading: boolean;
    // Add new navigation handlers
    onNavigateToAssessment?: (periodId: string) => void;
    onNavigateToInterview?: (periodId: string) => void;
    onNavigateToReports?: (periodId: string) => void;
}

// Helper function to format dates
const formatSimpleDate = (dateString: string) => {
    if (!dateString) return '-';
    
    try {
        // If already in DD/MM/YYYY format, return as is
        if (dateString.match(/^\d{1,2}\/\d{1,2}\/\d{4}$/)) {
            return dateString;
        }
        
        // Handle ISO date strings
        if (dateString.includes('T') || dateString.includes('-')) {
            const parts = dateString.split(/[-T]/);
            if (parts.length >= 3) {
                const year = parts[0];
                const month = parts[1];
                const day = parts[2].split(':')[0].split(' ')[0];
                return `${day}/${month}/${year}`;
            }
        }
        
        return dateString;
    } catch (error) {
        console.error('Error formatting date:', error);
        return dateString;
    }
};

// Options for items per page in pagination
const itemsPerPageOptions = [5, 10, 20, 50, 100];

export function PeriodsTable({
    periods,
    pagination,
    onView,
    onEdit,
    onDelete,
    onSelect,
    selectedPeriodId,
    onPageChange,
    onPerPageChange,
    isLoading,
    onNavigateToAssessment,
    onNavigateToInterview,
    onNavigateToReports,
}: PeriodsTableProps) {
    const handleNextPage = () => {
        if (pagination.current_page < pagination.last_page) {
            onPageChange(pagination.current_page + 1);
        }
    };

    const handlePrevPage = () => {
        if (pagination.current_page > 1) {
            onPageChange(pagination.current_page - 1);
        }
    };

    const handleItemsPerPageChange = (value: string) => {
        onPerPageChange(Number(value));
    };

    // Handle row click - navigate to administration page
    const handleRowClick = (periodId: string, event: React.MouseEvent) => {
        // Prevent navigation if clicking on action buttons
        if ((event.target as HTMLElement).closest('button')) {
            return;
        }
        
        onSelect(periodId);
    };

    return (
        <div className="w-full">
            {/* Responsive table container with horizontal scroll */}
            <div className="overflow-x-auto rounded-md border border-border">
                <Table className="min-w-full bg-muted/30 dark:bg-muted/20 [&_tr:hover]:bg-transparent">
                    <TableHeader className="bg-muted/30 dark:bg-muted/20 [&_tr:hover]:bg-transparent">
                        <TableRow className="hover:bg-transparent [&>th]:hover:bg-transparent">
                            <TableHead className="w-[60px] py-3">ID</TableHead>
                            <TableHead className="w-[180px] py-3">Name</TableHead>
                            <TableHead className="w-[120px] py-3">Start Date</TableHead>
                            <TableHead className="w-[120px] py-3">End Date</TableHead>
                            <TableHead className="w-[100px] py-3">Status</TableHead>
                            <TableHead className="w-[150px] py-3">Companies</TableHead>
                            <TableHead className="w-[100px] py-3">Applicants</TableHead>
                            <TableHead className="w-[140px] py-3 text-center">Action</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {isLoading ? (
                            // Skeleton loading rows
                            Array(pagination.per_page)
                                .fill(0)
                                .map((_, idx) => (
                                    <TableRow key={`skeleton-${idx}`}>
                                        <TableCell className="w-[60px]">
                                            <Skeleton className="h-4 w-8" />
                                        </TableCell>
                                        <TableCell className="w-[180px]">
                                            <Skeleton className="h-4 w-full" />
                                        </TableCell>
                                        <TableCell className="w-[120px]">
                                            <Skeleton className="h-4 w-20" />
                                        </TableCell>
                                        <TableCell className="w-[120px]">
                                            <Skeleton className="h-4 w-20" />
                                        </TableCell>
                                        <TableCell className="w-[100px]">
                                            <Skeleton className="h-6 w-16 rounded-full" />
                                        </TableCell>
                                        <TableCell className="w-[150px]">
                                            <Skeleton className="h-4 w-24" />
                                        </TableCell>
                                        <TableCell className="w-[100px]">
                                            <Skeleton className="h-4 w-8" />
                                        </TableCell>
                                        <TableCell className="w-[140px] text-center">
                                            <div className="flex justify-center space-x-2">
                                                <Skeleton className="h-8 w-8 rounded-full" />
                                                <Skeleton className="h-8 w-8 rounded-full" />
                                                <Skeleton className="h-8 w-8 rounded-full" />
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))
                        ) : periods.length > 0 ? (
                            periods.map((period, index) => (
                                <TableRow 
                                    key={period.id} 
                                    onClick={(e) => handleRowClick(period.id, e)}
                                    className={`
                                        cursor-pointer transition-colors hover:bg-muted
                                        ${selectedPeriodId === period.id 
                                            ? 'bg-primary/10 dark:bg-primary/20' 
                                            : index % 2 === 0 ? 'bg-background' : 'bg-muted/30 dark:bg-muted/20'
                                        }
                                    `}
                                >
                                    <TableCell className="whitespace-nowrap font-medium text-foreground">
                                        {String(period.id).padStart(2, '0')}
                                    </TableCell>
                                    <TableCell className="font-medium whitespace-nowrap text-foreground">
                                        {period.name}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap text-foreground">
                                        {formatSimpleDate(period.startTime)}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap text-foreground">
                                        {formatSimpleDate(period.endTime)}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                                            period.status === 'Open' ? 'bg-green-500/20 text-green-700 dark:text-green-400' : 
                                            period.status === 'Closed' ? 'bg-destructive/20 text-destructive dark:text-destructive' : 
                                            period.status === 'Upcoming' ? 'bg-primary/20 text-primary dark:text-primary' : 
                                            'bg-muted text-muted-foreground'
                                        }`}>
                                            {period.status || 'Not Set'}
                                        </span>
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap">
                                        {period.companies && period.companies.length > 0 ? (
                                            <div className="flex flex-wrap gap-1">
                                                {period.companies.map((company, idx) => (
                                                    <span 
                                                        key={company.id}
                                                        className="px-2 py-1 text-xs font-medium rounded-full bg-primary/10 text-primary dark:bg-primary/20 dark:text-primary"
                                                    >
                                                        {company.name}
                                                    </span>
                                                ))}
                                            </div>
                                        ) : (
                                            <span className="text-muted-foreground text-sm">No companies</span>
                                        )}
                                    </TableCell>
                                    <TableCell className="whitespace-nowrap text-foreground">
                                        {period.applicantsCount || 0}
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex justify-center space-x-3">
                                            <button
                                                onClick={(e) => {
                                                    e.stopPropagation(); // Prevent row click
                                                    onView(period.id);
                                                }}
                                                className="rounded-full p-1.5 text-primary hover:bg-primary/10 hover:text-primary dark:hover:bg-primary/20 transition-colors"
                                            >
                                                <Eye className="h-4.5 w-4.5" />
                                            </button>
                                            <button
                                                onClick={(e) => {
                                                    e.stopPropagation(); // Prevent row click
                                                    onEdit(period.id);
                                                }}
                                                className="rounded-full p-1.5 text-primary hover:bg-primary/10 hover:text-primary dark:hover:bg-primary/20 transition-colors"
                                            >
                                                <Edit className="h-4.5 w-4.5" />
                                            </button>
                                            <button
                                                onClick={(e) => {
                                                    e.stopPropagation(); // Prevent row click
                                                    onDelete(period.id);
                                                }}
                                                className="rounded-full p-1.5 text-destructive hover:bg-destructive/10 hover:text-destructive dark:hover:bg-destructive/20 transition-colors"
                                            >
                                                <Trash2 className="h-4.5 w-4.5" />
                                            </button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        ) : (
                            <TableRow>
                                <TableCell colSpan={7} className="py-4 text-center text-foreground">
                                    No periods found
                                </TableCell>
                            </TableRow>
                        )}
                    </TableBody>
                </Table>
            </div>

            {/* Responsive pagination controls */}
            <div className="mt-6 flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div className="flex items-center space-x-2">
                    <span className="text-sm text-muted-foreground">Show</span>
                    <Select value={pagination.per_page.toString()} onValueChange={handleItemsPerPageChange} disabled={isLoading}>
                        <SelectTrigger className="h-8 w-16 rounded">
                            <SelectValue placeholder={pagination.per_page.toString()} />
                        </SelectTrigger>
                        <SelectContent>
                            {itemsPerPageOptions.map((option) => (
                                <SelectItem
                                    key={option}
                                    value={option.toString()}
                                >
                                    {option}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <span className="text-sm text-muted-foreground">per page</span>
                </div>

                <div className="flex items-center gap-1">
                    <button
                        onClick={handlePrevPage}
                        disabled={pagination.current_page === 1 || isLoading}
                        className="flex items-center justify-center rounded-md border border-border px-2 py-1 text-foreground hover:bg-muted disabled:opacity-50 transition-colors"
                    >
                        <ChevronLeft className="h-4 w-4" />
                    </button>

                    {/* Responsive page numbers - hide some on small screens */}
                    {Array.from({ length: Math.min(5, pagination.last_page) }).map((_, idx) => {
                        const pageNumber = idx + 1;
                        const isActive = pageNumber === pagination.current_page;
                        // Only show first, last, and current page on small screens
                        const isVisible =
                            pageNumber === 1 ||
                            pageNumber === pagination.last_page ||
                            isActive ||
                            Math.abs(pageNumber - pagination.current_page) <= 1;

                        return (
                            <button
                                key={idx}
                                onClick={() => onPageChange(pageNumber)}
                                disabled={isLoading}
                                className={`flex h-8 min-w-[32px] items-center justify-center rounded-md px-2 transition-colors ${
                                    isActive 
                                        ? 'bg-primary text-primary-foreground' 
                                        : 'border border-border bg-background text-foreground hover:bg-muted'
                                } ${!isVisible ? 'hidden sm:flex' : ''}`}
                            >
                                {pageNumber}
                            </button>
                        );
                    })}

                    <button
                        onClick={handleNextPage}
                        disabled={pagination.current_page === pagination.last_page || isLoading}
                        className="flex items-center justify-center rounded-md border border-border px-2 py-1 text-foreground hover:bg-muted disabled:opacity-50 transition-colors"
                    >
                        <ChevronRight className="h-4 w-4" />
                    </button>
                </div>

                <div className="text-center text-sm text-muted-foreground sm:text-right">
                    Showing {(pagination.current_page - 1) * pagination.per_page + 1} to{' '}
                    {Math.min(pagination.current_page * pagination.per_page, pagination.total)} of {pagination.total} entries
                </div>
            </div>
        </div>
    );
}