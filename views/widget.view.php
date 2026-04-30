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

$default_return_url = 'zabbix.php?action=dashboard.view';
$dashboardid = (string) ($data['dashboardid'] ?? '');

if ($dashboardid !== '' && ctype_digit($dashboardid)) {
	$default_return_url .= '&dashboardid='.$dashboardid;
}

$return_url = $_SERVER['HTTP_REFERER'] ?? '';

if (!is_string($return_url) || $return_url === '') {
	$return_url = $default_return_url;
}

$toggle_button = (new CSubmit('trigger_toggle_submit', $state_label))
	->addClass('js-trigger-toggle')
	->addClass($state_class);

$toggle_target = 'trigger_toggle_sink';

$toggle_form = (new CForm('post', (new Curl('zabbix.php'))
	->setArgument('action', 'widget.trigger_toggle_ors.toggle')
	->getUrl()))
	->setAttribute('target', $toggle_target)
	->addVar('toggle_action', $data['next_toggle_action'])
	->addVar('triggerids', $data['triggerids_csv'])
	->addVar('dashboardid', $dashboardid)
	->addVar('return_url', $return_url)
	->addItem($toggle_button);

$summary = (new CDiv([
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
	(new CDiv([
		(new CTag('iframe', true))
			->setAttribute('name', $toggle_target)
			->setAttribute('title', 'trigger-toggle-sink')
			->setAttribute('onload', "if (this.dataset.loaded === '1') { window.location.reload(); } this.dataset.loaded = '1';")
			->setAttribute('style', 'display:none;width:0;height:0;border:0;'),
		$toggle_form
	]))->addClass('trigger-toggle-wrap')
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
