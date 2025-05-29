<?php

namespace App\Actions\Productive;

use App\Actions\Productive\Store\StoreCompany;
use App\Actions\Productive\Store\StoreContactEntry;
use App\Actions\Productive\Store\StoreContract;
use App\Actions\Productive\Store\StoreDeal;
use App\Actions\Productive\Store\StoreDealStatus;
use App\Actions\Productive\Store\StoreDocumentStyle;
use App\Actions\Productive\Store\StoreDocumentType;
use App\Actions\Productive\Store\StoreLostReason;
use App\Actions\Productive\Store\StorePeople;
use App\Actions\Productive\Store\StoreProject;
use App\Actions\Productive\Store\StoreSubsidiary;
use App\Actions\Productive\Store\StoreTaxRate;
use App\Actions\Productive\Store\StoreWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StoreData extends AbstractAction
{
    public function __construct(
        private StoreCompany $storeCompanyAction,
        private StoreProject $storeProjectAction,
        private StorePeople $storePeopleAction,
        private StoreWorkflow $storeWorkflowAction,
        private StoreDeal $storeDealAction,
        private StoreDocumentType $storeDocumentTypeAction,
        private StoreContactEntry $storeContactEntryAction,
        private StoreSubsidiary $storeSubsidiaryAction,
        private StoreTaxRate $storeTaxRateAction,
        private StoreDocumentStyle $storeDocumentStyleAction,
        private StoreDealStatus $storeDealStatusAction,
        private StoreLostReason $storeLostReasonAction,
        private StoreContract $storeContractAction
    ) {}

    /**
     * Store all fetched data in the database
     *
     * @param array $parameters
     * @return bool
     */
    public function handle(array $parameters = []): bool
    {
        $data = $parameters['data'] ?? [];
        $command = $parameters['command'] ?? null;

        try {
            DB::beginTransaction();

            // First validate that we have data to store
            if (
                empty($data['companies']) && empty($data['projects']) &&
                empty($data['people']) && empty($data['workflows']) &&
                empty($data['deals']) && empty($data['document_types']) &&
                empty($data['contact_entries']) && empty($data['subsidiaries']) &&
                empty($data['tax_rates']) && empty($data['document_styles']) &&
                empty($data['deal_statuses']) && empty($data['lost_reasons']) &&
                empty($data['contracts'])
            ) {
                if ($command instanceof Command) {
                    $command->warn('No data fetched from Productive API. Skipping storage.');
                }
                return true;
            }

            // Store subsidiaries first since other entities might depend on them
            if (!empty($data['subsidiaries'])) {
                if ($command instanceof Command) {
                    $command->info('Storing subsidiaries...');
                }

                $subsidiariesSuccess = 0;
                $subsidiariesError = 0;
                foreach ($data['subsidiaries'] as $subsidiaryData) {
                    try {
                        $this->storeSubsidiaryAction->handle([
                            'subsidiaryData' => $subsidiaryData,
                            'command' => $command
                        ]);
                        $subsidiariesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store subsidiary (ID: {$subsidiaryData['id']}): " . $e->getMessage());
                        }
                        $subsidiariesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Subsidiaries: {$subsidiariesSuccess} stored successfully, {$subsidiariesError} failed");
                }
            }

            // Store tax rates
            if (!empty($data['tax_rates'])) {
                if ($command instanceof Command) {
                    $command->info('Storing tax rates...');
                }

                $taxRatesSuccess = 0;
                $taxRatesError = 0;
                foreach ($data['tax_rates'] as $taxRateData) {
                    try {
                        $this->storeTaxRateAction->handle([
                            'taxRateData' => $taxRateData,
                            'command' => $command
                        ]);
                        $taxRatesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store tax rate (ID: {$taxRateData['id']}): " . $e->getMessage());
                        }
                        $taxRatesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Tax Rates: {$taxRatesSuccess} stored successfully, {$taxRatesError} failed");
                }
            }

            // Store document styles
            if (!empty($data['document_styles'])) {
                if ($command instanceof Command) {
                    $command->info('Storing document styles...');
                }

                $documentStylesSuccess = 0;
                $documentStylesError = 0;
                foreach ($data['document_styles'] as $documentStyleData) {
                    try {
                        $this->storeDocumentStyleAction->handle([
                            'documentStyleData' => $documentStyleData,
                            'command' => $command
                        ]);
                        $documentStylesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store document style (ID: {$documentStyleData['id']}): " . $e->getMessage());
                        }
                        $documentStylesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Document Styles: {$documentStylesSuccess} stored successfully, {$documentStylesError} failed");
                }
            }

            // Store deal statuses
            if (!empty($data['deal_statuses'])) {
                if ($command instanceof Command) {
                    $command->info('Storing deal statuses...');
                }

                $dealStatusesSuccess = 0;
                $dealStatusesError = 0;
                foreach ($data['deal_statuses'] as $dealStatusData) {
                    try {
                        $this->storeDealStatusAction->handle([
                            'dealStatusData' => $dealStatusData,
                            'command' => $command
                        ]);
                        $dealStatusesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store deal status (ID: {$dealStatusData['id']}): " . $e->getMessage());
                        }
                        $dealStatusesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Deal Statuses: {$dealStatusesSuccess} stored successfully, {$dealStatusesError} failed");
                }
            }

            // Store lost reasons
            if (!empty($data['lost_reasons'])) {
                if ($command instanceof Command) {
                    $command->info('Storing lost reasons...');
                }

                $lostReasonsSuccess = 0;
                $lostReasonsError = 0;
                foreach ($data['lost_reasons'] as $lostReasonData) {
                    try {
                        $this->storeLostReasonAction->handle([
                            'lostReasonData' => $lostReasonData,
                            'command' => $command
                        ]);
                        $lostReasonsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store lost reason (ID: {$lostReasonData['id']}): " . $e->getMessage());
                        }
                        $lostReasonsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Lost Reasons: {$lostReasonsSuccess} stored successfully, {$lostReasonsError} failed");
                }
            }

            // Store companies
            if (!empty($data['companies'])) {
                if ($command instanceof Command) {
                    $command->info('Storing companies...');
                }

                $companiesSuccess = 0;
                $companiesError = 0;
                foreach ($data['companies'] as $companyData) {
                    try {
                        $this->storeCompanyAction->handle([
                            'companyData' => $companyData,
                            'command' => $command
                        ]);
                        $companiesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store company (ID: {$companyData['id']}): " . $e->getMessage());
                        }
                        $companiesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Companies: {$companiesSuccess} stored successfully, {$companiesError} failed");
                }
            }

            // Store people
            if (!empty($data['people'])) {
                if ($command instanceof Command) {
                    $command->info('Storing people...');
                }

                $peopleSuccess = 0;
                $peopleError = 0;
                foreach ($data['people'] as $personData) {
                    try {
                        $this->storePeopleAction->handle([
                            'personData' => $personData,
                            'command' => $command
                        ]);
                        $peopleSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store person (ID: {$personData['id']}): " . $e->getMessage());
                        }
                        $peopleError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("People: {$peopleSuccess} stored successfully, {$peopleError} failed");
                }
            }

            // Store workflows
            if (!empty($data['workflows'])) {
                if ($command instanceof Command) {
                    $command->info('Storing workflows...');
                }

                $workflowsSuccess = 0;
                $workflowsError = 0;
                foreach ($data['workflows'] as $workflowData) {
                    try {
                        $this->storeWorkflowAction->handle([
                            'workflowData' => $workflowData,
                            'command' => $command
                        ]);
                        $workflowsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store workflow (ID: {$workflowData['id']}): " . $e->getMessage());
                        }
                        $workflowsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Workflows: {$workflowsSuccess} stored successfully, {$workflowsError} failed");
                }
            }

            // Store projects
            if (!empty($data['projects'])) {
                if ($command instanceof Command) {
                    $command->info('Storing projects...');
                }

                $projectsSuccess = 0;
                $projectsError = 0;
                foreach ($data['projects'] as $projectData) {
                    try {
                        $this->storeProjectAction->handle([
                            'projectData' => $projectData,
                            'command' => $command
                        ]);
                        $projectsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store project (ID: {$projectData['id']}): " . $e->getMessage());
                        }
                        $projectsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Projects: {$projectsSuccess} stored successfully, {$projectsError} failed");
                }
            }

            // Store document types
            if (!empty($data['document_types'])) {
                if ($command instanceof Command) {
                    $command->info('Storing document types...');
                }

                $documentTypesSuccess = 0;
                $documentTypesError = 0;
                foreach ($data['document_types'] as $documentTypeData) {
                    try {
                        $this->storeDocumentTypeAction->handle([
                            'documentTypeData' => $documentTypeData,
                            'command' => $command
                        ]);
                        $documentTypesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store document type (ID: {$documentTypeData['id']}): " . $e->getMessage());
                        }
                        $documentTypesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Document Types: {$documentTypesSuccess} stored successfully, {$documentTypesError} failed");
                }
            }

            // Store contact entries
            if (!empty($data['contact_entries'])) {
                if ($command instanceof Command) {
                    $command->info('Storing contact entries...');
                }

                $contactEntriesSuccess = 0;
                $contactEntriesError = 0;
                foreach ($data['contact_entries'] as $contactEntryData) {
                    try {
                        $this->storeContactEntryAction->handle([
                            'contactEntryData' => $contactEntryData,
                            'command' => $command
                        ]);
                        $contactEntriesSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store contact entry (ID: {$contactEntryData['id']}): " . $e->getMessage());
                        }
                        $contactEntriesError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Contact Entries: {$contactEntriesSuccess} stored successfully, {$contactEntriesError} failed");
                }
            }

            // Store contracts
            if (!empty($data['contracts'])) {
                if ($command instanceof Command) {
                    $command->info('Storing contracts...');
                }

                $contractsSuccess = 0;
                $contractsError = 0;
                foreach ($data['contracts'] as $contractData) {
                    try {
                        $this->storeContractAction->handle([
                            'contractData' => $contractData,
                            'command' => $command
                        ]);
                        $contractsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store contract (ID: {$contractData['id']}): " . $e->getMessage());
                        }
                        $contractsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Contracts: {$contractsSuccess} stored successfully, {$contractsError} failed");
                }
            }

            // Store deals
            if (!empty($data['deals'])) {
                if ($command instanceof Command) {
                    $command->info('Storing deals...');
                }

                $dealsSuccess = 0;
                $dealsError = 0;
                foreach ($data['deals'] as $dealData) {
                    try {
                        $this->storeDealAction->handle([
                            'dealData' => $dealData,
                            'command' => $command
                        ]);
                        $dealsSuccess++;
                    } catch (\Exception $e) {
                        if ($command instanceof Command) {
                            $command->error("Failed to store deal (ID: {$dealData['id']}): " . $e->getMessage());
                        }
                        $dealsError++;
                    }
                }

                if ($command instanceof Command) {
                    $command->info("Deals: {$dealsSuccess} stored successfully, {$dealsError} failed");
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            if ($command instanceof Command) {
                $command->error('Error storing data: ' . $e->getMessage());
            }
            return false;
        }
    }
}
