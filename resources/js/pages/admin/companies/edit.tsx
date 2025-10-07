import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { ArrowLeft, Building2, Globe, Mail, MapPin, Phone, Save, BookOpen, Upload, X } from 'lucide-react';
import { useState, useEffect } from 'react';
import { toast } from 'sonner';

interface AboutUs {
    id: number;
    vision?: string;
    mission?: string;
}

interface Company {
    id: number;
    name: string;
    logo?: string;
    logo_url?: string;
    description?: string;
    email?: string;
    phone?: string;
    address?: string;
    website?: string;
    featured: boolean;
    display_order?: number;
    created_at: string;
    about_us?: AboutUs;
}

interface Props {
    company: Company;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Company Management',
        href: '/dashboard/company-management',
    },
    {
        title: 'Edit Company',
        href: '#',
    },
];

// Mock data untuk parent company - nanti bisa diambil dari props
const parentCompanyInfo = {
    name: "PT MITRA KARYA ANALITIKA GROUP",
    industry: "Importer & Distributor || Laboratory Supply - Health & Safety Equipment - Environmental ||",
    email: "cs.mitrakarya.analitika@gmail.com",
    phone: "(024)7641-1111",
    address: "Jl. Klipang Ruko Amsterdam No.9D, Sendangmulyo, Kec. Tembalang, Kota Semarang, Jawa Tengah 50272",
    website: "https://mikacares.co.id/",
    established: "2014",
};

