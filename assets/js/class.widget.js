class CWidgetTriggerToggleORS extends CWidget {

    onInitialize() {
        super.onInitialize();

        this._is_edit_mode = false;
        this._toggle_button = null;
        this._content_container = null;
    }

    onStart() {
        super.onStart();

        this._content_container = this._target.querySelector('.trigger-toggle-widget');
        this._pending_toggle_action = null;
        this._events = {
            click: (event) => {
                if (this._isDashboardEditMode()) {
                    return;
                }

                if (!(event.target instanceof Element)) {
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

        this._bindToggleButton();
    }

    onActivate() {
        super.onActivate();

        if (this._content_container !== null) {
            this._content_container.style.pointerEvents = this._isDashboardEditMode() ? 'none' : '';
        }

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

        this._is_edit_mode = true;
        this._unbindToggleButton();

        if (this._content_container !== null) {
            this._content_container.style.pointerEvents = 'none';
        }
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

    _isDashboardEditMode() {
        if (this._is_edit_mode) {
            return true;
        }

        if (typeof this.isEditMode === 'function') {
            return this.isEditMode();
        }

        if (typeof this.getDashboard === 'function') {
            const dashboard = this.getDashboard();

            if (dashboard !== null && typeof dashboard.isEditMode === 'function') {
                return dashboard.isEditMode();
            }
        }

        const body = document.body;

        return body.classList.contains('dashboard-is-edit-mode')
            || body.classList.contains('dashboard-edit-mode')
            || body.classList.contains('dashboard-mode-edit');
    }

    _bindToggleButton() {
        this._unbindToggleButton();

        if (this._isDashboardEditMode()) {
            return;
        }

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
