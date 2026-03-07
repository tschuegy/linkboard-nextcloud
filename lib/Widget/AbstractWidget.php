<?php
declare(strict_types=1);
namespace OCA\LinkBoard\Widget;

/**
 * Base class for all LinkBoard widgets.
 *
 * Each widget knows how to build authenticated HTTP requests
 * to an external service and map the response into simple
 * key-value statistics displayed on the dashboard.
 *
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
abstract class AbstractWidget {

    /**
     * Unique widget identifier, e.g. 'proxmox'
     */
    abstract public function getId(): string;

    /**
     * Human-readable label, e.g. 'Proxmox VE'
     */
    abstract public function getLabel(): string;

    /**
     * Config fields the user must fill in (shown in the editor).
     *
     * @return array<array{key: string, label: string, type: string, required: bool, placeholder?: string}>
     */
    abstract public function getConfigFields(): array;

    /**
     * Keys of the statistics fields this widget produces.
     *
     * @return string[]
     */
    abstract public function getAllowedFields(): array;

    /**
     * Human-readable labels for each statistics field.
     *
     * @return array<string, string>  e.g. ['vms' => 'VMs', 'cpu' => 'CPU']
     */
    abstract public function getFieldLabels(): array;

    /**
     * Build one or more HTTP request specs that will be executed
     * by the proxy controller.
     *
     * @param  string $baseUrl   The service's base URL (from Service.href)
     * @param  array  $config    Decoded widgetConfig from the database
     * @return array<array{url: string, headers?: array, method?: string}>
     */
    abstract public function buildRequests(string $baseUrl, array $config): array;

    /**
     * Human-readable labels for each statistics field, optionally
     * derived from per-service config (e.g. dynamic mappings).
     * Default: delegates to the static getFieldLabels().
     *
     * @param  array $config  Decoded widgetConfig from the database
     * @return array<string, string>
     */
    public function getFieldLabelsForConfig(array $config): array {
        return $this->getFieldLabels();
    }

    /**
     * Extract key-value data from the API responses.
     *
     * @param  array $responses  Array of decoded JSON responses (one per request)
     * @param  array $config     Decoded widgetConfig
     * @return array<string, string|int|float>
     */
    abstract public function mapResponse(array $responses, array $config): array;

    /**
     * Whether this widget reads local system data instead of making HTTP requests.
     */
    public function isLocal(): bool {
        return false;
    }

    /**
     * Catalog representation for the frontend (no secrets).
     */
    public function toCatalog(): array {
        return [
            'id' => $this->getId(),
            'label' => $this->getLabel(),
            'configFields' => $this->getConfigFields(),
            'allowedFields' => $this->getAllowedFields(),
            'fieldLabels' => $this->getFieldLabels(),
        ];
    }
}
