$(document).ready(function () {
    var hasPayoutSelect = $('#payoutDriver').length > 0;
    var hasVendorSearchUrl = typeof payoutVendorSearchUrl !== 'undefined' && payoutVendorSearchUrl;
    var hasPayoutUpdateUrl = typeof payoutUpdateStatus !== 'undefined' && payoutUpdateStatus;

    if (hasPayoutSelect && hasVendorSearchUrl) {
        $('#payoutDriver').select2({
            minimumInputLength: 3,
            ajax: {
                url: payoutVendorSearchUrl,
                dataType: 'json',
                delay: 250,
                processResults: function (data) {
                    return {
                        results: $.map(data, function (drivers) {
                            return {
                                id: drivers.id,
                                text: drivers.name,
                            };
                        })
                    };
                },
                cache: true,
                error: function (jqXHR, textStatus, errorThrown) {
                    console.error("Error while fetching vendor data:", textStatus, errorThrown);
                }
            }
        });

        // Preload if vendor is set
        var vendorId = $('#payoutDriver').data('vendor-id');
        var vendorName = $('#payoutDriver').data('vendor-name');

        if (vendorId && vendorName) {
            var preloadOption = new Option(vendorName, vendorId, true, true);
            $('#payoutDriver').append(preloadOption).trigger('change');
        }
    }

    $('.open-payout-modal').on('click', function (e) {
        e.preventDefault();
        const payoutId = $(this).data('payout-id');
        const amount = $(this).data('amount');
        const vendor = $(this).data('vendor');
        const status = $(this).data('status');
        $('#modalPayoutId').val(payoutId);
        $('#modalAmount').text(amount);
        $('#modalVendor').text(vendor);
        $('#status').text('Success');
        $('#payoutModal').modal('show');
    });

    // Submit form with file + notes
    $('#payoutForm').on('submit', function (e) {
        e.preventDefault();
        if (!hasPayoutUpdateUrl) {
            Swal.fire('Error', 'Payout update URL is missing.', 'error');
            return;
        }
        const payoutId = $('#modalPayoutId').val();
        const formData = new FormData(this);

        $.ajax({
            url: payoutUpdateStatus.replace(':payoutId', payoutId),
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                $('#payoutModal').modal('hide');
                Swal.fire("Success", "Payout released successfully!", "success").then(() => {
                    window.location.reload();
                });
            },
            error: function (response) {
                Swal.fire("Error", "Something went wrong.", "error");
            }
        });
    });

    $('#resetBtn').click(function () {
        const form = $('#filterForm')[0];
        if (form) {
            form.reset();
        }
        $('#filterForm select').val(null).trigger('change');
        const baseUrl = window.location.origin + window.location.pathname;
        window.history.replaceState({}, document.title, baseUrl);
        window.location.reload();
    });

});
