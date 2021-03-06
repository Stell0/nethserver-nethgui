/*
 * Progress bar
 *
 * Copyright (C) 2011 Nethesis S.r.l.
 */
(function( $ ) {
    var SUPER = $.nethgui.Component;
    $.widget('nethgui.Progressbar', SUPER, {
        _deep: false,
        _create: function() {
            SUPER.prototype._create.apply(this);

            this._template = this.element.text();
            this._text = $('<span class="ProgressbarText"></span>');

            this.element.empty();
            this.element.progressbar();
            this.element.append(this._text);
        },
        _updateView: function(value) {
            var percent = parseInt(value);

            if(isNaN(percent) && typeof(value) === 'string') {
                this._text.text(value);
            } else {
                if(percent > 100) {
                    percent = 100;
                } else if(percent < 0) {
                    percent = 0;
                }
                this.element.progressbar('value', percent);
                this._text.text(this._template.replacePlaceholders(percent));
            }
        }
    });
}( jQuery ));
