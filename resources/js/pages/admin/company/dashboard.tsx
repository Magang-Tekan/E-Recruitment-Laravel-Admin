import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { BarChart2, Briefcase, Users, Clock, ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Company {
  id: number;
  name: string;
  description?: string | null;
}

interface Stats {
  totalVacancies: number;
  totalApplicants: number;
  openPeriods: number;
}

interface Vacancy {
  id: number;
  title: string;
  department?: string;
  created_at?: string | null;
}

interface Props {
  company: Company;
  stats: Stats;
  vacancies: Vacancy[];
}

export default function CompanyDashboard({ company, stats, vacancies }: Props) {
  const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Company Management', href: '/dashboard/company-management' },
    { title: company.name, href: `/dashboard/companies/${company.id}/dashboard` },
  ];

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title={`Company Dashboard - ${company.name}`} />
      <div className="flex flex-col gap-6 p-6">
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-semibold">{company.name}</h1>
            <p className="mt-1 text-sm text-gray-500">
              Ringkasan aktivitas rekrutmen dan lowongan untuk perusahaan ini.
            </p>
          </div>
          <Button
            variant="outline"
            className="gap-2"
            onClick={() => router.visit('/dashboard/company-management')}
          >
            <ArrowLeft className="h-4 w-4" />
            Back to Company Management
          </Button>
        </div>

        {company.description && (
          <p className="text-sm text-gray-600">{company.description}</p>
        )}

        {/* Stats */}
        <div className="grid gap-4 md:grid-cols-3">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Vacancies</CardTitle>
              <Briefcase className="h-4 w-4 text-blue-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.totalVacancies}</div>
              <p className="text-xs text-gray-500">Semua lowongan untuk perusahaan ini</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Applicants</CardTitle>
              <Users className="h-4 w-4 text-green-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.totalApplicants}</div>
              <p className="text-xs text-gray-500">Semua kandidat yang melamar ke lowongan perusahaan ini</p>
            </CardContent>
          </Card>

          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Open Periods</CardTitle>
              <Clock className="h-4 w-4 text-orange-500" />
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{stats.openPeriods}</div>
              <p className="text-xs text-gray-500">Periode rekrutmen yang sedang berjalan</p>
            </CardContent>
          </Card>
        </div>

        {/* Vacancies list */}
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0">
            <CardTitle className="flex items-center gap-2 text-base">
              <BarChart2 className="h-4 w-4 text-blue-500" />
              Job Vacancies
            </CardTitle>
            <Button
              type="button"
              className="bg-blue-500 text-white hover:bg-blue-600"
              onClick={() => router.visit('/dashboard/jobs/create')}
            >
              + Add Job
            </Button>
          </CardHeader>
          <CardContent>
            {vacancies.length === 0 ? (
              <p className="py-4 text-sm text-gray-500">
                Belum ada lowongan untuk perusahaan ini.
              </p>
            ) : (
              <div className="space-y-2">
                {vacancies.map((vacancy) => (
                  <div
                    key={vacancy.id}
                    className="flex items-center justify-between rounded-md border px-4 py-3 text-sm"
                  >
                    <div>
                      <p className="font-medium">{vacancy.title}</p>
                      <p className="text-xs text-gray-500">
                        {vacancy.department || 'Unknown department'}
                        {vacancy.created_at ? ` • Dibuat ${vacancy.created_at}` : ''}
                      </p>
                    </div>
                    <Button
                      variant="outline"
                      size="sm"
                      onClick={() =>
                        router.visit(
                          `/dashboard/recruitment/administration?company=${company.id}&vacancy=${vacancy.id}`,
                        )
                      }
                    >
                      View Candidates
                    </Button>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}


