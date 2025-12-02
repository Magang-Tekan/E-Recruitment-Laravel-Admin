import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis, PieChart, Pie, Cell, BarChart, Bar, LineChart, Line, Legend } from 'recharts';
import { 
    Users, 
    Building2, 
    FileText, 
    ClipboardList, 
    MessageSquare,
    AlertCircle,
    Loader2,
    BarChart3,
    TrendingUp,
    Clock,
    CheckCircle2,
    ArrowRight,
    Activity
} from 'lucide-react';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

interface DashboardProps {
    dashboardStats: {
        totalCandidates: number;
        companyStats: Array<{
            id?: number;
            name: string;
            applications: number;
        }>;
        totalApplications: number;
        adminReview: number;
        assessmentStage: number;
        interviewStage: number;
        pendingActions: number;
    };
    recruitmentStageData: Array<{
        name: string;
        value: number;
        color: string;
    }>;
    weeklyData: Array<{
        day: string;
        admin: number;
        assessment: number;
        interview: number;
    }>;
    recentActivities: Array<{
        id: number;
        type: string;
        message: string;
        time: string;
        status: string;
    }>;
    topPositions: Array<{
        title: string;
        applications: number;
        subsidiary: string;
    }>;
}

export default function Dashboard({ 
    dashboardStats,
    recruitmentStageData,
    weeklyData,
    recentActivities,
    topPositions
}: DashboardProps) {
    const [isLoading, setIsLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    useEffect(() => {
        // Simulate loading state for smoother transitions
        const timer = setTimeout(() => {
            setIsLoading(false);
        }, 500);

        return () => clearTimeout(timer);
    }, []);

    useEffect(() => {
        // Check for any missing or invalid data
        const validateData = () => {
            if (!dashboardStats) {
                return 'Dashboard statistics are missing';
            }

            // Validate required properties in dashboardStats
            const requiredStats = [
                'totalCandidates',
                'companyStats',
                'totalApplications',
                'adminReview',
                'assessmentStage',
                'interviewStage',
                'pendingActions'
            ];

            const missingStats = requiredStats.filter(stat => !(stat in dashboardStats));
            if (missingStats.length > 0) {
                return `Missing required statistics: ${missingStats.join(', ')}`;
            }

            // Validate arrays exist (but allow empty arrays)
            if (!Array.isArray(recruitmentStageData) || recruitmentStageData.length === 0) {
                return 'Recruitment stage data is missing';
            }

            if (!Array.isArray(weeklyData)) {
                return 'Weekly data format is invalid';
            }

            if (!Array.isArray(recentActivities)) {
                return 'Recent activities data is missing';
            }

            if (!Array.isArray(topPositions)) {
                return 'Top positions data is missing';
            }

            return null;
        };

        const validationError = validateData();
        if (validationError) {
            setError(validationError);
        } else {
            setError(null);
        }
    }, [dashboardStats, recruitmentStageData, weeklyData, recentActivities, topPositions]);

    if (isLoading) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard Admin - PT Mitra Karya Grup" />
                <div className="flex h-full flex-1 flex-col items-center justify-center gap-6 rounded-xl p-4">
                    <Loader2 className="h-8 w-8 animate-spin text-blue-600" />
                    <p className="text-muted-foreground">Loading dashboard data...</p>
                </div>
            </AppLayout>
        );
    }

    if (error) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard Admin - PT Mitra Karya Grup" />
                <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                    <Alert variant="destructive">
                        <AlertCircle className="h-4 w-4" />
                        <AlertDescription>{error}</AlertDescription>
                    </Alert>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard Admin - PT Mitra Karya Grup" />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 bg-gradient-to-br from-slate-50 to-blue-50/30">
                {/* Welcome Section - Redesigned */}
                <div className="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 p-8 text-white shadow-xl">
                    <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDM0VjUySDQ4VjM0SDM2em0tMjQgMjRIMjRWMjJIMTJ2MzZ6Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-20"></div>
                    <div className="relative z-10">
                        <div className="flex items-center gap-3 mb-3">
                            <div className="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                                <Activity className="h-6 w-6" />
                            </div>
                            <h1 className="text-3xl font-bold tracking-tight">Dashboard Admin</h1>
                        </div>
                        <h2 className="text-xl font-semibold mb-2 text-blue-100">PT Mitra Karya Grup</h2>
                        <p className="text-blue-100/90 text-sm">Kelola sistem rekrutmen untuk semua perusahaan</p>
                    </div>
                </div>

                {/* Company Stats Grid - Redesigned */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card className="group relative overflow-hidden border-0 bg-gradient-to-br from-white to-blue-50/50 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div className="absolute top-0 right-0 w-32 h-32 bg-blue-100/50 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-blue-200/50 transition-colors"></div>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3 relative z-10">
                            <CardTitle className="text-sm font-semibold text-slate-700">Total Kandidat</CardTitle>
                            <div className="p-2 bg-blue-100 rounded-lg group-hover:bg-blue-200 transition-colors">
                                <Users className="h-5 w-5 text-blue-600" />
                            </div>
                        </CardHeader>
                        <CardContent className="relative z-10">
                            <div className="text-3xl font-bold text-slate-900 mb-1">{dashboardStats.totalCandidates.toLocaleString()}</div>
                            <p className="text-xs text-slate-500 flex items-center gap-1">
                                <TrendingUp className="h-3 w-3" />
                                Total kandidat yang mendaftar
                            </p>
                        </CardContent>
                    </Card>

                    {dashboardStats.companyStats.map((company, index) => {
                        const colorConfigs = [
                            { 
                                bg: 'from-purple-50 to-purple-100/50', 
                                bgBlur: 'bg-purple-100/50', 
                                bgBlurHover: 'group-hover:bg-purple-200/50',
                                icon: 'bg-purple-100', 
                                iconHover: 'group-hover:bg-purple-200',
                                iconColor: 'text-purple-600'
                            },
                            { 
                                bg: 'from-emerald-50 to-emerald-100/50', 
                                bgBlur: 'bg-emerald-100/50',
                                bgBlurHover: 'group-hover:bg-emerald-200/50',
                                icon: 'bg-emerald-100', 
                                iconHover: 'group-hover:bg-emerald-200',
                                iconColor: 'text-emerald-600'
                            },
                            { 
                                bg: 'from-amber-50 to-amber-100/50', 
                                bgBlur: 'bg-amber-100/50',
                                bgBlurHover: 'group-hover:bg-amber-200/50',
                                icon: 'bg-amber-100', 
                                iconHover: 'group-hover:bg-amber-200',
                                iconColor: 'text-amber-600'
                            },
                        ];
                        const color = colorConfigs[index % colorConfigs.length] || colorConfigs[0];
                        
                        return (
                            <Card key={index} className={`group relative overflow-hidden border-0 bg-gradient-to-br ${color.bg} shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1`}>
                                <div className={`absolute top-0 right-0 w-32 h-32 ${color.bgBlur} rounded-full -mr-16 -mt-16 blur-2xl ${color.bgBlurHover} transition-colors`}></div>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3 relative z-10">
                                    <CardTitle className="text-sm font-semibold text-slate-700 line-clamp-1 pr-2">{company.name}</CardTitle>
                                    <div className={`p-2 ${color.icon} rounded-lg ${color.iconHover} transition-colors`}>
                                        <Building2 className={`h-5 w-5 ${color.iconColor}`} />
                                    </div>
                                </CardHeader>
                                <CardContent className="relative z-10">
                                    <div className="text-3xl font-bold text-slate-900 mb-1">{company.applications}</div>
                                    <p className="text-xs text-slate-500 flex items-center gap-1">
                                        <Activity className="h-3 w-3" />
                                        Kandidat aktif
                                    </p>
                                </CardContent>
                            </Card>
                        );
                    })}

                    <Card className="group relative overflow-hidden border-0 bg-gradient-to-br from-white to-indigo-50/50 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1">
                        <div className="absolute top-0 right-0 w-32 h-32 bg-indigo-100/50 rounded-full -mr-16 -mt-16 blur-2xl group-hover:bg-indigo-200/50 transition-colors"></div>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3 relative z-10">
                            <CardTitle className="text-sm font-semibold text-slate-700">Total Aplikasi</CardTitle>
                            <div className="p-2 bg-indigo-100 rounded-lg group-hover:bg-indigo-200 transition-colors">
                                <FileText className="h-5 w-5 text-indigo-600" />
                            </div>
                        </CardHeader>
                        <CardContent className="relative z-10">
                            <div className="text-3xl font-bold text-slate-900 mb-1">{dashboardStats.totalApplications.toLocaleString()}</div>
                            <p className="text-xs text-slate-500 flex items-center gap-1">
                                <TrendingUp className="h-3 w-3" />
                                Total aplikasi masuk
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* 3-Step Process Stats - Redesigned */}
                <div className="grid gap-4 md:grid-cols-3">
                    <Card className="group relative overflow-hidden border-0 bg-gradient-to-br from-blue-50 to-blue-100/30 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border-l-4 border-l-blue-500">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                            <div className="flex items-center gap-3">
                                <div className="p-2.5 bg-blue-500 rounded-lg text-white">
                                    <ClipboardList className="h-5 w-5" />
                                </div>
                                <div>
                                    <CardTitle className="text-sm font-semibold text-slate-700">Step 1: Administration</CardTitle>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-slate-900 mb-1">{dashboardStats.adminReview}</div>
                            <p className="text-xs text-slate-500 flex items-center gap-1">
                                <Clock className="h-3 w-3" />
                                Menunggu review admin
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="group relative overflow-hidden border-0 bg-gradient-to-br from-emerald-50 to-emerald-100/30 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border-l-4 border-l-emerald-500">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                            <div className="flex items-center gap-3">
                                <div className="p-2.5 bg-emerald-500 rounded-lg text-white">
                                    <FileText className="h-5 w-5" />
                                </div>
                                <div>
                                    <CardTitle className="text-sm font-semibold text-slate-700">Step 2: Assessment</CardTitle>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-slate-900 mb-1">{dashboardStats.assessmentStage}</div>
                            <p className="text-xs text-slate-500 flex items-center gap-1">
                                <Activity className="h-3 w-3" />
                                Dalam tahap tes
                            </p>
                        </CardContent>
                    </Card>

                    <Card className="group relative overflow-hidden border-0 bg-gradient-to-br from-amber-50 to-amber-100/30 shadow-md hover:shadow-xl transition-all duration-300 hover:-translate-y-1 border-l-4 border-l-amber-500">
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-3">
                            <div className="flex items-center gap-3">
                                <div className="p-2.5 bg-amber-500 rounded-lg text-white">
                                    <MessageSquare className="h-5 w-5" />
                                </div>
                                <div>
                                    <CardTitle className="text-sm font-semibold text-slate-700">Step 3: Interview</CardTitle>
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="text-3xl font-bold text-slate-900 mb-1">{dashboardStats.interviewStage}</div>
                            <p className="text-xs text-slate-500 flex items-center gap-1">
                                <CheckCircle2 className="h-3 w-3" />
                                Tahap wawancara
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Charts Section - Redesigned */}
                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    {/* Weekly Process Activity Chart */}
                    <Card className="lg:col-span-2 border-0 shadow-lg bg-white/80 backdrop-blur-sm">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle className="text-slate-800 text-lg font-semibold">Aktivitas 7 Hari Terakhir</CardTitle>
                                    <CardDescription className="mt-1">Progres kandidat melalui 3 tahap rekrutmen</CardDescription>
                                </div>
                                <div className="p-2 bg-blue-100 rounded-lg">
                                    <BarChart3 className="h-5 w-5 text-blue-600" />
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="h-[300px]">
                                {weeklyData.length > 0 ? (
                                    <ResponsiveContainer width="100%" height="100%">
                                        <LineChart 
                                            data={weeklyData.slice(-7)} 
                                            margin={{ top: 20, right: 20, left: 0, bottom: 10 }}
                                        >
                                            <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                            <XAxis 
                                                dataKey="day" 
                                                tick={{ fill: '#64748b', fontSize: 11, fontWeight: 500 }}
                                                tickLine={{ stroke: '#cbd5e1' }}
                                                axisLine={{ stroke: '#e2e8f0' }}
                                            />
                                            <YAxis 
                                                tick={{ fill: '#64748b', fontSize: 11, fontWeight: 500 }}
                                                tickLine={{ stroke: '#cbd5e1' }}
                                                axisLine={{ stroke: '#e2e8f0' }}
                                                tickFormatter={(value) => Math.round(value).toString()}
                                            />
                                            <Tooltip 
                                                contentStyle={{ 
                                                    backgroundColor: '#fff',
                                                    border: '1px solid #e2e8f0',
                                                    borderRadius: '8px',
                                                    padding: '12px',
                                                    fontSize: '12px',
                                                    boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)'
                                                }}
                                                formatter={(value, name) => [value, name === 'admin' ? 'Administration' : name === 'assessment' ? 'Assessment' : 'Interview']}
                                                labelFormatter={(label) => `Tanggal: ${label}`}
                                            />
                                            <Legend 
                                                verticalAlign="top"
                                                height={40}
                                                iconType="line"
                                                wrapperStyle={{ paddingTop: '10px' }}
                                                formatter={(value) => value === 'admin' ? 'Administration' : value === 'assessment' ? 'Assessment' : 'Interview'}
                                            />
                                            <Line 
                                                type="monotone" 
                                                dataKey="admin" 
                                                name="Administration" 
                                                stroke="#3B82F6" 
                                                strokeWidth={3}
                                                dot={{ fill: '#3B82F6', r: 4, strokeWidth: 2, stroke: '#fff' }}
                                                activeDot={{ r: 7, stroke: '#3B82F6', strokeWidth: 2, fill: '#fff' }}
                                            />
                                            <Line 
                                                type="monotone" 
                                                dataKey="assessment" 
                                                name="Assessment" 
                                                stroke="#10B981" 
                                                strokeWidth={3}
                                                dot={{ fill: '#10B981', r: 4, strokeWidth: 2, stroke: '#fff' }}
                                                activeDot={{ r: 7, stroke: '#10B981', strokeWidth: 2, fill: '#fff' }}
                                            />
                                            <Line 
                                                type="monotone" 
                                                dataKey="interview" 
                                                name="Interview" 
                                                stroke="#F59E0B" 
                                                strokeWidth={3}
                                                dot={{ fill: '#F59E0B', r: 4, strokeWidth: 2, stroke: '#fff' }}
                                                activeDot={{ r: 7, stroke: '#F59E0B', strokeWidth: 2, fill: '#fff' }}
                                            />
                                        </LineChart>
                                    </ResponsiveContainer>
                                ) : (
                                    <div className="flex items-center justify-center h-full">
                                        <div className="text-center text-slate-400">
                                            <BarChart3 className="h-12 w-12 mx-auto mb-3 opacity-30" />
                                            <p className="text-sm font-medium">Belum ada data aktivitas mingguan</p>
                                            <p className="text-xs mt-1 text-slate-400">Data akan muncul setelah ada aktivitas rekrutmen</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Stage Distribution - Redesigned */}
                    <Card className="border-0 shadow-lg bg-white/80 backdrop-blur-sm">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle className="text-slate-800 text-lg font-semibold">Distribusi Tahap</CardTitle>
                                    <CardDescription className="mt-1">Persentase kandidat per tahap</CardDescription>
                                </div>
                                <div className="p-2 bg-purple-100 rounded-lg">
                                    <Activity className="h-5 w-5 text-purple-600" />
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="h-[300px] flex items-center">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={recruitmentStageData}
                                            cx="50%"
                                            cy="50%"
                                            outerRadius={90}
                                            innerRadius={65}
                                            dataKey="value"
                                            label={({ name, percent }) => `${name} ${(percent * 100).toFixed(0)}%`}
                                            labelLine={false}
                                        >
                                            {recruitmentStageData.map((entry, index) => (
                                                <Cell 
                                                    key={`cell-${index}`} 
                                                    fill={entry.color}
                                                    stroke="#fff"
                                                    strokeWidth={3}
                                                />
                                            ))}
                                        </Pie>
                                        <Tooltip 
                                            contentStyle={{ 
                                                backgroundColor: '#fff',
                                                border: '1px solid #e2e8f0',
                                                borderRadius: '8px',
                                                fontSize: '12px',
                                                boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)'
                                            }}
                                        />
                                    </PieChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Company Applications - Redesigned */}
                    <Card className="lg:col-span-2 border-0 shadow-lg bg-white/80 backdrop-blur-sm">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle className="text-slate-800 text-lg font-semibold">Aplikasi per Perusahaan</CardTitle>
                                    <CardDescription className="mt-1">Jumlah aplikasi masuk tiap perusahaan</CardDescription>
                                </div>
                                <div className="p-2 bg-indigo-100 rounded-lg">
                                    <Building2 className="h-5 w-5 text-indigo-600" />
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="h-[300px]">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart 
                                        data={dashboardStats.companyStats}
                                        layout="vertical"
                                        margin={{ top: 10, right: 20, left: 120, bottom: 10 }}
                                    >
                                        <CartesianGrid strokeDasharray="3 3" horizontal={true} vertical={false} stroke="#e2e8f0" />
                                        <XAxis 
                                            type="number" 
                                            tick={{ fill: '#64748b', fontSize: 11, fontWeight: 500 }}
                                            tickLine={{ stroke: '#cbd5e1' }}
                                            axisLine={{ stroke: '#e2e8f0' }}
                                        />
                                        <YAxis 
                                            dataKey="name" 
                                            type="category" 
                                            tick={{ fill: '#64748b', fontSize: 11, fontWeight: 500 }}
                                            tickLine={{ stroke: '#cbd5e1' }}
                                            axisLine={{ stroke: '#e2e8f0' }}
                                            width={120}
                                        />
                                        <Tooltip
                                            contentStyle={{ 
                                                backgroundColor: '#fff',
                                                border: '1px solid #e2e8f0',
                                                borderRadius: '8px',
                                                padding: '12px',
                                                fontSize: '12px',
                                                boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)'
                                            }}
                                            formatter={(value) => [`${value} aplikasi`, 'Jumlah']}
                                        />
                                        <Bar 
                                            dataKey="applications" 
                                            radius={[0, 6, 6, 0]}
                                        >
                                            {dashboardStats.companyStats.map((entry, index) => (
                                                <Cell 
                                                    key={`cell-${index}`}
                                                    fill={`hsl(${220 + index * 30}, 70%, 55%)`}
                                                />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Top Positions Chart - Redesigned */}
                    <Card className="border-0 shadow-lg bg-white/80 backdrop-blur-sm">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle className="text-slate-800 text-lg font-semibold">Top Posisi</CardTitle>
                                    <CardDescription className="mt-1">5 posisi terpopuler</CardDescription>
                                </div>
                                <div className="p-2 bg-amber-100 rounded-lg">
                                    <TrendingUp className="h-5 w-5 text-amber-600" />
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="h-[300px]">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart
                                        data={topPositions.slice(0, 5)}
                                        layout="vertical"
                                        margin={{ top: 10, right: 20, left: 120, bottom: 10 }}
                                    >
                                        <CartesianGrid strokeDasharray="3 3" horizontal={true} vertical={false} stroke="#e2e8f0" />
                                        <XAxis 
                                            type="number" 
                                            tick={{ fill: '#64748b', fontSize: 11, fontWeight: 500 }}
                                            tickLine={{ stroke: '#cbd5e1' }}
                                            axisLine={{ stroke: '#e2e8f0' }}
                                        />
                                        <YAxis
                                            dataKey="title"
                                            type="category"
                                            tick={{ fill: '#64748b', fontSize: 11, fontWeight: 500 }}
                                            tickLine={{ stroke: '#cbd5e1' }}
                                            axisLine={{ stroke: '#e2e8f0' }}
                                            width={120}
                                        />
                                        <Tooltip
                                            contentStyle={{
                                                backgroundColor: '#fff',
                                                border: '1px solid #e2e8f0',
                                                borderRadius: '8px',
                                                padding: '12px',
                                                fontSize: '12px',
                                                boxShadow: '0 4px 6px -1px rgb(0 0 0 / 0.1)'
                                            }}
                                            formatter={(value) => [`${value} aplikasi`, 'Jumlah']}
                                        />
                                        <Bar
                                            dataKey="applications"
                                            radius={[0, 6, 6, 0]}
                                        >
                                            {topPositions.slice(0, 5).map((_, index) => (
                                                <Cell
                                                    key={`cell-${index}`}
                                                    fill={`hsl(${200 + index * 20}, 70%, 55%)`}
                                                />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Activities - Redesigned */}
                <div className="grid gap-6 md:grid-cols-2">
                    {/* Recent Activities List */}
                    <Card className="border-0 shadow-lg bg-white/80 backdrop-blur-sm">
                        <CardHeader className="pb-3 border-b border-slate-100">
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle className="text-slate-800 text-lg font-semibold">Aktivitas Terbaru</CardTitle>
                                    <CardDescription className="mt-1">5 aktivitas terakhir dalam sistem</CardDescription>
                                </div>
                                <div className="p-2 bg-slate-100 rounded-lg">
                                    <Clock className="h-5 w-5 text-slate-600" />
                                </div>
                            </div>
                        </CardHeader>
                        <CardContent className="pt-6">
                            <div className="space-y-4">
                                {recentActivities.length > 0 ? (
                                    recentActivities.map((activity) => (
                                        <div 
                                            key={activity.id} 
                                            className="group flex items-start gap-4 p-3 rounded-lg hover:bg-slate-50 transition-colors border border-transparent hover:border-slate-200"
                                        >
                                            <div className={`p-2 rounded-lg ${
                                                activity.type === 'admin' ? 'bg-blue-100' :
                                                activity.type === 'assessment' ? 'bg-emerald-100' :
                                                'bg-amber-100'
                                            }`}>
                                                {activity.type === 'admin' && <ClipboardList className={`h-4 w-4 ${
                                                    activity.type === 'admin' ? 'text-blue-600' :
                                                    activity.type === 'assessment' ? 'text-emerald-600' :
                                                    'text-amber-600'
                                                }`} />}
                                                {activity.type === 'assessment' && <FileText className="h-4 w-4 text-emerald-600" />}
                                                {activity.type === 'interview' && <MessageSquare className="h-4 w-4 text-amber-600" />}
                                            </div>
                                            <div className="flex-1 min-w-0">
                                                <p className="text-sm font-medium text-slate-800 leading-snug">{activity.message}</p>
                                                <p className="text-xs text-slate-500 mt-1 flex items-center gap-1">
                                                    <Clock className="h-3 w-3" />
                                                    {activity.time}
                                                </p>
                                            </div>
                                            <div className={`px-2.5 py-1 rounded-full text-xs font-medium ${
                                                activity.status === 'completed' ? 'bg-green-100 text-green-700' :
                                                activity.status === 'scheduled' ? 'bg-blue-100 text-blue-700' :
                                                activity.status === 'new' ? 'bg-amber-100 text-amber-700' :
                                                'bg-slate-100 text-slate-700'
                                            }`}>
                                                {activity.status}
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center py-8 text-slate-400">
                                        <Activity className="h-10 w-10 mx-auto mb-2 opacity-30" />
                                        <p className="text-sm">Belum ada aktivitas terbaru</p>
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}