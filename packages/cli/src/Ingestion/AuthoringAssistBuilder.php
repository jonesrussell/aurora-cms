<?php

declare(strict_types=1);

namespace Waaseyaa\CLI\Ingestion;

final class AuthoringAssistBuilder
{
    /**
     * @param array<string, mixed> $envelope
     * @param array<string, array<string, mixed>> $nodes
     * @param list<array<string, mixed>> $relationships
     * @param list<array<string, mixed>> $validationDiagnostics
     * @param array<string, mixed> $refreshSummary
     * @return array{
     *   suggestions:list<array<string, mixed>>,
     *   summary:array{suggestion_count:int,average_confidence:float},
     *   diagnostics:list<array<string, mixed>>
     * }
     */
    public function build(
        array $envelope,
        array $nodes,
        array $relationships,
        array $validationDiagnostics,
        array $refreshSummary,
    ): array {
        $suggestions = [];
        $diagnostics = [];

        $validationByNode = $this->validationByNode($validationDiagnostics);
        $inferenceByNode = $this->inferenceEdgesByNode($relationships);
        $items = array_values((array) ($envelope['items'] ?? []));
        $batchId = (string) ($envelope['batch_id'] ?? '');
        $refreshRequired = (bool) ($refreshSummary['needs_refresh'] ?? false);
        $refreshPrimaryCategory = trim((string) ($refreshSummary['primary_category'] ?? ''));

        foreach ($items as $index => $item) {
            if (!is_array($item)) {
                continue;
            }
            $sourceUri = trim((string) ($item['source_uri'] ?? ''));
            $nodeKey = $this->nodeKeyFromSourceUri($sourceUri);
            if ($nodeKey === '' || !array_key_exists($nodeKey, $nodes)) {
                $diagnostics[] = [
                    'code' => 'assist.unmapped_source_item',
                    'message' => sprintf('No mapped node found for source item index %d.', $index),
                    'location' => '/assist/items/' . $index,
                    'item_index' => $index,
                    'context' => [
                        'source_item_index' => $index,
                        'source_uri' => $sourceUri,
                    ],
                ];
                continue;
            }

            $node = $nodes[$nodeKey];
            $title = trim((string) ($node['title'] ?? $nodeKey));
            $workflowState = strtolower(trim((string) ($node['workflow_state'] ?? 'draft')));
            $validationSignals = $validationByNode[$nodeKey] ?? [];
            sort($validationSignals);
            $inferenceEdges = $inferenceByNode[$nodeKey] ?? [];
            sort($inferenceEdges);

            $primaryCue = $this->primaryCue($validationSignals, $inferenceEdges, $refreshRequired, $refreshPrimaryCategory);
            $supportingCues = $this->supportingCues($validationSignals, $inferenceEdges, $refreshRequired, $refreshPrimaryCategory);

            $confidence = $this->confidence(
                validationSignals: $validationSignals,
                inferenceEdges: $inferenceEdges,
                refreshRequired: $refreshRequired,
                refreshPrimaryCategory: $refreshPrimaryCategory,
                workflowState: $workflowState,
            );

            $suggestionId = sprintf('assist_%03d_%s', $index, $this->normalizeId($nodeKey));
            $body = $this->suggestedBody(
                title: $title,
                workflowState: $workflowState,
                primaryCue: $primaryCue,
                supportingCues: $supportingCues,
            );

            $suggestion = [
                'suggestion_id' => $suggestionId,
                'title' => sprintf('Editorial Assist: %s', $title),
                'body' => $body,
                'confidence' => $confidence,
                'source_item_index' => $index,
            ];

            $tags = $this->tags($workflowState, $validationSignals, $inferenceEdges, $refreshRequired);
            if ($tags !== []) {
                $suggestion['tags'] = $tags;
            }
            $suggestion['provenance'] = $sourceUri !== '' ? $sourceUri : $batchId;
            $suggestion['explainability'] = [
                'primary_cue' => $primaryCue,
                'supporting_cues' => $supportingCues,
                'inference_edges_used' => $inferenceEdges,
                'validation_signals' => $validationSignals,
            ];

            $suggestions[] = $suggestion;
        }

        usort(
            $suggestions,
            static function (array $left, array $right): int {
                $confidenceCompare = ((float) ($right['confidence'] ?? 0.0)) <=> ((float) ($left['confidence'] ?? 0.0));
                if ($confidenceCompare !== 0) {
                    return $confidenceCompare;
                }

                $indexCompare = ((int) ($left['source_item_index'] ?? 0)) <=> ((int) ($right['source_item_index'] ?? 0));
                if ($indexCompare !== 0) {
                    return $indexCompare;
                }

                return strcmp((string) ($left['suggestion_id'] ?? ''), (string) ($right['suggestion_id'] ?? ''));
            },
        );

        usort(
            $diagnostics,
            static fn(array $a, array $b): int => strcmp((string) ($a['location'] ?? ''), (string) ($b['location'] ?? '')),
        );

        return [
            'suggestions' => $suggestions,
            'summary' => [
                'suggestion_count' => count($suggestions),
                'average_confidence' => $this->averageConfidence($suggestions),
            ],
            'diagnostics' => $diagnostics,
        ];
    }

