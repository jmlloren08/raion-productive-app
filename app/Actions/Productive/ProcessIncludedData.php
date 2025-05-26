<?php

namespace App\Actions\Productive;

use Illuminate\Console\Command;

class ProcessIncludedData extends AbstractAction
{
    /**
     * Process included relationships from API response to extract related entity details
     *
     * @param array $parameters
     * @return array The enriched resources with included data
     */
    public function handle(array $parameters = []): array
    {
        $responseBody = $parameters['responseBody'] ?? [];
        $resources = $parameters['resources'] ?? [];
        $command = $parameters['command'] ?? null;

        if (!isset($responseBody['included']) || !is_array($responseBody['included'])) {
            return $resources;
        }

        if ($command instanceof Command) {
            $command->info("Processing " . count($responseBody['included']) . " included resources");
        }

        // Create a map of included resources
        $includedMap = [];
        $includedTypes = [];
        foreach ($responseBody['included'] as $included) {
            $resourceType = $included['type'] ?? 'unknown';
            $resourceId = $included['id'] ?? 'unknown';
            if ($resourceType !== 'unknown' && $resourceId !== 'unknown') {
                $includedMap["{$resourceType}:{$resourceId}"] = $included;
                $includedTypes[$resourceType] = ($includedTypes[$resourceType] ?? 0) + 1;
            }
        }

        // Log the types of included resources
        if ($command instanceof Command) {
            foreach ($includedTypes as $type => $count) {
                $command->info("Found {$count} included resources of type '{$type}'");
            }
        }

        // Enrich resources with included entities
        foreach ($resources as &$resource) {
            if (isset($resource['relationships'])) {
                foreach ($resource['relationships'] as $relName => $relData) {
                    if (isset($relData['data'])) {
                        // Handle single relationship
                        if (!isset($relData['data'][0])) {
                            $relType = $relData['data']['type'] ?? null;
                            $relId = $relData['data']['id'] ?? null;
                            if ($relType && $relId) {
                                $mapKey = "{$relType}:{$relId}";
                                if (isset($includedMap[$mapKey])) {
                                    // Add the included data to the relationship
                                    $resource['relationships'][$relName]['included'] = $includedMap[$mapKey];

                                    // If this included entity also has relationships, process them
                                    $includedEntity = $includedMap[$mapKey];
                                    if (isset($includedEntity['relationships'])) {
                                        foreach ($includedEntity['relationships'] as $subRelName => $subRelData) {
                                            if (isset($subRelData['data'])) {
                                                $processNestedAction = new ProcessNestedRelationship();
                                                $processNestedAction->handle([
                                                    'includedMap' => $includedMap,
                                                    'targetRelationship' => &$resource['relationships'][$relName]['included']['relationships'][$subRelName],
                                                    'relationshipData' => $subRelData['data']
                                                ]);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        // Handle array of relationships
                        else {
                            foreach ($relData['data'] as $i => $rel) {
                                $relType = $rel['type'] ?? null;
                                $relId = $rel['id'] ?? null;
                                if ($relType && $relId) {
                                    $mapKey = "{$relType}:{$relId}";
                                    if (isset($includedMap[$mapKey])) {
                                        if (!isset($resource['relationships'][$relName]['included'])) {
                                            $resource['relationships'][$relName]['included'] = [];
                                        }

                                        // Add the included data to the relationship
                                        $resource['relationships'][$relName]['included'][] = $includedMap[$mapKey];

                                        // Process nested relationships if any
                                        $includedIndex = count($resource['relationships'][$relName]['included']) - 1;
                                        $includedEntity = $includedMap[$mapKey];

                                        if (isset($includedEntity['relationships'])) {
                                            foreach ($includedEntity['relationships'] as $subRelName => $subRelData) {
                                                if (isset($subRelData['data'])) {
                                                    $processNestedAction = new ProcessNestedRelationship();
                                                    $processNestedAction->handle([
                                                        'includedMap' => $includedMap,
                                                        'targetRelationship' => &$resource['relationships'][$relName]['included'][$includedIndex]['relationships'][$subRelName],
                                                        'relationshipData' => $subRelData['data']
                                                    ]);
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $resources;
    }
}
