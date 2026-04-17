<?php declare(strict_types = 0);

namespace Modules\TriggerToggleORS\Actions;

use API;
use CControllerDashboardWidgetView;
use CControllerResponseData;
use Throwable;

class WidgetView extends CControllerDashboardWidgetView {

	protected function init(): void {
		parent::init();

		$this->addValidationRules([
			'toggle_action' => 'in enable,disable'
		]);
	}

	protected function doAction(): void {
		$triggers = $this->getTriggersByFilters();
		$toggle_error = null;

		$toggle_action = $this->getInput('toggle_action', '');
		if ($toggle_action !== '' && $triggers) {
			try {
				$this->toggleTriggers($triggers, $toggle_action);
				$triggers = $this->getTriggersByFilters();
			}
			catch (Throwable $e) {
				$toggle_error = $e->getMessage();
			}
		}

		$enabled_status = $this->getEnabledStatus();
		$enabled_count = 0;

		foreach ($triggers as $trigger) {
			if ((int) $trigger['status'] === $enabled_status) {
				$enabled_count++;
			}
		}

		$total_count = count($triggers);
		$disabled_count = $total_count - $enabled_count;
		$is_enabled = $total_count > 0 && $disabled_count === 0;

		$data = [
			'name' => $this->getInput('name', _('Trigger toggle')),
			'user' => [
				'debug_mode' => $this->getDebugMode()
			],
			'triggers' => $triggers,
			'total_count' => $total_count,
			'enabled_count' => $enabled_count,
			'disabled_count' => $disabled_count,
			'is_enabled' => $is_enabled,
			'next_toggle_action' => $is_enabled ? 'disable' : 'enable',
			'toggle_error' => $toggle_error,
			'show_trigger_list' => (bool) ($this->fields_values['show_trigger_list'] ?? true)
		];

		$this->setResponse(new CControllerResponseData($data));
	}

	private function getTriggersByFilters(): array {
		$options = [
			'output' => ['triggerid', 'description', 'status', 'priority'],
			'selectHosts' => ['hostid', 'name'],
			'sortfield' => ['priority', 'description'],
			'sortorder' => ZBX_SORT_DOWN,
			'editable' => true
		];

		$groupids = $this->normalizeIds($this->fields_values['groupids'] ?? []);
		if ($groupids) {
			$options['groupids'] = $groupids;
		}

		$hostids = $this->normalizeIds($this->fields_values['hostids'] ?? []);
		if ($hostids) {
			$options['hostids'] = $hostids;
		}

		$tags = $this->fields_values['tags'] ?? [];
		if (is_array($tags) && $tags) {
			if (array_key_exists('tags', $tags)) {
				if (!empty($tags['tags'])) {
					$options['tags'] = $tags['tags'];
				}

				if (array_key_exists('evaltype', $tags)) {
					$options['evaltype'] = $tags['evaltype'];
				}
			}
			else {
				$options['tags'] = $tags;
			}
		}

		$triggers = API::Trigger()->get($options);

		foreach ($triggers as &$trigger) {
			$host_names = [];
			foreach ($trigger['hosts'] as $host) {
				$host_names[] = $host['name'];
			}
			$trigger['host_names'] = implode(', ', $host_names);
		}
		unset($trigger);

		return $triggers;
	}

	private function toggleTriggers(array $triggers, string $toggle_action): void {
		$target_status = $toggle_action === 'disable'
			? $this->getDisabledStatus()
			: $this->getEnabledStatus();

		$updates = [];
		foreach ($triggers as $trigger) {
			if ((int) $trigger['status'] === $target_status) {
				continue;
			}

			$updates[] = [
				'triggerid' => $trigger['triggerid'],
				'status' => $target_status
			];
		}

		if ($updates) {
			API::Trigger()->update($updates);
		}
	}

	private function normalizeIds(array $values): array {
		$ids = [];

		foreach ($values as $value) {
			if (is_scalar($value) && preg_match('/^\d+$/', (string) $value) === 1) {
				$ids[] = (string) $value;
			}
		}

		return $ids;
	}

	private function getEnabledStatus(): int {
		return defined('TRIGGER_STATUS_ENABLED') ? TRIGGER_STATUS_ENABLED : 0;
	}

	private function getDisabledStatus(): int {
		return defined('TRIGGER_STATUS_DISABLED') ? TRIGGER_STATUS_DISABLED : 1;
	}
}
