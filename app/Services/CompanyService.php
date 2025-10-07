<?php

namespace App\Services;

use App\Http\Requests\StoreCompanyRequest;
use App\Http\Requests\UpdateCompanyRequest;
use App\Models\Company;
use Illuminate\Http\UploadedFile;

class CompanyService
{
    public function __construct(
        private FileUploadService $fileUploadService
    ) {}

    /**
     * Create a new company with logo upload.
     */
    public function create(StoreCompanyRequest $request): Company
    {
        $data = $request->validated();
        
        // Handle logo upload
        if ($request->hasFile('logo')) {
            $data['logo'] = $this->fileUploadService->upload(
                $request->file('logo'),
                'company-logos'
            );
        }

        // Create company
        $company = Company::create([
            'name' => $data['name'],
            'logo' => $data['logo'] ?? null,
            'description' => $data['description'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'website' => $data['website'] ?? null,
            'featured' => $data['featured'] ?? false,
            'display_order' => $data['display_order'] ?? null,
        ]);

        // Create About Us record if vision or mission is provided
        if (!empty($data['vision']) || !empty($data['mission'])) {
            $company->aboutUs()->create([
                'vision' => $data['vision'] ?? null,
                'mission' => $data['mission'] ?? null,
            ]);
        }

        return $company;
    }

    /**
     * Update company with logo upload handling.
     */
    public function update(Company $company, UpdateCompanyRequest $request): Company
    {
        $data = $request->validated();
        
        // Handle logo upload
        $logoPath = $company->logo; // Keep existing logo if no new file uploaded
        
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($company->logo) {
                $this->fileUploadService->delete($company->logo);
            }
            
            // Upload new logo
            $logoPath = $this->fileUploadService->upload(
                $request->file('logo'),
                'company-logos'
            );
        }

        // Prepare update data (only update fields that are present in request)
        $updateData = array_filter([
            'name' => $data['name'] ?? $company->name,
            'logo' => $logoPath,
            'description' => $data['description'] ?? $company->description,
            'email' => $data['email'] ?? $company->email,
            'phone' => $data['phone'] ?? $company->phone,
            'address' => $data['address'] ?? $company->address,
            'website' => $data['website'] ?? $company->website,
            'featured' => $data['featured'] ?? $company->featured,
            'display_order' => $data['display_order'] ?? $company->display_order,
        ], function ($value) {
            return $value !== null;
        });

        $company->update($updateData);

        // Update or create About Us record
        $this->updateAboutUs($company, $data);

        return $company->fresh();
    }

    /**
     * Delete company and its logo.
     */
    public function delete(Company $company): bool
    {
        // Delete logo if exists
        if ($company->logo) {
            $this->fileUploadService->delete($company->logo);
        }

        return $company->delete();
    }

    /**
     * Update or create About Us record for the company.
     */
    private function updateAboutUs(Company $company, array $data): void
    {
        $existingAboutUs = $company->aboutUs;
        
        $vision = $data['vision'] ?? ($existingAboutUs?->vision);
        $mission = $data['mission'] ?? ($existingAboutUs?->mission);

        if ($vision || $mission) {
            $company->aboutUs()->updateOrCreate(
                ['company_id' => $company->id],
                [
                    'vision' => $vision,
                    'mission' => $mission,
                ]
            );
        }
    }
}