<?php declare(strict_types = 0);

/**
 * Trigger toggle widget form view.
 *
 * @var CView $this
 * @var array $data
 */

(new CWidgetFormView($data))
	->addField(
		new CWidgetFieldMultiSelectGroupView($data['fields']['groupids'])
	)
	->addField(
		new CWidgetFieldMultiSelectHostView($data['fields']['hostids'])
	)
	->addField(
		new CWidgetFieldTagsView($data['fields']['tags'])
	)
	->addField(
		new CWidgetFieldCheckBoxView($data['fields']['show_trigger_list'])
	)
	->show();
