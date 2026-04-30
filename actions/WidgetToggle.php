<?php declare(strict_types = 0);

namespace Modules\TriggerToggleORS\Actions;

use API;
use CController;
use CControllerResponseRedirect;

class WidgetToggle extends CController {

    protected function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        return $this->validateInput([
            'toggle_action' => 'required|in enable,disable',
            'triggerids' => 'string',
            'dashboardid' => 'string',
            'return_url' => 'string'
        ]);
    }

    protected function checkPermissions(): bool {
        return true;
    }

    protected function doAction(): void {
        $toggle_action = $this->getInput('toggle_action', '');
        $triggerids = $this->normalizeIds($this->getInput('triggerids', ''));

        if ($toggle_action !== '' && $triggerids) {
            $target_status = $toggle_action === 'disable'
                ? $this->getDisabledStatus()
                : $this->getEnabledStatus();

            $triggers = API::Trigger()->get([
                'output' => ['triggerid', 'status'],
                'triggerids' => $triggerids,
                'editable' => true
            ]);

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

        $return_url = $this->normalizeReturnUrl(
            $this->getInput('return_url', ''),
            (string) $this->getInput('dashboardid', '')
        );

        $this->setResponse(new CControllerResponseRedirect($return_url));
    }

    private function normalizeIds(string $triggerids): array {
        $ids = [];

        foreach (explode(',', $triggerids) as $id) {
            $id = trim($id);

            if ($id !== '' && preg_match('/^\d+$/', $id) === 1) {
                $ids[] = $id;
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

    private function normalizeReturnUrl($return_url, string $dashboardid): string {
        if (is_string($return_url) && $return_url !== '') {
            $parts = parse_url($return_url);

            if (is_array($parts) && array_key_exists('query', $parts) && is_string($parts['query'])) {
                parse_str($parts['query'], $query);

                if (($query['action'] ?? '') === 'dashboard.view') {
                    return $return_url;
                }
            }
        }

        $safe_url = 'zabbix.php?action=dashboard.view';

        if ($dashboardid !== '' && preg_match('/^\d+$/', $dashboardid) === 1) {
            $safe_url .= '&dashboardid='.$dashboardid;
        }

        return $safe_url;
    }
}
