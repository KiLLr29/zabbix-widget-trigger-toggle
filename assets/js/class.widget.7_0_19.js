class CWidgetTriggerToggleORS extends CWidget {

    onStart() {
        super.onStart();

        this._toggle_button = null;
        this._is_edit_mode = false;
        this._pending_toggle_action = null;
        this._events = {
            click: (event) => {
                if (this._isDashboardEditMode()) {
                    return;
                }

                event.preventDefault();

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
        this._applyModeState();
    }

    onDeactivate() {
        this._unbindToggleButton();
        super.onDeactivate();
    }

    onEdit() {
        if (typeof super.onEdit === 'function') {
            super.onEdit();
        }

        this._is_edit_mode = true;
        this._applyModeState();
    }

    processUpdateResponse(response) {
        super.processUpdateResponse(response);
        this._applyModeState();
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

    _isDashboardEditMode() {
        if (typeof this.isEditMode === 'function') {
            return this.isEditMode();
        }

        if (typeof this.getDashboard === 'function') {
            const dashboard = this.getDashboard();

            if (dashboard !== null && typeof dashboard.isEditMode === 'function') {
                return dashboard.isEditMode();
            }
        }

        return this._is_edit_mode;
    }

    _applyModeState() {
        const content = this._target.querySelector('.trigger-toggle-widget');
        const is_edit_mode = this._isDashboardEditMode();

        this._is_edit_mode = is_edit_mode;

        if (content !== null) {
            content.style.pointerEvents = is_edit_mode ? 'none' : '';
        }

        if (is_edit_mode) {
            this._unbindToggleButton();
        }
        else {
            this._bindToggleButton();
        }
    }
}
