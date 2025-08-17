$(document).ready(function() {

    // --- Client Search Logic on New Loan Page ---

    $('#search-client-btn').on('click', function() {
        const contactNumber = $('#search_contact').val();
        const resultDiv = $('#client-search-result');
        const formDiv = $('#client-details-form');

        if (!contactNumber) {
            resultDiv.html('<div class="alert alert-warning">Please enter a contact number to search.</div>');
            return;
        }

        $.ajax({
            url: 'ajax_get_client.php',
            type: 'GET',
            data: { contact_number: contactNumber },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const client = response.client;
                    $('#client_id').val(client.id);
                    $('#client_name').val(client.name).prop('readonly', true);
                    $('#contact_number').val(client.contact_number).prop('readonly', true);
                    $('#address').val(client.address).prop('readonly', true);
                    $('#id_proof_type').val(client.id_proof_type).prop('readonly', true);
                    $('#id_proof_number').val(client.id_proof_number).prop('readonly', true);
                    resultDiv.html('<div class="alert alert-success">Client found. Details populated above.</div>');
                    formDiv.slideDown();
                } else {
                    resultDiv.html('<div class="alert alert-danger">' + response.message + ' You can create a new client profile below.</div>');
                    $('#new-client-btn').click(); // Show a blank form
                }
            },
            error: function() {
                resultDiv.html('<div class="alert alert-danger">An error occurred while searching.</div>');
            }
        });
    });

    $('#new-client-btn').on('click', function() {
        const formDiv = $('#client-details-form');
        $('#client_id').val('');
        formDiv.find('input, textarea').val('').prop('readonly', false);
        $('#client-search-result').html('');
        formDiv.slideDown();
    });


    // --- New Loan Form Logic ---

    let itemIndex = 0;

    // Add a new collateral item row
    $('#add-item-btn').on('click', function() {
        const itemHtml = `
            <tr id="item-row-${itemIndex}">
                <td><input type="text" name="items[${itemIndex}][description]" class="form-control" required></td>
                <td><input type="number" step="0.01" name="items[${itemIndex}][weight]" class="form-control item-calc" required></td>
                <td><input type="number" name="items[${itemIndex}][purity]" class="form-control item-calc" required></td>
                <td><input type="text" name="items[${itemIndex}][location]" class="form-control" required></td>
                <td><input type="text" name="items[${itemIndex}][locker_number]" class="form-control"></td>
                <td><button type="button" class="btn btn-danger btn-sm remove-item-btn" data-row-id="${itemIndex}">Remove</button></td>
            </tr>
        `;
        $('#collateral-items-body').append(itemHtml);
        itemIndex++;
    });

    // Remove a collateral item row
    $(document).on('click', '.remove-item-btn', function() {
        const rowId = $(this).data('row-id');
        $(`#item-row-${rowId}`).remove();
        calculateTotals(); // Recalculate if an item is removed
    });

    // --- Real-time Calculations (Server-Side) ---

    function calculateTotals() {
        const principal = parseFloat($('#principal_amount').val()) || 0;
        const interestRate = parseFloat($('#interest_rate').val()) || 0;
        const tenure = parseInt($('#loan_tenure').val()) || 0;
        const type = $('#repayment_type').val();

        // Show/hide EMI display
        if (type === 'emi') {
            $('#emi-display-container').show();
        } else {
            $('#emi-display-container').hide();
        }

        if (principal > 0 && interestRate >= 0 && tenure > 0) {
            $.ajax({
                url: 'ajax_calculate_loan.php',
                type: 'POST',
                data: {
                    principal: principal,
                    rate: interestRate,
                    tenure: tenure,
                    type: type
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#total-repayable-display').text('$' + response.total_repayable.toFixed(2));
                        $('#total_repayable').val(response.total_repayable.toFixed(2));
                        if (type === 'emi') {
                            $('#emi-display').text('$' + response.emi_amount.toFixed(2));
                        }
                    } else {
                        // Handle error from backend, e.g., display a message
                        $('#total-repayable-display').text('$0.00');
                        $('#total_repayable').val('');
                        $('#emi-display').text('$0.00');
                    }
                },
                error: function() {
                    // Handle AJAX error
                     $('#total-repayable-display').text('Error!');
                }
            });
        } else {
            $('#total-repayable-display').text('$0.00');
            $('#total_repayable').val('');
            $('#emi-display').text('$0.00');
        }
    }

    // Function to calculate due date
    function calculateDueDate(){
        const loanDateStr = $('#loan_date').val();
        const tenure = parseInt($('#loan_tenure').val()) || 0;

        if(loanDateStr && tenure > 0){
            const loanDate = new Date(loanDateStr);
            loanDate.setMonth(loanDate.getMonth() + tenure);

            // Format date to YYYY-MM-DD for the input field
            const year = loanDate.getFullYear();
            const month = String(loanDate.getMonth() + 1).padStart(2, '0');
            const day = String(loanDate.getDate()).padStart(2, '0');

            $('#due_date').val(`${year}-${month}-${day}`);
        } else {
             $('#due_date').val('');
        }
    }


    // Event listeners for loan term inputs
    $('#principal_amount, #interest_rate, #loan_tenure, #repayment_type').on('input change', calculateTotals);
    $('#loan_date, #loan_tenure').on('input', calculateDueDate);

    // Initial calculation on page load if values are pre-filled
    calculateTotals();
    calculateDueDate();

});
