<?php declare(strict_types = 0);

namespace Modules\TriggerToggleORS\Actions;

use API;
use CController;
use CControllerResponseData;

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

        $this->setResponse(new CControllerResponseData([]));
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

}