    /**
     * @param list<array<string, mixed>> $validationDiagnostics
     * @return array<string, list<string>>
     */
    private function validationByNode(array $validationDiagnostics): array
    {
        $byNode = [];
        foreach ($validationDiagnostics as $diagnostic) {
            $code = trim((string) ($diagnostic['code'] ?? ''));
            $location = trim((string) ($diagnostic['location'] ?? ''));
            if ($code === '' || $location === '') {
                continue;
            }
            if (preg_match('#^/nodes/([^/]+)/#', $location, $matches) !== 1) {
                continue;
            }
            $nodeKey = trim((string) ($matches[1] ?? ''));
            if ($nodeKey === '') {
                continue;
            }
            $byNode[$nodeKey][] = $code;
        }

        foreach ($byNode as &$codes) {
            $codes = array_values(array_unique($codes));
            sort($codes);
        }
        unset($codes);
        ksort($byNode);

        return $byNode;
    }

    /**
     * @param list<array<string, mixed>> $relationships
     * @return array<string, list<string>>
     */
    private function inferenceEdgesByNode(array $relationships): array
    {
        $byNode = [];
        foreach ($relationships as $relationship) {
            $source = trim((string) ($relationship['from'] ?? ''));
            $target = trim((string) ($relationship['to'] ?? ''));
            $edgeId = $source !== '' && $target !== '' ? ($source . '→' . $target) : '';
            if ($edgeId === '') {
                continue;
            }
            if (!array_key_exists('inference_source', $relationship)) {
                continue;
            }

            $byNode[$source][] = $edgeId;
            $byNode[$target][] = $edgeId;
        }

        foreach ($byNode as &$edges) {
            $edges = array_values(array_unique($edges));
            sort($edges);
        }
        unset($edges);
        ksort($byNode);

        return $byNode;
    }

    private function nodeKeyFromSourceUri(string $sourceUri): string
    {
        if (!str_starts_with($sourceUri, 'item://')) {
            return '';
        }

        return trim(substr($sourceUri, strlen('item://')));
    }

    /**
     * @param list<string> $validationSignals
     * @param list<string> $inferenceEdges
     */
    private function primaryCue(array $validationSignals, array $inferenceEdges, bool $refreshRequired, string $refreshPrimaryCategory): string
    {
        if ($validationSignals !== []) {
            return 'validation:' . $validationSignals[0];
        }
        if ($inferenceEdges !== []) {
            return 'inference:' . $inferenceEdges[0];
        }
        if ($refreshRequired && $refreshPrimaryCategory !== '') {
            return 'refresh:' . $refreshPrimaryCategory;
        }

        return 'semantic:baseline';
    }

