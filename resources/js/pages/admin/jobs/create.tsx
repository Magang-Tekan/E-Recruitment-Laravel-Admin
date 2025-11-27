import { useState } from "react";
import { Head, useForm } from "@inertiajs/react";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { Button } from "@/components/ui/button";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { Badge } from "@/components/ui/badge";
import { router } from "@inertiajs/react";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { ChevronDown, ChevronUp, X } from "lucide-react";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

interface CreateJobPageProps {
    companies: { id: number; name: string }[];
    departments: { id: number; name: string }[];
    majors: { id: number; name: string }[];
    questionPacks: { id: number; pack_name: string }[];
    educationLevels: { id: number; name: string }[];
    vacancyTypes: { id: number; name: string }[];
    openPeriods: { id: number; name: string; start_time: string; end_time: string }[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Job Management',
        href: '/dashboard/jobs',
    },
    {
        title: 'Create Job',
        href: '#',
    },
];

export default function CreateJob({ companies, departments, majors, questionPacks, educationLevels, vacancyTypes, openPeriods }: CreateJobPageProps) {
    const [requirements, setRequirements] = useState<string>("");
    const [benefits, setBenefits] = useState<string>("");
    const [majorSearchQuery, setMajorSearchQuery] = useState<string>("");
    const [isMajorDropdownOpen, setIsMajorDropdownOpen] = useState<boolean>(false);
    const [step, setStep] = useState<1 | 2 | 3 | 4>(1);

    const [prefilledCompanyId] = useState(() => {
        if (typeof window === "undefined") return "";
        const params = new URLSearchParams(window.location.search);
        return params.get("company") ?? "";
    });

    const { data, setData, post, processing, errors } = useForm({
        title: "",
        department_id: "",
        major_id: "", // Legacy support
        major_ids: [] as number[],
        location: "",
        salary: "",
        company_id: prefilledCompanyId,
        vacancy_type_id: "",
        job_description: "",
        question_pack_id: "",
        education_level_id: "",
        period_name: "",
        period_start_time: "",
        period_end_time: "",
        psychotest_name: "Tes Psikologi",
        requirements: [] as string[],
        benefits: [] as string[],
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();

        // Hanya submit di step terakhir
        if (step < 4) {
            setStep((prev) => (prev < 4 ? (prev + 1) as 1 | 2 | 3 | 4 : prev));
            return;
        }
        
        // Convert requirements and benefits from string to array
        const requirementsArray = requirements.split('\n').filter(req => req.trim() !== '');
        const benefitsArray = benefits.split('\n').filter(ben => ben.trim() !== '');
        
        if (requirementsArray.length === 0) {
            alert('Please add at least satu requirement');
            return;
        }
        
        // Create the submission data
        const submitData: any = {
            title: data.title,
            department_id: data.department_id,
            major_ids: data.major_ids.length > 0 ? data.major_ids : [],
            location: data.location,
            salary: data.salary.trim() || null,
            company_id: data.company_id,
            vacancy_type_id: data.vacancy_type_id,
            job_description: data.job_description,
            question_pack_id: data.question_pack_id,
            education_level_id: data.education_level_id,
            period_name: data.period_name,
            period_start_time: data.period_start_time,
            period_end_time: data.period_end_time,
            psychotest_name: data.psychotest_name,
            requirements: requirementsArray,
            benefits: benefitsArray,
        };
        
        // Submit using router.post
        router.post(route('admin.jobs.store'), submitData, {
            onSuccess: () => {
                router.visit(route('admin.jobs.index'));
            },
            onError: () => {
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Job Vacancy" />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">Create Job Vacancy</h2>
                    <Button
                        variant="outline"
                        onClick={() => router.visit(route('admin.jobs.index'))}
                    >
                        Cancel
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Create New Job Vacancy</CardTitle>
                        <CardDescription>Isi data lowongan melalui beberapa tahap.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Step indicator */}
                            <div className="flex items-center justify-between text-xs font-medium text-gray-600">
                                <div className={`flex-1 border-b-2 pb-2 text-center ${step === 1 ? "border-blue-500 text-blue-600" : "border-gray-200"}`}>
                                    1. General
                                </div>
                                <div className={`flex-1 border-b-2 pb-2 text-center ${step === 2 ? "border-blue-500 text-blue-600" : "border-gray-200"}`}>
                                    2. Education
                                </div>
                                <div className={`flex-1 border-b-2 pb-2 text-center ${step === 3 ? "border-blue-500 text-blue-600" : "border-gray-200"}`}>
                                    3. Test
                                </div>
                                <div className={`flex-1 border-b-2 pb-2 text-center ${step === 4 ? "border-blue-500 text-blue-600" : "border-gray-200"}`}>
                                    4. Other
                                </div>
                            </div>

                            {/* Step 1 - General */}
                            {step === 1 && (
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div className="space-y-4">
                                        <div>
                                            <Label htmlFor="title">Job Title</Label>
                                            <Input
                                                id="title"
                                                value={data.title}
                                                onChange={e => setData('title', e.target.value)}
                                                placeholder="Enter job title"
                                            />
                                            {errors.title && <p className="text-red-500 text-sm">{errors.title}</p>}
                                        </div>

                                        <div>
                                            <Label htmlFor="department">Department</Label>
                                            <Select value={data.department_id} onValueChange={(value) => setData('department_id', value)}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select department" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {departments.map((department) => (
                                                        <SelectItem key={department.id} value={department.id.toString()}>
                                                            {department.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.department_id && <p className="text-red-500 text-sm">{errors.department_id}</p>}
                                        </div>

                                        <div>
                                            <Label htmlFor="vacancy_type">Vacancy Type</Label>
                                            <Select value={data.vacancy_type_id} onValueChange={(value) => setData('vacancy_type_id', value)}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select vacancy type" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {vacancyTypes.map((type) => (
                                                        <SelectItem key={type.id} value={type.id.toString()}>
                                                            {type.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.vacancy_type_id && <p className="text-red-500 text-sm">{errors.vacancy_type_id}</p>}
                                        </div>

                                        <div>
                                            <Label htmlFor="location">Location</Label>
                                            <Input
                                                id="location"
                                                value={data.location}
                                                onChange={e => setData('location', e.target.value)}
                                                placeholder="Enter job location"
                                            />
                                            {errors.location && <p className="text-red-500 text-sm">{errors.location}</p>}
                                        </div>

                                        <div>
                                            <Label htmlFor="salary">Salary Range (Optional)</Label>
                                            <Input
                                                id="salary"
                                                value={data.salary}
                                                onChange={e => setData('salary', e.target.value)}
                                                placeholder="Enter salary range (leave empty if not specified)"
                                            />
                                            {errors.salary && <p className="text-red-500 text-sm">{errors.salary}</p>}
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <div>
                                            <Label htmlFor="company">Company</Label>
                                            <Select value={data.company_id} onValueChange={(value) => setData('company_id', value)}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select company" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {companies.map((company) => (
                                                        <SelectItem key={company.id} value={company.id.toString()}>
                                                            {company.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.company_id && <p className="text-red-500 text-sm">{errors.company_id}</p>}
                                        </div>

                                        <div>
                                            <Label>Period Name</Label>
                                            <Input
                                                value={data.period_name}
                                                onChange={e => setData('period_name', e.target.value)}
                                                placeholder="e.g. Recruitment Q1 2026"
                                            />
                                            {errors.period_name && <p className="text-red-500 text-sm">{errors.period_name}</p>}
                                        </div>

                                        <div>
                                            <Label>Period Start Time</Label>
                                            <Input
                                                type="datetime-local"
                                                value={data.period_start_time}
                                                onChange={e => setData('period_start_time', e.target.value)}
                                            />
                                            {errors.period_start_time && <p className="text-red-500 text-sm">{errors.period_start_time}</p>}
                                        </div>

                                        <div>
                                            <Label>Period End Time</Label>
                                            <Input
                                                type="datetime-local"
                                                value={data.period_end_time}
                                                onChange={e => setData('period_end_time', e.target.value)}
                                            />
                                            {errors.period_end_time && <p className="text-red-500 text-sm">{errors.period_end_time}</p>}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Step 2 - Education */}
                            {step === 2 && (
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div className="space-y-4">
                                        <div>
                                            <Label htmlFor="major">Major (Bisa pilih lebih dari satu)</Label>
                                            <div className="space-y-2">
                                                {data.major_ids.length > 0 && (
                                                    <div className="flex flex-wrap gap-2 rounded-md border p-2 min-h-[42px]">
                                                        {data.major_ids.map((majorId: number) => {
                                                            const major = majors.find(m => m.id === majorId);
                                                            if (!major) return null;
                                                            return (
                                                                <Badge
                                                                    key={majorId}
                                                                    variant="secondary"
                                                                    className="flex items-center gap-1 pr-1"
                                                                >
                                                                    <span>{major.name}</span>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => {
                                                                            setData('major_ids', data.major_ids.filter((id: number) => id !== majorId));
                                                                        }}
                                                                        className="ml-1 rounded-full p-0.5 hover:bg-gray-300"
                                                                    >
                                                                        <X className="h-3 w-3" />
                                                                    </button>
                                                                </Badge>
                                                            );
                                                        })}
                                                    </div>
                                                )}

                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    onClick={() => setIsMajorDropdownOpen(!isMajorDropdownOpen)}
                                                    className="w-full justify-between"
                                                >
                                                    <span>{isMajorDropdownOpen ? 'Sembunyikan' : 'Tampilkan'} Daftar Major</span>
                                                    {isMajorDropdownOpen ? (
                                                        <ChevronUp className="h-4 w-4" />
                                                    ) : (
                                                        <ChevronDown className="h-4 w-4" />
                                                    )}
                                                </Button>

                                                {isMajorDropdownOpen && (
                                                    <div className="space-y-2">
                                                        <Input
                                                            type="text"
                                                            placeholder="Cari major..."
                                                            value={majorSearchQuery}
                                                            onChange={(e) => setMajorSearchQuery(e.target.value)}
                                                            className="w-full"
                                                        />
                                                        <div className="min-h-[42px] max-h-[200px] overflow-y-auto rounded-md border p-3">
                                                            {(() => {
                                                                const filteredMajors = majorSearchQuery.trim()
                                                                    ? majors.filter(major =>
                                                                        major.name.toLowerCase().includes(majorSearchQuery.toLowerCase())
                                                                    )
                                                                    : majors;

                                                                if (filteredMajors.length === 0) {
                                                                    return (
                                                                        <p className="text-sm text-gray-500">
                                                                            {majorSearchQuery.trim() ? 'No majors found' : 'No majors available'}
                                                                        </p>
                                                                    );
                                                                }

                                                                return (
                                                                    <div className="space-y-2">
                                                                        {filteredMajors.map((major) => {
                                                                            const isSelected = data.major_ids.includes(major.id);
                                                                            return (
                                                                                <label
                                                                                    key={major.id}
                                                                                    className="flex cursor-pointer items-center space-x-2 rounded p-2 hover:bg-gray-50"
                                                                                >
                                                                                    <input
                                                                                        type="checkbox"
                                                                                        checked={isSelected}
                                                                                        onChange={(e) => {
                                                                                            if (e.target.checked) {
                                                                                                setData('major_ids', [...data.major_ids, major.id]);
                                                                                            } else {
                                                                                                setData('major_ids', data.major_ids.filter((id: number) => id !== major.id));
                                                                                            }
                                                                                        }}
                                                                                        className="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                                                                    />
                                                                                    <span className="text-sm">{major.name}</span>
                                                                                </label>
                                                                            );
                                                                        })}
                                                                    </div>
                                                                );
                                                            })()}
                                                        </div>
                                                    </div>
                                                )}
                                            </div>
                                            {errors.major_ids && <p className="text-red-500 text-sm">{errors.major_ids}</p>}
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <div>
                                            <Label htmlFor="education_level">Education Level</Label>
                                            <Select value={data.education_level_id} onValueChange={(value) => setData('education_level_id', value)}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select education level" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {educationLevels.map((level) => (
                                                        <SelectItem key={level.id} value={level.id.toString()}>
                                                            {level.name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.education_level_id && <p className="text-red-500 text-sm">{errors.education_level_id}</p>}
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Step 3 - Test */}
                            {step === 3 && (
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div className="space-y-4">
                                        <div>
                                            <Label htmlFor="question_pack">Question Pack</Label>
                                            <Select value={data.question_pack_id} onValueChange={(value) => setData('question_pack_id', value)}>
                                                <SelectTrigger>
                                                    <SelectValue placeholder="Select question pack" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    {questionPacks.map((pack) => (
                                                        <SelectItem key={pack.id} value={pack.id.toString()}>
                                                            {pack.pack_name}
                                                        </SelectItem>
                                                    ))}
                                                </SelectContent>
                                            </Select>
                                            {errors.question_pack_id && <p className="text-red-500 text-sm">{errors.question_pack_id}</p>}
                                        </div>
                                    </div>

                                    <div className="space-y-4">
                                        <div>
                                            <Label htmlFor="psychotest_name">Test Name</Label>
                                            <Input
                                                id="psychotest_name"
                                                value={data.psychotest_name}
                                                onChange={e => setData('psychotest_name', e.target.value)}
                                                placeholder="Contoh: Tes Kepribadian, Tes IQ"
                                            />
                                            {errors.psychotest_name && <p className="text-red-500 text-sm">{errors.psychotest_name}</p>}
                                            <p className="mt-1 text-xs text-gray-500">
                                                Nama ini akan terlihat oleh kandidat saat mengerjakan tes.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Step 4 - Other */}
                            {step === 4 && (
                                <div className="space-y-4">
                                    <div>
                                        <Label htmlFor="description">Description</Label>
                                        <Textarea
                                            id="description"
                                            value={data.job_description}
                                            onChange={e => setData('job_description', e.target.value)}
                                            placeholder="Enter job description"
                                            className="min-h-[100px]"
                                        />
                                        {errors.job_description && <p className="text-red-500 text-sm">{errors.job_description}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="requirements">Requirements (one per line)</Label>
                                        <Textarea
                                            id="requirements"
                                            value={requirements}
                                            onChange={e => setRequirements(e.target.value)}
                                            placeholder="Enter job requirements (one per line)"
                                            className="min-h-[100px]"
                                        />
                                        {errors.requirements && <p className="text-red-500 text-sm">{errors.requirements}</p>}
                                    </div>

                                    <div>
                                        <Label htmlFor="benefits">Benefits (one per line, optional)</Label>
                                        <Textarea
                                            id="benefits"
                                            value={benefits}
                                            onChange={e => setBenefits(e.target.value)}
                                            placeholder="Enter job benefits (one per line)"
                                            className="min-h-[100px]"
                                        />
                                        {errors.benefits && <p className="text-red-500 text-sm">{errors.benefits}</p>}
                                    </div>
                                </div>
                            )}

                            <div className="flex items-center justify-between pt-4">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.visit(route('admin.jobs.index'))}
                                >
                                    Cancel
                                </Button>
                                <div className="flex gap-2">
                                    {step > 1 && (
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setStep((prev) => (prev > 1 ? (prev - 1) as 1 | 2 | 3 | 4 : prev))}
                                        >
                                            Back
                                        </Button>
                                    )}
                                    {step < 4 && (
                                        <Button
                                            type="button"
                                            onClick={() => setStep((prev) => (prev < 4 ? (prev + 1) as 1 | 2 | 3 | 4 : prev))}
                                        >
                                            Next
                                        </Button>
                                    )}
                                    {step === 4 && (
                                        <Button type="submit" disabled={processing}>
                                            Save Job
                                        </Button>
                                    )}
                                </div>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
} 