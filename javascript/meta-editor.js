(function ($) {
    $.entwine('ss', function ($) {

        /* counter */
        $('body').append($('<div id="MetaEditorCharCounter"></div>').hide());

        $('.meta-editor .ss-gridfield-item input, .meta-editor .ss-gridfield-item textarea').entwine({

            onkeydown: function (e) {
                if ((e.which && e.which == 13) || (e.keyCode && e.keyCode == 13)) {
                    e.preventDefault();
                    $(this).trigger('change');
                }
            },

            onkeyup: function () {
                $('#MetaEditorCharCounter').html($(this).val().trim().length);
            },

            onfocusin: function () {
                $('.cms-edit-form').removeClass('changed');
                $('#MetaEditorCharCounter').show();
                $('#MetaEditorCharCounter').html($(this).val().trim().length);
            },

            onfocusout: function () {
                $('#MetaEditorCharCounter').hide();
            },

            onchange: function () {

                // prevent changes to the form / popup
                $('.cms-edit-form').removeClass('changed');

                var $this = $(this);
                var id = $this.closest('tr').attr('data-id');
                var url = $this.closest('.ss-gridfield').attr('data-url') + "/update/" + id;
                var data = encodeURIComponent($this.attr('name')) + '=' + encodeURIComponent($(this).val());
                $this.closest('td').addClass('saving');

                $.ajax({
                    type: "POST",
                    url: url,
                    data: data,
                    success: function (data, textStatus) {
                        $this.closest('td').attr('class', '');
                        if (data.errors.length) {
                            $this.closest('td').addClass('has-warning');
                            data.errors.forEach(function (error) {
                                $this.closest('td').addClass(error)
                            });
                        } else {
                            $this.closest('td').addClass('has-success');
                        }
                        $('.cms-edit-form').removeClass('changed');
                    },
                    error: function (data, textStatus) {
                        $this.closest('td').attr('class', '');
                        $this.closest('td').addClass('error');
                        alert(data.responseText);
                    },
                    dataType: 'json'
                });
            }
        });

    });

}(jQuery));
