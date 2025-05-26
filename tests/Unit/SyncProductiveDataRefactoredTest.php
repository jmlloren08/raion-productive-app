<?php

namespace Tests\Unit;

use App\Actions\Productive\FetchCompanies;
use App\Actions\Productive\FetchDeals;
use App\Actions\Productive\FetchProjects;
use App\Actions\Productive\FetchTimeEntries;
use App\Actions\Productive\FetchTimeEntryVersions;
use App\Actions\Productive\InitializeClient;
use App\Actions\Productive\StoreData;
use App\Actions\Productive\ValidateDataIntegrity;
use App\Console\Commands\SyncProductiveDataRefactored;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;
use Illuminate\Console\Application;
use Illuminate\Console\OutputStyle;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

class SyncProductiveDataRefactoredTest extends TestCase
{
    private SyncProductiveDataRefactored $command;
    private $initializeClientAction;
    private $fetchCompaniesAction;
    private $fetchProjectsAction;
    private $fetchDealsAction;
    private $fetchTimeEntriesAction;
    private $fetchTimeEntryVersionsAction;
    private $storeDataAction;
    private $validateDataIntegrityAction;    private OutputStyle $output;
    private BufferedOutput $bufferedOutput;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock all the actions
        $this->initializeClientAction = Mockery::mock(InitializeClient::class);
        $this->fetchCompaniesAction = Mockery::mock(FetchCompanies::class);
        $this->fetchProjectsAction = Mockery::mock(FetchProjects::class);
        $this->fetchDealsAction = Mockery::mock(FetchDeals::class);
        $this->fetchTimeEntriesAction = Mockery::mock(FetchTimeEntries::class);
        $this->fetchTimeEntryVersionsAction = Mockery::mock(FetchTimeEntryVersions::class);
        $this->storeDataAction = Mockery::mock(StoreData::class);
        $this->validateDataIntegrityAction = Mockery::mock(ValidateDataIntegrity::class);

        // Create command with mocked dependencies
        $this->command = new SyncProductiveDataRefactored(
            $this->initializeClientAction,
            $this->fetchCompaniesAction,
            $this->fetchProjectsAction,
            $this->fetchDealsAction,
            $this->fetchTimeEntriesAction,
            $this->fetchTimeEntryVersionsAction,
            $this->storeDataAction,
            $this->validateDataIntegrityAction
        );

        // Set up command input and output
        $this->bufferedOutput = new BufferedOutput();
        $this->output = new OutputStyle(new ArrayInput([]), $this->bufferedOutput);
        $this->command->setOutput($this->output);

