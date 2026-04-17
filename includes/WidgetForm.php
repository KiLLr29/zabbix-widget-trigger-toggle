<?php declare(strict_types = 0);

namespace Modules\ItemMAX\Includes;

use Zabbix\Widgets\CWidgetForm;
use Zabbix\Widgets\Fields\CWidgetFieldCheckBox;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectGroup;
use Zabbix\Widgets\Fields\CWidgetFieldMultiSelectHost;
use Zabbix\Widgets\Fields\CWidgetFieldTags;

class WidgetForm extends CWidgetForm {

	public function addFields(): self {
		return $this
			->addField(
				new CWidgetFieldMultiSelectGroup('groupids', _('Host groups'))
			)
			->addField(
				new CWidgetFieldMultiSelectHost('hostids', _('Hosts'))
			)
			->addField(
				new CWidgetFieldTags('tags', _('Tags'))
			)
			->addField(
				(new CWidgetFieldCheckBox('show_trigger_list', _('Show trigger list')))->setDefault(1)
			);
	}
}
