import { SearchBar } from '@/components/searchbar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Building2, Search, Mail, Phone, MapPin, Globe, BarChart2, Users } from 'lucide-react';
import { useMemo, useState } from 'react';

interface Company {
    id: number;
    name: string;
    logo?: string;
    description?: string;
    email?: string;
    phone?: string;
    address?: string;
    website?: string;
    created_at: string;
}

interface Props {
    companies: Company[];
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
];

export default function RecruitmentCompanySelection({ companies }: Props) {
    const [searchQuery, setSearchQuery] = useState('');

    // Filtered companies based on search
    const filteredCompanies = useMemo(() => {
        let result = companies;

        // Apply search filter
        if (searchQuery) {
            result = result.filter(
                (company) =>
                    company.name.toLowerCase().includes(searchQuery.toLowerCase()) ||
                    (company.email && company.email.toLowerCase().includes(searchQuery.toLowerCase())) ||
                    (company.description && company.description.toLowerCase().includes(searchQuery.toLowerCase())),
            );
        }

        return result;
    }, [companies, searchQuery]);


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Recruitment - Select Company" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div>
                    <div className="mb-4 flex items-center justify-between">
                        <div>
                            <h2 className="text-2xl font-semibold">Recruitment</h2>
                            <p className="text-sm text-muted-foreground mt-1">
                                Pilih perusahaan untuk mengakses tahap rekrutmen
                            </p>
                        </div>
                    </div>

                    <Card>
                        <CardHeader className="flex flex-col items-start justify-between gap-4 md:flex-row md:items-center">
                            <div>
                                <CardTitle>Pilih Perusahaan</CardTitle>
                                <CardDescription>Pilih perusahaan untuk mengakses Administration, Assessment, atau Interview</CardDescription>
                            </div>

                            <div className="flex items-center gap-4">
                                <SearchBar
                                    icon={<Search className="h-4 w-4" />}
                                    placeholder="Cari perusahaan..."
                                    value={searchQuery}
                                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearchQuery(e.target.value)}
                                />
                            </div>
                        </CardHeader>
                        <CardContent>
                            {filteredCompanies.length > 0 ? (
                                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                                    {filteredCompanies.map((company) => (
                                        <Card key={company.id} className="relative">
                                            <CardHeader>
                                                <div className="flex items-start justify-between">
                                                    <div className="flex-1 space-y-1">
                                                        <h3 className="font-semibold text-lg">{company.name}</h3>
                                                        <Badge variant="outline">
                                                            Active
                                                        </Badge>
                                                    </div>
                                                </div>
                                                {company.description && (
                                                    <p className="text-sm text-gray-600 mt-2">{company.description}</p>
                                                )}
                                            </CardHeader>
                                            <CardContent>
                                                <div className="space-y-2 text-sm">
                                                    {company.email && (
                                                        <div className="flex items-center gap-2">
                                                            <Mail className="h-4 w-4 text-gray-400" />
                                                            <span className="text-gray-600">{company.email}</span>
                                                        </div>
                                                    )}
                                                    {company.phone && (
                                                        <div className="flex items-center gap-2">
                                                            <Phone className="h-4 w-4 text-gray-400" />
                                                            <span className="text-gray-600">{company.phone}</span>
                                                        </div>
                                                    )}
                                                    {company.address && (
                                                        <div className="flex items-center gap-2">
                                                            <MapPin className="h-4 w-4 text-gray-400" />
                                                            <span className="text-gray-600 line-clamp-2">{company.address}</span>
                                                        </div>
                                                    )}
                                                    {company.website && (
                                                        <div className="flex items-center gap-2">
                                                            <Globe className="h-4 w-4 text-gray-400" />
                                                            <a 
                                                                href={company.website} 
                                                                target="_blank" 
                                                                rel="noopener noreferrer"
                                                                className="text-blue-600 hover:underline text-sm"
                                                            >
                                                                {company.website}
                                                            </a>
                                                        </div>
                                                    )}
                                                </div>
                                                <div className="mt-4 flex gap-2 border-t pt-4">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="flex-1"
                                                        onClick={() => router.get(route('companies.dashboard', company.id), { from: 'recruitment' })}
                                                    >
                                                        <BarChart2 className="mr-2 h-4 w-4" />
                                                        Dashboard
                                                    </Button>
                                                    <Button
                                                        variant="default"
                                                        size="sm"
                                                        className="flex-1"
                                                        onClick={() => router.get(route('companies.candidates', company.id), { from: 'recruitment' })}
                                                    >
                                                        <Users className="mr-2 h-4 w-4" />
                                                        Kandidat
                                                    </Button>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}
                                </div>
                            ) : (
                                <div className="text-center py-12">
                                    <Building2 className="mx-auto h-12 w-12 text-gray-400" />
                                    <h3 className="mt-2 text-sm font-medium text-gray-900">
                                        {searchQuery ? 'No companies found' : 'No companies'}
                                    </h3>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {searchQuery
                                            ? 'Try adjusting your search terms.'
                                            : 'No companies available at the moment.'
                                        }
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}

