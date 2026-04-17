class CWidgetTriggerToggleORS extends CWidget {

    onStart() {
        super.onStart();

        this._toggle_button = null;
        this._mode_timer = null;
        this._pending_toggle_action = null;
        this._events = {
            click: (event) => {
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
        this._startModeWatcher();
    }

    onDeactivate() {
        this._stopModeWatcher();
        this._unbindToggleButton();
        super.onDeactivate();
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
        if (typeof this.isEditMode === 'function' && this.isEditMode()) {
            return true;
        }

        if (typeof this.getDashboard === 'function') {
            const dashboard = this.getDashboard();

            if (dashboard !== null && typeof dashboard.isEditMode === 'function' && dashboard.isEditMode()) {
                return true;
            }
        }

        const url_params = new URLSearchParams(window.location.search);

        if (url_params.get('edit') === '1' || url_params.get('mode') === 'edit') {
            return true;
        }

        return document.querySelector(
            '.dashboard-is-edit-mode,' +
            '.dashboard-edit-mode,' +
            '.dashboard-mode-edit,' +
            '.is-edit-mode,' +
            '.dashboard-grid--edit,' +
            '[data-mode=\"edit\"]'
        ) !== null;
    }

    _applyModeState() {
        const content = this._target.querySelector('.trigger-toggle-widget');

        if (this._isDashboardEditMode()) {
            if (content !== null) {
                content.style.pointerEvents = 'none';
            }

            this._unbindToggleButton();
        }
        else {
            if (content !== null) {
                content.style.pointerEvents = '';
            }

            this._bindToggleButton();
        }
    }

    _startModeWatcher() {
        this._stopModeWatcher();

        this._mode_timer = window.setInterval(() => {
            this._applyModeState();
        }, 400);
    }

    _stopModeWatcher() {
        if (this._mode_timer !== null) {
            window.clearInterval(this._mode_timer);
            this._mode_timer = null;
        }
    }
}
