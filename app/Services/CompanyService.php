<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use App\Repositories\CompanyRepository;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class CompanyService
{
    public function __construct(protected CompanyRepository $repository)
    {
    }

    public function create(array $data): array
    {
        if (isset($data['logo']) && $data['logo']) {
            $data['logo'] = $data['logo']->store('companies_logos', 'public');
        }

        return ['company' => $this->repository->create($data)];
    }

    public function getCompanyById(Company $company): array
    {
        return ['company' => $this->repository->findById($company)];
    }

    public function getCompanyByName(string $company): array
    {
        return ['company' => $this->repository->findByName($company)];
    }

    public function update(Company $company, array $data): array
    {
        if (isset($data['logo']) && $data['logo']) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $data['logo'] = $data['logo']->store('companies_logos', 'public');
        }
        $updatedCompany = $this->repository->update($company, $data);

        return ['company' => $updatedCompany];
    }

    public function delete(Company $company): ?bool
    {
        return $this->repository->delete($company);
    }

    public function addEmployeeToCompany(Company $company, int $employeeId, int $managerId): array
    {
        $user = User::findOrFail($employeeId);

        if ($user->company_id !== null) {
            throw new ConflictHttpException('This user already belongs to a company.');
        }

        if ($user->manager_id !== null) {
            throw new ConflictHttpException('This user is already assigned to a manager.');
        }

        $employee = $this->repository->addEmployee($user, $company->id, $managerId);

        return ['employee' => $employee];
    }

    public function removeEmployeeFromCompany(Company $company, int $employeeId): array
    {
        $user = User::findOrFail($employeeId);

        if ($user->company_id !== $company->id) {
            throw new ConflictHttpException('This user does not belong to this company.');
        }

        $employee = $this->repository->removeEmployee($user);

        return ['employee' => $employee];
    }
}