    /**
     * @param list<string> $validationSignals
     * @param list<string> $inferenceEdges
     * @return list<string>
     */
    private function supportingCues(array $validationSignals, array $inferenceEdges, bool $refreshRequired, string $refreshPrimaryCategory): array
    {
        $ranked = [];
        foreach ($validationSignals as $code) {
            $ranked[] = ['cue' => 'validation:' . $code, 'weight' => 300];
        }
        foreach ($inferenceEdges as $edge) {
            $ranked[] = ['cue' => 'inference:' . $edge, 'weight' => 200];
        }
        if ($refreshRequired && $refreshPrimaryCategory !== '') {
            $ranked[] = ['cue' => 'refresh:' . $refreshPrimaryCategory, 'weight' => 100];
        }
        $ranked[] = ['cue' => 'semantic:context', 'weight' => 50];

        usort(
            $ranked,
            static function (array $left, array $right): int {
                $weight = ((int) ($right['weight'] ?? 0)) <=> ((int) ($left['weight'] ?? 0));
                if ($weight !== 0) {
                    return $weight;
                }

                return strcmp((string) ($left['cue'] ?? ''), (string) ($right['cue'] ?? ''));
            },
        );

        $cues = [];
        foreach ($ranked as $row) {
            $cue = (string) ($row['cue'] ?? '');
            if ($cue === '') {
                continue;
            }
            $cues[$cue] = true;
            if (count($cues) >= 4) {
                break;
            }
        }

        return array_keys($cues);
    }

    /**
     * @param list<string> $validationSignals
     * @param list<string> $inferenceEdges
     */
    private function confidence(
        array $validationSignals,
        array $inferenceEdges,
        bool $refreshRequired,
        string $refreshPrimaryCategory,
        string $workflowState,
    ): float {
        $value = 0.6;
        if ($validationSignals !== []) {
            $value -= 0.25;
        }
        if ($inferenceEdges !== []) {
            $value += 0.15;
        }
        if ($refreshRequired) {
            $value += match ($refreshPrimaryCategory) {
                'provenance_change' => 0.05,
                'relationship_change' => 0.1,
                'policy_change' => 0.08,
                'structural_drift' => 0.06,
                default => 0.03,
            };
        }
        if ($workflowState === 'published') {
            $value += 0.05;
        }

        $value = max(0.05, min(0.99, $value));
        return round($value, 4);
    }

    /**
     * @param list<string> $supportingCues
     */
    private function suggestedBody(
        string $title,
        string $workflowState,
        string $primaryCue,
        array $supportingCues,
    ): string {
        $cueSummary = implode(', ', $supportingCues);

        return sprintf(
            'Expand "%s" for workflow state "%s". Primary cue: %s. Supporting cues: %s.',
            $title,
            $workflowState !== '' ? $workflowState : 'draft',
            $primaryCue,
            $cueSummary !== '' ? $cueSummary : 'semantic:context',
        );
    }

    /**
     * @param list<string> $validationSignals
     * @param list<string> $inferenceEdges
     * @return list<string>
     */
    private function tags(string $workflowState, array $validationSignals, array $inferenceEdges, bool $refreshRequired): array
    {
        $tags = [];
        if ($workflowState !== '') {
            $tags[] = 'workflow:' . $workflowState;
        }
        if ($validationSignals !== []) {
            $tags[] = 'requires_validation_review';
        }
        if ($inferenceEdges !== []) {
            $tags[] = 'inference_supported';
        }
        if ($refreshRequired) {
            $tags[] = 'refresh_required';
        }

        $tags = array_values(array_unique($tags));
        sort($tags);
        return $tags;
    }

    private function normalizeId(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9_-]+/', '_', $normalized) ?? '';
        return trim($normalized, '_-');
    }

    /**
     * @param list<array<string, mixed>> $suggestions
     */
    private function averageConfidence(array $suggestions): float
    {
        if ($suggestions === []) {
            return 0.0;
        }

        $sum = 0.0;
        foreach ($suggestions as $suggestion) {
            $sum += (float) ($suggestion['confidence'] ?? 0.0);
        }

        return round($sum / count($suggestions), 4);
    }
}
