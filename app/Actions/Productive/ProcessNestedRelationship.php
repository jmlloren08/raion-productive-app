<?php

namespace App\Actions\Productive;

class ProcessNestedRelationship extends AbstractAction
{
    /**
     * Helper method to process nested relationships
     *
     * @param array $parameters
     * @return void
     */
    public function handle(array $parameters = []): void
    {
        $includedMap = $parameters['includedMap'] ?? [];
        $targetRelationship = &$parameters['targetRelationship'];
        $relationshipData = $parameters['relationshipData'] ?? [];

        // Handle single relationship
        if (!isset($relationshipData[0])) {
            $relType = $relationshipData['type'] ?? null;
            $relId = $relationshipData['id'] ?? null;
            if ($relType && $relId) {
                $mapKey = "{$relType}:{$relId}";
                if (isset($includedMap[$mapKey])) {
                    // Add the included data to the relationship
                    $targetRelationship['included'] = $includedMap[$mapKey];
                }
            }
        }
        // Handle array of relationships
        else {
            foreach ($relationshipData as $i => $rel) {
                $relType = $rel['type'] ?? null;
                $relId = $rel['id'] ?? null;
                if ($relType && $relId) {
                    $mapKey = "{$relType}:{$relId}";
                    if (isset($includedMap[$mapKey])) {
                        if (!isset($targetRelationship['included'])) {
                            $targetRelationship['included'] = [];
                        }
                        // Add the included data to the relationship
                        $targetRelationship['included'][] = $includedMap[$mapKey];
                    }
                }
            }
        }
    }
}
