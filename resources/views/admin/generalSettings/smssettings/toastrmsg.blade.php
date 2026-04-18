<script>
    $(document).ready(function() {
        function notifySuccess(message) {
            toastr.success(message, 'Success', {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-bottom-right'
            });
        }

        function notifyError(message) {
            toastr.error(message || 'An unexpected error occurred. Please try again.', 'Error', {
                closeButton: true,
                progressBar: true,
                positionClass: 'toast-bottom-right'
            });
        }

        $('.smssettingform').on('submit', function(event) {
            event.preventDefault();
            const $form = $(this);

            $.ajax({
                url: $form.attr('action'),
                type: $form.attr('method') || 'POST',
                data: $form.serialize(),
                success: function(response) {
                    if (response && response.error) {
                        notifyError(response.error);
                        return;
                    }

                    notifySuccess(response.message || '{{ trans('global.data_has_been_submitted') }}');
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};
                    notifyError(response.error || response.message || 'An error occurred while saving SMS settings.');
                }
            });
        });

        $('#autofillotp').on('change', function() {
            const $toggle = $(this);
            const status = $toggle.is(':checked') ? 'Active' : 'Inactive';

            $.ajax({
                url: "{{ route('admin.update-auto-fill-otp') }}",
                type: 'POST',
                data: {
                    status: status,
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    notifySuccess(response.message || 'OTP auto-fill updated successfully.');
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};
                    $toggle.prop('checked', !$toggle.is(':checked'));
                    notifyError(response.error || response.message || 'Unable to update OTP auto-fill.');
                }
            });
        });

        $('.statusdata').on('change', function() {
            const $toggle = $(this);

            if (!$toggle.is(':checked')) {
                notifyError('Keep at least one SMS provider active.');
                $toggle.prop('checked', true);
                return;
            }

            $.ajax({
                url: $toggle.data('url'),
                type: 'POST',
                data: {
                    status: 'Active',
                    userValue: $toggle.data('user-value'),
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response && response.success === false) {
                        $toggle.prop('checked', false);
                        notifyError(response.message || 'Unable to update the active SMS provider.');
                        return;
                    }

                    notifySuccess('SMS provider updated successfully.');
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};
                    $toggle.prop('checked', false);
                    notifyError(response.error || response.message || 'Unable to update the active SMS provider.');
                }
            });
        });
    });
</script>
