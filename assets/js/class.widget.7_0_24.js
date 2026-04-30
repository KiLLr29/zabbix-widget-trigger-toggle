class CWidgetTriggerToggleORS extends CWidget {

    onStart() {
        super.onStart();

        this._content_container = null;
        this._is_edit_mode = false;
        this._last_edit_mode = null;
        this._mode_timer = null;
        this._toggle_button = null;
        this._pending_toggle_action = null;

        this._events = {
            click: (event) => {
                if (this._isDashboardEditMode()) {
                    return;
                }

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

        this._content_container = this._target.querySelector('.trigger-toggle-widget');
        this._applyModeState();
        this._startModeWatcher();
    }

    onDeactivate() {
        this._stopModeWatcher();
        this._unbindButton();

        if (this._content_container !== null) {
            this._content_container.style.pointerEvents = '';
        }

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

        this._content_container = this._target.querySelector('.trigger-toggle-widget');
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

    _bindButton() {
        this._unbindButton();

        if (this._isDashboardEditMode()) {
            return;
        }

        const button = this._target.querySelector('.js-trigger-toggle');

        if (button !== null) {
            button.addEventListener('click', this._events.click);
            this._toggle_button = button;
        }
    }

    _unbindButton() {
        if (this._toggle_button !== null) {
            this._toggle_button.removeEventListener('click', this._events.click);
            this._toggle_button = null;
        }
    }

    _isDashboardEditMode() {
        if (this._is_edit_mode) {
            return true;
        }

        if (typeof this.isEditMode === 'function' && this.isEditMode()) {
            return true;
        }

        if (typeof this.getDashboard === 'function') {
            const dashboard = this.getDashboard();

            if (dashboard !== null && typeof dashboard.isEditMode === 'function' && dashboard.isEditMode()) {
                return true;
            }
        }

        const body = document.body;

        return body.classList.contains('dashboard-is-edit-mode')
            || body.classList.contains('dashboard-edit-mode')
            || body.classList.contains('dashboard-mode-edit');
    }

    _applyModeState() {
        const is_edit_mode = this._isDashboardEditMode();

        if (this._content_container !== null) {
            this._content_container.style.pointerEvents = is_edit_mode ? 'none' : '';
        }

        if (is_edit_mode) {
            this._unbindButton();
        }
        else {
            this._bindButton();
        }

        this._last_edit_mode = is_edit_mode;
    }

    _startModeWatcher() {
        this._stopModeWatcher();

        this._mode_timer = setInterval(() => {
            const is_edit_mode = this._isDashboardEditMode();

            if (this._last_edit_mode === null || this._last_edit_mode !== is_edit_mode) {
                this._applyModeState();
            }
        }, 300);
    }

    _stopModeWatcher() {
        if (this._mode_timer !== null) {
            clearInterval(this._mode_timer);
            this._mode_timer = null;
        }
    }
}
