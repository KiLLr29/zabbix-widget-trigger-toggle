class CWidgetTriggerToggleORS extends CWidget {

    onInitialize() {
        super.onInitialize();

        this._toggle_button = null;
        this._pending_toggle_action = null;
        this._events = {
            click: (event) => {
                event.preventDefault();
                event.stopPropagation();

                if (this._state !== WIDGET_STATE_ACTIVE) {
                    return;
                }

                const button = event.currentTarget;

                if (!(button instanceof HTMLElement)) {
                    return;
                }

                const action = button.dataset.toggleAction ?? '';

                if (action === '') {
                    return;
                }

                this._pending_toggle_action = action;
                this._startUpdating();
            }
        };
    }

    onActivate() {
        super.onActivate();
        this._bindToggleButton();
    }

    onDeactivate() {
        this._unbindToggleButton();
        super.onDeactivate();
    }

    onEdit() {
        if (typeof super.onEdit === 'function') {
            super.onEdit();
        }

        this._unbindToggleButton();
    }

    processUpdateResponse(response) {
        super.processUpdateResponse(response);
        this._bindToggleButton();
    }

    getUpdateRequestData() {
        const update_request_data = super.getUpdateRequestData();

        if (this._pending_toggle_action !== null) {
            update_request_data.toggle_action = this._pending_toggle_action;
            this._pending_toggle_action = null;
        }

        return update_request_data;
    }

    _bindToggleButton() {
        this._unbindToggleButton();

        const button = this._target.querySelector('.js-trigger-toggle');

        if (button !== null) {
            button.addEventListener('click', this._events.click);
            this._toggle_button = button;
        }
    }

    _unbindToggleButton() {
        if (this._toggle_button !== null) {
            this._toggle_button.removeEventListener('click', this._events.click);
            this._toggle_button = null;
        }
    }
}