        // Set Laravel instance for command
        $this->command->setLaravel(app());
    }    public function test_executes_sync_process_successfully()
    {        // Set up expectations for each action
        Http::fake();
        $pendingRequest = Http::withoutVerifying()
            ->timeout(60)
            ->retry(3, 5000)
            ->withHeaders([
                'X-Auth-Token' => 'test-token',
                'X-Organization-Id' => 'test-org',
                'Content-Type' => 'application/vnd.api+json',
                'Accept' => 'application/vnd.api+json',
            ]);
        $this->initializeClientAction->shouldReceive('handle')->once()->andReturn($pendingRequest);

        $this->fetchCompaniesAction->shouldReceive('handle')->once()->andReturn([
            'success' => true,
            'companies' => [['id' => 1, 'name' => 'Test Company']]
        ]);

        $this->fetchProjectsAction->shouldReceive('handle')->once()->andReturn([
            'success' => true,
            'projects' => [['id' => 1, 'name' => 'Test Project']]
        ]);

        $this->fetchDealsAction->shouldReceive('handle')->once()->andReturn([
            'success' => true,
            'deals' => [['id' => 1, 'name' => 'Test Deal']]
        ]);

        $this->fetchTimeEntriesAction->shouldReceive('handle')->once()->andReturn([
            'success' => true,
            'time_entries' => [['id' => 1, 'note' => 'Test Entry']]
        ]);

        $this->fetchTimeEntryVersionsAction->shouldReceive('handle')->once()->andReturn([
            'success' => true,
            'time_entry_versions' => [['id' => 1, 'version' => 1]]
        ]);

        $this->storeDataAction->shouldReceive('handle')->once()->andReturn(true);

        $this->validateDataIntegrityAction->shouldReceive('handle')->once()->andReturn([
            'companies' => ['total' => 1],
            'projects' => ['total' => 1],
            'deals' => ['total' => 1],
            'time_entries' => ['total' => 1],
            'time_entry_versions' => ['total' => 1]
        ]);

        // Configure test environment
        config([
            'services.productive.api_url' => 'http://api.example.com',
            'services.productive.api_token' => 'test-token',
            'services.productive.organization_id' => 'test-org'
        ]);

        // Execute the command
        $exitCode = $this->command->handle();

        // Assert command completed successfully
        $this->assertEquals(0, $exitCode, 'Command should complete successfully');
          // The test should have output the sync summary
        $output = $this->getOutput();
        $this->assertStringContainsString('==== Sync Summary ====', $output);
        $this->assertStringContainsString('Companies synced: 1', $output);
        $this->assertStringContainsString('Projects synced: 1', $output);
        $this->assertStringContainsString('Deals synced: 1', $output);
        $this->assertStringContainsString('Time Entries synced: 1', $output);
        $this->assertStringContainsString('Time Entry Versions synced: 1', $output);
    }    public function test_handles_company_fetch_failure()
    {        // Set up expectations
        Http::fake();
        $pendingRequest = Http::withoutVerifying()
            ->timeout(60)
            ->retry(3, 5000)
            ->withHeaders([
                'X-Auth-Token' => 'test-token',
                'X-Organization-Id' => 'test-org',
                'Content-Type' => 'application/vnd.api+json',
                'Accept' => 'application/vnd.api+json',
            ]);
        $this->initializeClientAction->shouldReceive('handle')->once()->andReturn($pendingRequest);

        $this->fetchCompaniesAction->shouldReceive('handle')->once()->andReturn([
            'success' => false
        ]);

        // Configure test environment
        config([
            'services.productive.api_url' => 'http://api.example.com',
            'services.productive.api_token' => 'test-token',
            'services.productive.organization_id' => 'test-org'
        ]);

        // Execute the command
        $exitCode = $this->command->handle();

        // Assert command failed and proper error message was shown
        $this->assertEquals(1, $exitCode, 'Command should fail with exit code 1');
        $this->assertStringContainsString('Failed to fetch companies', $this->getOutput());
    }        public function test_handles_storage_failure()
    {        // Set up expectations for successful fetch but failed storage
        Http::fake();
        $pendingRequest = Http::withoutVerifying()
            ->timeout(60)
            ->retry(3, 5000)
            ->withHeaders([
                'X-Auth-Token' => 'test-token',
                'X-Organization-Id' => 'test-org',
                'Content-Type' => 'application/vnd.api+json',
                'Accept' => 'application/vnd.api+json',
            ]);
        $this->initializeClientAction->shouldReceive('handle')->once()->andReturn($pendingRequest);

        $this->fetchCompaniesAction->shouldReceive('handle')->once()->andReturn([
            'success' => true,
            'companies' => []
        ]);

        $this->fetchProjectsAction->shouldReceive('handle')->once()->andReturn([
            'success' => true,
            'projects' => []
        ]);

        $this->fetchDealsAction->shouldReceive('handle')->once()->andReturn([
            'success' => true,
            'deals' => []
        ]);

        $this->fetchTimeEntriesAction->shouldReceive('handle')->once()->andReturn([
            'success' => true,
            'time_entries' => []
        ]);

        $this->fetchTimeEntryVersionsAction->shouldReceive('handle')->once()->andReturn([
            'success' => true,
            'time_entry_versions' => []
        ]);
        
        // Simulate storage failure
        $this->storeDataAction->shouldReceive('handle')->once()->andReturn(false);

        // Configure test environment
        config([
            'services.productive.api_url' => 'http://api.example.com',
            'services.productive.api_token' => 'test-token',
            'services.productive.organization_id' => 'test-org'
        ]);

        // Execute the command
        $exitCode = $this->command->handle();

        // Assert command failed and proper error message was shown
        $this->assertEquals(1, $exitCode, 'Command should fail with exit code 1');
        $this->assertStringContainsString('Failed to store data in database', $this->getOutput());
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function getOutput(): string
    {
        return $this->bufferedOutput->fetch();
    }
}
