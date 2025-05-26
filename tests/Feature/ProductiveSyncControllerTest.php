<?php

namespace Tests\Feature;

use App\Actions\Productive\GetRelationshipStats;
use App\Actions\Productive\GetSyncStatus;
use App\Actions\Productive\TriggerSync;
use App\Http\Controllers\ProductiveSyncController;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class ProductiveSyncControllerTest extends TestCase
{
    private ProductiveSyncController $controller;
    private $getSyncStatusAction;
    private $triggerSyncAction;
    private $getRelationshipStatsAction;

    protected function setUp(): void
    {
        parent::setUp();

        // Create mocks for actions
        $this->getSyncStatusAction = Mockery::mock(GetSyncStatus::class);
        $this->triggerSyncAction = Mockery::mock(TriggerSync::class);
        $this->getRelationshipStatsAction = Mockery::mock(GetRelationshipStats::class);

        // Create controller with mocked dependencies
        $this->controller = new ProductiveSyncController(
            $this->getSyncStatusAction,
            $this->triggerSyncAction,
            $this->getRelationshipStatsAction
        );
    }    /** @test */
    public function it_can_get_sync_status_successfully()
    {
        // Arrange
        $now = now();
        $expectedResponse = [
            'last_sync' => $now,
            'is_syncing' => false,
            'stats' => ['companies_count' => 10]
        ];

        $this->getSyncStatusAction
            ->expects('handle')
            ->withNoArgs()
            ->once()
            ->andReturn($expectedResponse);

        // Act
        $response = $this->controller->status();
        $responseData = $response->getData(true);

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        // Compare everything except the exact timestamp
        $this->assertArrayHasKey('last_sync', $responseData);
        $this->assertEquals($expectedResponse['is_syncing'], $responseData['is_syncing']);
        $this->assertEquals($expectedResponse['stats'], $responseData['stats']);
    }

    /** @test */
    public function it_handles_sync_status_errors_gracefully()
    {
        // Arrange
        $this->getSyncStatusAction
            ->expects('handle')
            ->withNoArgs()
            ->once()
            ->andThrow(new \Exception('Database connection failed'));

        // Act
        $response = $this->controller->status();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to get sync status', $responseData['message']);
    }

    /** @test */
    public function it_can_trigger_sync_successfully()
    {
        // Arrange
        $expectedResponse = [
            'message' => 'Sync completed successfully',
            'status' => 'success',
            'code' => 200,
            'execution_time' => '5.2 seconds',
            'stats' => ['companies_count' => 10],
            'relationships' => ['companies_with_projects' => 8]
        ];

        $this->triggerSyncAction
            ->expects('handle')
            ->withNoArgs()
            ->once()
            ->andReturn($expectedResponse);

        // Act
        $response = $this->controller->sync();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedResponse, $response->getData(true));
    }

    /** @test */
    public function it_handles_sync_already_in_progress()
    {
        // Arrange
        $expectedResponse = [
            'message' => 'Sync already in progress',
            'status' => 'error',
            'code' => 409
        ];

        $this->triggerSyncAction
            ->expects('handle')
            ->withNoArgs()
            ->once()
            ->andReturn($expectedResponse);

        // Act
        $response = $this->controller->sync();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(409, $response->getStatusCode());
        $this->assertEquals($expectedResponse, $response->getData(true));
    }

    /** @test */
    public function it_handles_sync_failures_gracefully()
    {
        // Arrange
        $this->triggerSyncAction
            ->expects('handle')
            ->withNoArgs()
            ->once()
            ->andThrow(new \Exception('API connection timeout'));

        // Act
        $response = $this->controller->sync();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to run sync process', $responseData['message']);
    }

    /** @test */
    public function it_can_get_relationship_stats_successfully()
    {
        // Arrange
        $expectedResponse = [
            'companies' => [
                'total' => 10,
                'with_projects' => 8
            ],
            'projects' => [
                'total' => 20,
                'with_company' => 18
            ]
        ];

        $this->getRelationshipStatsAction
            ->expects('handle')
            ->withNoArgs()
            ->once()
            ->andReturn($expectedResponse);

        // Act
        $response = $this->controller->relationshipStats();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($expectedResponse, $response->getData(true));
    }

    /** @test */
    public function it_handles_relationship_stats_errors_gracefully()
    {
        // Arrange
        $this->getRelationshipStatsAction
            ->expects('handle')
            ->withNoArgs()
            ->once()
            ->andThrow(new \Exception('Database query failed'));

        // Act
        $response = $this->controller->relationshipStats();

        // Assert
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $responseData = $response->getData(true);
        $this->assertEquals('error', $responseData['status']);
        $this->assertEquals('Failed to get relationship statistics', $responseData['message']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}