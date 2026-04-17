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
        $fields = [
            'toggle_action' => 'required|in enable,disable',
            'triggerids' => 'string',
            'return_url' => 'string'
        ];

        return $this->validateInput($fields);
    }

    protected function checkPermissions(): bool {
        return true;
    }

    protected function doAction(): void {
        $triggerids = $this->normalizeIds($this->getInput('triggerids', ''));
        $toggle_action = $this->getInput('toggle_action', '');

        if ($triggerids && $toggle_action !== '') {
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

        $return_url = $this->getInput('return_url', '');

        if (!is_string($return_url) || $return_url === '') {
            $return_url = (new \Curl('zabbix.php'))
                ->setArgument('action', 'dashboard.view')
                ->getUrl();
        }

        $this->setResponse(new CControllerResponseRedirect($return_url));
    }

    private function normalizeIds(string $values): array {
        $ids = [];

        foreach (explode(',', $values) as $value) {
            $value = trim($value);

            if ($value !== '' && preg_match('/^\d+$/', $value) === 1) {
                $ids[] = $value;
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