export default function EditCompany({ company }: Props) {
    const [logoFile, setLogoFile] = useState<File | null>(null);
    const [logoPreview, setLogoPreview] = useState<string | null>(company.logo_url || company.logo || null);
    const [processing, setProcessing] = useState(false);

    const { data, setData, errors } = useForm({
        name: company.name || '',
        logo: null as File | null,
        description: company.description || '',
        email: company.email || '',
        phone: company.phone || '',
        address: company.address || '',
        website: company.website || '',
        featured: company.featured || false,
        display_order: company.display_order?.toString() || '',
        vision: company.about_us?.vision || '',
        mission: company.about_us?.mission || '',
    });

    // Update form data when company prop changes
    useEffect(() => {
        if (company) {
            setData('name', company.name || '');
            setData('description', company.description || '');
            setData('email', company.email || '');
            setData('phone', company.phone || '');
            setData('address', company.address || '');
            setData('website', company.website || '');
            setData('featured', company.featured || false);
            setData('display_order', company.display_order?.toString() || '');
            setData('vision', company.about_us?.vision || '');
            setData('mission', company.about_us?.mission || '');
        }
    }, [company, setData]);

    const handleLogoChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setLogoFile(file);
            setData('logo', file);
            
            // Create preview URL
            const reader = new FileReader();
            reader.onload = (e) => {
                setLogoPreview(e.target?.result as string);
            };
            reader.readAsDataURL(file);
        }
    };

    const removeLogo = () => {
        setLogoFile(null);
        setLogoPreview(null);
        setData('logo', null);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        
        // Debug: Log form data before submission
        console.log('Form data being submitted:', data);
        console.log('Logo file:', data.logo);
        console.log('Has logo file:', !!data.logo);
        
        setProcessing(true);
        
        // For file uploads with PUT, we need to use POST with _method spoofing
        // This is a Laravel/PHP limitation with multipart/form-data and PUT
        router.post(route('companies.update', company.id), {
            ...data,
            _method: 'PUT'
        }, {
            onSuccess: () => {
                toast.success('Company updated successfully!');
                setProcessing(false);
            },
            onError: (errors: any) => {
                console.error('Update failed:', errors);
                toast.error('Failed to update company. Please try again.');
                setProcessing(false);
            },
            onFinish: () => {
                setProcessing(false);
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${company.name}`} />

            <div className="flex h-full flex-1 flex-col gap-6 rounded-xl p-4">
                <div>
                    <div className="mb-4 flex items-center justify-between">
                        <div className="flex items-center gap-4">
                            <Link href={route('company-management.index')}>
                                <Button variant="outline" size="sm">
                                    <ArrowLeft className="mr-2 h-4 w-4" />
                                </Button>
                            </Link>
                            <h2 className="text-2xl font-semibold">Edit Company</h2>
                        </div>
                    </div>

                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Left Column */}
                        <div className="space-y-6">
                            {/* Edit Company Form */}
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center gap-2">
                                        <Building2 className="h-5 w-5 text-blue-600" />
                                        <CardTitle>Edit Subsidiary Company</CardTitle>
                                    </div>
                                    <CardDescription>Update the details for {company.name}</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={handleSubmit} className="space-y-4" encType="multipart/form-data">
                                        <div className="space-y-2">
                                            <Label htmlFor="name">Company Name *</Label>
                                            <Input
                                                id="name"
                                                value={data.name}
                                                onChange={(e) => setData('name', e.target.value)}
                                                placeholder="Enter subsidiary company name"
                                                className={errors.name ? 'border-red-500' : ''}
                                            />
                                            {errors.name && (
                                                <p className="text-sm text-red-500">{errors.name}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="logo">Company Logo</Label>
                                            <div className="space-y-4">
                                                {logoPreview ? (
                                                    <div className="relative">
                                                        <img
                                                            src={logoPreview}
                                                            alt="Logo preview"
                                                            className="w-32 h-32 object-cover rounded-lg border border-gray-200"
                                                        />
                                                        <Button
                                                            type="button"
                                                            variant="destructive"
                                                            size="sm"
                                                            className="absolute -top-2 -right-2"
                                                            onClick={removeLogo}
                                                        >
                                                            <X className="h-4 w-4" />
                                                        </Button>
                                                    </div>
                                                ) : (
                                                    <div className="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                                                        <Upload className="mx-auto h-12 w-12 text-gray-400" />
                                                        <p className="mt-2 text-sm text-gray-600">
                                                            Upload company logo
                                                        </p>
                                                        <p className="text-xs text-gray-500">
                                                            PNG, JPG, GIF up to 2MB
                                                        </p>
                                                    </div>
                                                )}
                                                <Input
                                                    id="logo"
                                                    type="file"
                                                    accept="image/*"
                                                    onChange={handleLogoChange}
                                                    className={errors.logo ? 'border-red-500' : ''}
                                                />
                                                {errors.logo && (
                                                    <p className="text-sm text-red-500">{errors.logo}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="description">Description</Label>
                                            <Textarea
                                                id="description"
                                                value={data.description}
                                                onChange={(e) => setData('description', e.target.value)}
                                                placeholder="Enter subsidiary company description"
                                                className={errors.description ? 'border-red-500' : ''}
                                                rows={3}
                                            />
                                            {errors.description && (
                                                <p className="text-sm text-red-500">{errors.description}</p>
                                            )}
                                        </div>

                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="email">Email</Label>
                                                <Input
                                                    id="email"
                                                    type="email"
                                                    value={data.email}
                                                    onChange={(e) => setData('email', e.target.value)}
                                                    placeholder="company@example.com"
                                                    className={errors.email ? 'border-red-500' : ''}
                                                />
                                                {errors.email && (
                                                    <p className="text-sm text-red-500">{errors.email}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="phone">Phone</Label>
                                                <Input
                                                    id="phone"
                                                    value={data.phone}
                                                    onChange={(e) => setData('phone', e.target.value)}
                                                    placeholder="+62 21 1234 5678"
                                                    className={errors.phone ? 'border-red-500' : ''}
                                                />
                                                {errors.phone && (
                                                    <p className="text-sm text-red-500">{errors.phone}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="address">Address</Label>
                                            <Textarea
                                                id="address"
                                                value={data.address}
                                                onChange={(e) => setData('address', e.target.value)}
                                                placeholder="Complete company address"
                                                className={errors.address ? 'border-red-500' : ''}
                                                rows={3}
                                            />
                                            {errors.address && (
                                                <p className="text-sm text-red-500">{errors.address}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="website">Website</Label>
                                            <Input
                                                id="website"
                                                value={data.website}
                                                onChange={(e) => setData('website', e.target.value)}
                                                placeholder="https://www.company.com"
                                                className={errors.website ? 'border-red-500' : ''}
                                            />
                                            {errors.website && (
                                                <p className="text-sm text-red-500">{errors.website}</p>
                                            )}
                                        </div>

                                        <div className="grid gap-4 md:grid-cols-2">
                                            <div className="space-y-2">
                                                <Label htmlFor="display_order">Display Order</Label>
                                                <Input
                                                    id="display_order"
                                                    type="number"
                                                    value={data.display_order}
                                                    onChange={(e) => setData('display_order', e.target.value)}
                                                    placeholder="0"
                                                    min="0"
                                                    className={errors.display_order ? 'border-red-500' : ''}
                                                />
                                                {errors.display_order && (
                                                    <p className="text-sm text-red-500">{errors.display_order}</p>
                                                )}
                                            </div>

                                            <div className="space-y-2">
                                                <Label htmlFor="featured" className="flex items-center gap-2">
                                                    <input
                                                        id="featured"
                                                        type="checkbox"
                                                        checked={data.featured}
                                                        onChange={(e) => setData('featured', e.target.checked)}
                                                        className="rounded border-gray-300"
                                                    />
                                                    Featured Company
                                                </Label>
                                                {errors.featured && (
                                                    <p className="text-sm text-red-500">{errors.featured}</p>
                                                )}
                                            </div>
                                        </div>

                                        <div className="flex justify-end items-center space-x-2 pt-6">
                                            <Link href={route('company-management.index')}>
                                                <Button type="button" variant="outline">
                                                    Cancel
                                                </Button>
                                            </Link>
                                            <Button type="submit" disabled={processing}>
                                                {processing ? (
                                                    <>
                                                        <div className="mr-2 h-4 w-4 animate-spin rounded-full border-2 border-gray-300 border-t-white"></div>
                                                        Updating...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Save className="mr-2 h-4 w-4" />
                                                        Update Company
                                                    </>
                                                )}
                                            </Button>
                                        </div>
                                    </form>
                                </CardContent>
                            </Card>

                            {/* About Us Section */}
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center gap-2">
                                        <BookOpen className="h-5 w-5 text-blue-600" />
                                        <CardTitle>About Us</CardTitle>
                                    </div>
                                    <CardDescription>Update your company's vision and mission</CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <div className="space-y-2">
                                            <Label htmlFor="vision">Vision</Label>
                                            <Textarea
                                                id="vision"
                                                value={data.vision}
                                                onChange={(e) => setData('vision', e.target.value)}
                                                placeholder="Enter company vision"
                                                className={errors.vision ? 'border-red-500' : ''}
                                                rows={3}
                                            />
                                            {errors.vision && (
                                                <p className="text-sm text-red-500">{errors.vision}</p>
                                            )}
                                        </div>

                                        <div className="space-y-2">
                                            <Label htmlFor="mission">Mission</Label>
                                            <Textarea
                                                id="mission"
                                                value={data.mission}
                                                onChange={(e) => setData('mission', e.target.value)}
                                                placeholder="Enter company mission"
                                                className={errors.mission ? 'border-red-500' : ''}
                                                rows={3}
                                            />
                                            {errors.mission && (
                                                <p className="text-sm text-red-500">{errors.mission}</p>
                                            )}
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Parent Company Information Card - Right Column */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center gap-2">
                                    <Building2 className="h-5 w-5 text-blue-600" />
                                    <CardTitle>Parent Company Information</CardTitle>
                                </div>
                                <CardDescription>
                                    This subsidiary is under the following parent company
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="font-medium text-gray-900">{parentCompanyInfo.name}</p>
                                            <Badge variant="secondary" className="mt-1">
                                                {parentCompanyInfo.industry}
                                            </Badge>
                                        </div>
                                    </div>

                                    <Separator />

                                    <div className="space-y-3">
                                        <div className="flex items-start gap-3">
                                            <Mail className="mt-0.5 h-4 w-4 text-gray-400" />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">Email</p>
                                                <p className="text-sm text-gray-600">{parentCompanyInfo.email}</p>
                                            </div>
                                        </div>

                                        <div className="flex items-start gap-3">
                                            <Phone className="mt-0.5 h-4 w-4 text-gray-400" />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">Phone</p>
                                                <p className="text-sm text-gray-600">{parentCompanyInfo.phone}</p>
                                            </div>
                                        </div>

                                        <div className="flex items-start gap-3">
                                            <Globe className="mt-0.5 h-4 w-4 text-gray-400" />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">Website</p>
                                                <a 
                                                    href={parentCompanyInfo.website} 
                                                    target="_blank" 
                                                    rel="noopener noreferrer"
                                                    className="text-sm text-blue-600 hover:underline"
                                                >
                                                    {parentCompanyInfo.website}
                                                </a>
                                            </div>
                                        </div>

                                        <div className="flex items-start gap-3">
                                            <MapPin className="mt-0.5 h-4 w-4 text-gray-400" />
                                            <div>
                                                <p className="text-sm font-medium text-gray-900">Address</p>
                                                <p className="text-sm text-gray-600">{parentCompanyInfo.address}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <Separator />

                                    <div className="grid grid-cols-2 gap-4">
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">Established</p>
                                            <p className="text-sm text-muted-foreground">{parentCompanyInfo.established}</p>
                                        </div>
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">Created</p>
                                            <p className="text-sm text-muted-foreground">
                                                {new Date(company.created_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}