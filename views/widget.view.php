<?php declare(strict_types = 0);

/**
 * Trigger toggle widget view.
 *
 * @var CView $this
 * @var array $data
 */

$enabled_status = defined('TRIGGER_STATUS_ENABLED') ? TRIGGER_STATUS_ENABLED : 0;
$is_enabled = (bool) $data['is_enabled'];

$state_label = $is_enabled ? _('Enabled') : _('Disabled');
$state_class = $is_enabled ? 'state-enabled' : 'state-disabled';

$toggle_button = (new CSubmit('trigger_toggle_submit', $state_label))
	->addClass('js-trigger-toggle')
	->addClass($state_class);

$toggle_form = (new CForm('post', (new Curl('zabbix.php'))
	->setArgument('action', 'widget.trigger_toggle_ors.toggle')
	->getUrl()))
	->addVar('toggle_action', $data['next_toggle_action'])
	->addVar('triggerids', $data['triggerids_csv'])
	->addVar('return_url', $_SERVER['REQUEST_URI'] ?? '')
	->addItem($toggle_button);

$summary = (new CDiv([
	(new CDiv([
		(new CSpan(_('Matched triggers')))->addClass('label'),
		(new CSpan((string) $data['total_count']))->addClass('value')
	]))->addClass('trigger-summary-item'),
	(new CDiv([
		(new CSpan(_('Enabled')))->addClass('label'),
		(new CSpan((string) $data['enabled_count']))->addClass('value')
	]))->addClass('trigger-summary-item'),
	(new CDiv([
		(new CSpan(_('Disabled')))->addClass('label'),
		(new CSpan((string) $data['disabled_count']))->addClass('value')
	]))->addClass('trigger-summary-item')
]))->addClass('trigger-summary');

$content = [
	(new CDiv($toggle_form))->addClass('trigger-toggle-wrap')
];

if ($data['show_summary']) {
	$content[] = $summary;
}

if ($data['toggle_error'] !== null) {
	$content[] = (new CMessageBox(ZBX_STYLE_MSG_BAD, $data['toggle_error']))->toString();
}

if ($data['show_trigger_list']) {
	$table = (new CTableInfo())->setHeader([_('Host'), _('Trigger'), _('State')]);

	foreach ($data['triggers'] as $trigger) {
		$status_text = (int) $trigger['status'] === $enabled_status ? _('Enabled') : _('Disabled');
		$status_class = (int) $trigger['status'] === $enabled_status ? 'status-enabled' : 'status-disabled';

		$table->addRow([
			$trigger['host_names'],
			$trigger['description'],
			(new CSpan($status_text))->addClass($status_class)
		]);
	}

	if (!$data['triggers']) {
		$table->setNoDataMessage(_('No triggers match selected filters.'));
	}

	$content[] = (new CDiv($table))->addClass('trigger-list-wrap');
}

(new CWidgetView($data))
	->addItem((new CDiv($content))->addClass('trigger-toggle-widget'))
	->show();
