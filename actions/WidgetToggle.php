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

                if ($toggle_action === 'disable') {
                    $this->closeActiveProblemsForTriggers(array_column($updates, 'triggerid'));
                }
            }
        }

        $this->setResponse(new CControllerResponseData([]));
    }

    private function closeActiveProblemsForTriggers(array $triggerids): void {
        $eventids = $this->getClosableProblemEventIds($triggerids);

        if (!$eventids) {
            return;
        }

        $data = [
            'action' => $this->getProblemUpdateCloseAction() | $this->getProblemUpdateMessageAction(),
            'message' => $this->buildProblemCloseMessage()
        ];

        $chunk_size = defined('ZBX_DB_MAX_INSERTS') ? ZBX_DB_MAX_INSERTS : 1000;

        foreach (array_chunk($eventids, $chunk_size) as $eventid_chunk) {
            $data['eventids'] = $eventid_chunk;
            API::Event()->acknowledge($data);
        }
    }

    private function getClosableProblemEventIds(array $triggerids): array {
        $problems = API::Problem()->get([
            'output' => ['eventid', 'objectid'],
            'objectids' => array_values(array_unique($triggerids)),
            'source' => $this->getEventSourceTriggers(),
            'object' => $this->getEventObjectTrigger()
        ]);

        if (!$problems) {
            return [];
        }

        $problem_triggerids = array_values(array_unique(array_column($problems, 'objectid')));
        $closable_triggers = API::Trigger()->get([
            'output' => ['triggerid'],
            'triggerids' => $problem_triggerids,
            'editable' => true,
            'filter' => [
                'manual_close' => $this->getTriggerManualCloseAllowedValue()
            ]
        ]);

        if (!$closable_triggers) {
            return [];
        }

        $closable_triggerids = array_flip(array_column($closable_triggers, 'triggerid'));
        $eventids = [];

        foreach ($problems as $problem) {
            if (array_key_exists($problem['objectid'], $closable_triggerids)) {
                $eventids[] = $problem['eventid'];
            }
        }

        return array_values(array_unique($eventids));
    }

    private function buildProblemCloseMessage(): string {
        $user = $this->getCurrentUserDisplayName();
        $datetime = $this->getCurrentDateTime();

        return sprintf('Пользователь "%s" выключил триггеры в "%s"', $user, $datetime);
    }

    private function getCurrentUserDisplayName(): string {
        $user_data = \CWebUser::$data ?? [];

        if (!is_array($user_data)) {
            return 'unknown';
        }

        $alias = trim((string) ($user_data['alias'] ?? ($user_data['username'] ?? '')));
        $name = trim((string) ($user_data['name'] ?? ''));
        $surname = trim((string) ($user_data['surname'] ?? ''));

        if ($alias !== '' && function_exists('getUserFullname')) {
            return getUserFullname([
                'alias' => $alias,
                'name' => $name,
                'surname' => $surname
            ]);
        }

        $full_name = trim($name.' '.$surname);

        if ($alias !== '' && $full_name !== '') {
            return $alias.' ('.$full_name.')';
        }

        if ($alias !== '') {
            return $alias;
        }

        return $full_name !== '' ? $full_name : 'unknown';
    }

    private function getCurrentDateTime(): string {
        $timestamp = time();

        if (function_exists('zbx_date2str') && defined('DATE_TIME_FORMAT_SECONDS')) {
            return zbx_date2str(DATE_TIME_FORMAT_SECONDS, $timestamp);
        }

        return date('Y-m-d H:i:s', $timestamp);
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

    private function getEventSourceTriggers(): int {
        return defined('EVENT_SOURCE_TRIGGERS') ? EVENT_SOURCE_TRIGGERS : 0;
    }

    private function getEventObjectTrigger(): int {
        return defined('EVENT_OBJECT_TRIGGER') ? EVENT_OBJECT_TRIGGER : 0;
    }

    private function getProblemUpdateCloseAction(): int {
        return defined('ZBX_PROBLEM_UPDATE_CLOSE') ? ZBX_PROBLEM_UPDATE_CLOSE : 0x01;
    }

    private function getProblemUpdateMessageAction(): int {
        return defined('ZBX_PROBLEM_UPDATE_MESSAGE') ? ZBX_PROBLEM_UPDATE_MESSAGE : 0x04;
    }

    private function getTriggerManualCloseAllowedValue(): int {
        return defined('ZBX_TRIGGER_MANUAL_CLOSE_ALLOWED') ? ZBX_TRIGGER_MANUAL_CLOSE_ALLOWED : 1;
    }

}
