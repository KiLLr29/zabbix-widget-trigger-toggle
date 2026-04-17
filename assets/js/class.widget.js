class CWidgetTriggerToggle extends CWidget {

    onStart() {
        super.onStart();

        this._pending_toggle_action = null;
        this._events = {
            click: (event) => {
                if (this._isDashboardEditMode()) {
                    return;
                }

                const button = event.target.closest('.js-trigger-toggle');

                if (!button) {
                    return;
                }

                if (this._state !== WIDGET_STATE_ACTIVE) {
                    return;
                }

                const action = button.dataset.toggleAction;
                this._pending_toggle_action = action;
                this._startUpdating();
            }
        };
    }

    onActivate() {
        super.onActivate();
        this._target.addEventListener('click', this._events.click);
    }

    onDeactivate() {
        this._target.removeEventListener('click', this._events.click);
        super.onDeactivate();
    }

    getUpdateRequestData() {
        const update_request_data = super.getUpdateRequestData();

        if (this._pending_toggle_action !== null) {
            update_request_data.toggle_action = this._pending_toggle_action;
            this._pending_toggle_action = null;
        }

        return update_request_data;
    }

    _isDashboardEditMode() {
        const body = document.body;

        return body.classList.contains('dashboard-is-edit-mode')
            || body.classList.contains('dashboard-edit-mode')
            || body.classList.contains('dashboard-mode-edit');
    }
}
