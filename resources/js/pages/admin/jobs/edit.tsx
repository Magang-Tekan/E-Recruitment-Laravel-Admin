import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { ChevronDown, ChevronUp, X } from 'lucide-react';

interface JobProps {
    job: {
        id: number;
        title: string;
        department_id: number;
        major_id?: number;
        majors?: { id: number; name: string }[];
        location: string;
        salary?: string;
        company_id: number;
        requirements: string[] | string;
        benefits?: string[] | string;
        question_pack_id?: number;
        education_level_id?: number;
        vacancy_type_id: number;
        job_description?: string;
        psychotest_name?: string;
        period_id?: number;
    };
    companies: { id: number; name: string }[];
    departments: { id: number; name: string }[];
    majors: { id: number; name: string }[];
    questionPacks: { id: number; pack_name: string }[];
    educationLevels: { id: number; name: string }[];
    vacancyTypes: { id: number; name: string }[];
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
        title: 'Edit Job',
        href: '#',
    },
];

export default function EditJob({ job, companies, departments, majors, questionPacks, educationLevels, vacancyTypes }: JobProps) {
    const [isLoading, setIsLoading] = useState(false);
    const [majorSearchQuery, setMajorSearchQuery] = useState<string>("");
    const [isMajorDropdownOpen, setIsMajorDropdownOpen] = useState<boolean>(false);
    const [selectedMajorIds, setSelectedMajorIds] = useState<number[]>(
        job.majors && job.majors.length > 0 
            ? job.majors.map(m => m.id) 
            : (job.major_id ? [job.major_id] : [])
    );
    const [formData, setFormData] = useState({
        title: job.title,
        department_id: String(job.department_id),
        major_id: job.major_id ? String(job.major_id) : 'none',
        location: job.location,
        salary: job.salary || '',
        company_id: String(job.company_id),
        requirements: Array.isArray(job.requirements) ? job.requirements.join('\n') : job.requirements || '',
        benefits: Array.isArray(job.benefits) ? job.benefits.join('\n') : job.benefits || '',
        question_pack_id: job.question_pack_id ? String(job.question_pack_id) : 'none',
        education_level_id: job.education_level_id ? String(job.education_level_id) : 'none',
        vacancy_type_id: String(job.vacancy_type_id),
        job_description: job.job_description || '',
        psychotest_name: job.psychotest_name || 'Tes Psikologi',
        period_id: job.period_id ? String(job.period_id) : '1', // Default to period 1
    });

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value } = e.target;
        setFormData((prev) => ({ ...prev, [name]: value }));
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsLoading(true);

        try {
            const requirementsArray = formData.requirements.split('\n').filter((req) => req.trim() !== '');
            const benefitsArray = formData.benefits ? formData.benefits.split('\n').filter((ben) => ben.trim() !== '') : [];

            if (requirementsArray.length === 0) {
                alert('Please add at least one requirement');
                setIsLoading(false);
                return;
            }

            const data = {
                title: formData.title.trim(),
                department_id: parseInt(formData.department_id),
                major_id: formData.major_id !== 'none' ? parseInt(formData.major_id) : null, // Legacy support
                major_ids: selectedMajorIds.length > 0 ? selectedMajorIds : null,
                location: formData.location.trim(),
                salary: formData.salary.trim() || null,
                company_id: parseInt(formData.company_id),
                requirements: requirementsArray,
                benefits: benefitsArray.length > 0 ? benefitsArray : null,
                question_pack_id: formData.question_pack_id !== 'none' ? parseInt(formData.question_pack_id) : null,
                education_level_id: formData.education_level_id !== 'none' ? parseInt(formData.education_level_id) : null,
                vacancy_type_id: parseInt(formData.vacancy_type_id),
                job_description: formData.job_description.trim() || null,
                psychotest_name: formData.psychotest_name.trim() || 'Tes Psikologi',
                period_id: parseInt(formData.period_id),
            };

            // Biarkan Inertia mengikuti redirect dari backend.
            // Jangan lakukan router.visit kedua kali, supaya request tidak tercancel.
            await router.put(route('admin.jobs.update', { id: job.id }), data, {
                preserveScroll: true,
                onFinish: () => setIsLoading(false),
            });
        } catch (error) {
            setIsLoading(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Job - ${job.title}`} />
            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-semibold">Edit Job</h2>
                    <Button
                        variant="outline"
                        onClick={() => router.visit(route('admin.jobs.index'))}
                    >
                        Cancel
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Edit Job Details</CardTitle>
                        <CardDescription>Update the job opening information.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="space-y-4">
                                    <div>
                                        <Label htmlFor="title">Job Title</Label>
                                        <Input
                                            id="title"
                                            name="title"
                                            value={formData.title}
                                            onChange={handleChange}
                                            required
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="department_id">Department</Label>
                                        <Select
                                            name="department_id"
                                            value={formData.department_id}
                                            onValueChange={(value) => handleChange({ target: { name: 'department_id', value } } as any)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select department" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {departments.map((dept) => (
                                                    <SelectItem key={dept.id} value={String(dept.id)}>
                                                        {dept.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="vacancy_type_id">Vacancy Type</Label>
                                        <Select
                                            name="vacancy_type_id"
                                            value={formData.vacancy_type_id}
                                            onValueChange={(value) => handleChange({ target: { name: 'vacancy_type_id', value } } as any)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select vacancy type" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {vacancyTypes.map((type) => (
                                                    <SelectItem key={type.id} value={String(type.id)}>
                                                        {type.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="job_description">Description</Label>
                                        <Textarea
                                            id="job_description"
                                            name="job_description"
                                            value={formData.job_description}
                                            onChange={handleChange}
                                            className="min-h-[100px]"
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="location">Location</Label>
                                        <Input
                                            id="location"
                                            name="location"
                                            value={formData.location}
                                            onChange={handleChange}
                                            required
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="salary">Salary (Optional)</Label>
                                        <Input
                                            id="salary"
                                            name="salary"
                                            value={formData.salary}
                                            onChange={handleChange}
                                            placeholder="Enter salary range or amount (leave empty if not specified)"
                                        />
                                    </div>
                                </div>

                                <div className="space-y-4">
                                    <div>
                                        <Label htmlFor="company_id">Company</Label>
                                        <Select
                                            name="company_id"
                                            value={formData.company_id}
                                            onValueChange={(value) => handleChange({ target: { name: 'company_id', value } } as any)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select company" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {companies.map((company) => (
                                                    <SelectItem key={company.id} value={String(company.id)}>
                                                        {company.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="major_id">Major (Bisa pilih lebih dari satu)</Label>
                                        <div className="space-y-2">
                                            {/* Selected Majors Display */}
                                            {selectedMajorIds.length > 0 && (
                                                <div className="flex flex-wrap gap-2 p-2 border rounded-md min-h-[42px]">
                                                    {selectedMajorIds.map((majorId: number) => {
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
                                                                        setSelectedMajorIds(selectedMajorIds.filter(id => id !== majorId));
                                                                    }}
                                                                    className="ml-1 hover:bg-gray-300 rounded-full p-0.5"
                                                                >
                                                                    <X className="h-3 w-3" />
                                                                </button>
                                                            </Badge>
                                                        );
                                                    })}
                                                </div>
                                            )}
                                            
                                            {/* Dropdown Toggle Button */}
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
                                            
                                            {/* Dropdown Content */}
                                            {isMajorDropdownOpen && (
                                                <div className="space-y-2">
                                                    <Input
                                                        type="text"
                                                        placeholder="Cari major..."
                                                        value={majorSearchQuery}
                                                        onChange={(e) => setMajorSearchQuery(e.target.value)}
                                                        className="w-full"
                                                    />
                                                    <div className="border rounded-md p-3 min-h-[42px] max-h-[200px] overflow-y-auto">
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
                                                                        const isSelected = selectedMajorIds.includes(major.id);
                                                                        return (
                                                                            <label
                                                                                key={major.id}
                                                                                className="flex items-center space-x-2 cursor-pointer hover:bg-gray-50 p-2 rounded"
                                                                            >
                                                                                <input
                                                                                    type="checkbox"
                                                                                    checked={isSelected}
                                                                                    onChange={(e) => {
                                                                                        if (e.target.checked) {
                                                                                            setSelectedMajorIds([...selectedMajorIds, major.id]);
                                                                                        } else {
                                                                                            setSelectedMajorIds(selectedMajorIds.filter(id => id !== major.id));
                                                                                        }
                                                                                    }}
                                                                                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
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
                                    </div>

                                    <div>
                                        <Label htmlFor="education_level_id">Education Level</Label>
                                        <Select
                                            name="education_level_id"
                                            value={formData.education_level_id}
                                            onValueChange={(value) => handleChange({ target: { name: 'education_level_id', value } } as any)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select education level" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">None</SelectItem>
                                                {educationLevels.map((level) => (
                                                    <SelectItem key={level.id} value={String(level.id)}>
                                                        {level.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="question_pack_id">Question Pack</Label>
                                        <Select
                                            name="question_pack_id"
                                            value={formData.question_pack_id}
                                            onValueChange={(value) => handleChange({ target: { name: 'question_pack_id', value } } as any)}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select question pack" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="none">None</SelectItem>
                                                {questionPacks.map((pack) => (
                                                    <SelectItem key={pack.id} value={String(pack.id)}>
                                                        {pack.pack_name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    <div>
                                        <Label htmlFor="psychotest_name">Test Name</Label>
                                        <Input
                                            id="psychotest_name"
                                            name="psychotest_name"
                                            value={formData.psychotest_name}
                                            onChange={handleChange}
                                            placeholder="Enter name for the psychological test"
                                        />
                                        <p className="text-xs text-gray-500 mt-1">
                                            This name will be displayed to candidates during the test
                                        </p>
                                    </div>

                                    <div>
                                        <Label htmlFor="requirements">Requirements (one per line)</Label>
                                        <Textarea
                                            id="requirements"
                                            name="requirements"
                                            value={formData.requirements}
                                            onChange={handleChange}
                                            rows={4}
                                            required
                                        />
                                    </div>

                                    <div>
                                        <Label htmlFor="benefits">Benefits (one per line, optional)</Label>
                                        <Textarea
                                            id="benefits"
                                            name="benefits"
                                            value={formData.benefits}
                                            onChange={handleChange}
                                            rows={4}
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="flex justify-end gap-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => router.visit(route('admin.jobs.index'))}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={isLoading}>
                                    {isLoading ? 'Saving...' : 'Save Changes'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
} 