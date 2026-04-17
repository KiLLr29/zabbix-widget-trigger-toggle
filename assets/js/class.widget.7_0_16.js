class CWidgetTriggerToggleORS extends CWidget {

    onStart() {
        super.onStart();

        this._content_container = null;
        this._is_edit_mode = false;
        this._pending_toggle_action = null;
        this._events = {
            click: (event) => {
                if (!(event.target instanceof Element)) {
                    return;
                }

                const button = event.target.closest('.js-trigger-toggle');

                if (!button) {
                    return;
                }

                event.preventDefault();

                if (this._state !== WIDGET_STATE_ACTIVE) {
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
        this._applyEditModeState();
    }

    onDeactivate() {
        this._unbindClickHandler();
        super.onDeactivate();
    }

    onEdit() {
        if (typeof super.onEdit === 'function') {
            super.onEdit();
        }

        this._is_edit_mode = true;
        this._applyEditModeState();
    }

    processUpdateResponse(response) {
        super.processUpdateResponse(response);

        this._content_container = this._target.querySelector('.trigger-toggle-widget');
        this._applyEditModeState();
    }

    getUpdateRequestData() {
        const update_request_data = super.getUpdateRequestData();

        if (this._pending_toggle_action !== null) {
            update_request_data.toggle_action = this._pending_toggle_action;
            this._pending_toggle_action = null;
        }

        return update_request_data;
    }

    _bindClickHandler() {
        this._unbindClickHandler();

        if (this._content_container === null) {
            return;
        }

        this._content_container.addEventListener('click', this._events.click);
    }

    _unbindClickHandler() {
        if (this._content_container !== null) {
            this._content_container.removeEventListener('click', this._events.click);
        }
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

        return false;
    }

    _applyEditModeState() {
        if (this._content_container === null) {
            return;
        }

        if (this._isDashboardEditMode()) {
            this._content_container.style.pointerEvents = 'none';
            this._unbindClickHandler();
        }
        else {
            this._content_container.style.pointerEvents = '';
            this._bindClickHandler();
        }
    }
}
