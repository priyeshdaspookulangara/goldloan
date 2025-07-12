$(document).ready(function() {

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

    // --- Real-time Calculations ---

    // Function to calculate total repayable amount
    function calculateTotals() {
        const principal = parseFloat($('#principal_amount').val()) || 0;
        const interestRate = parseFloat($('#interest_rate').val()) || 0;
        const tenure = parseInt($('#loan_tenure').val()) || 0;

        if (principal > 0 && interestRate > 0 && tenure > 0) {
            const interest = (principal * (interestRate / 100) * tenure) / 12;
            const totalRepayable = principal + interest;

            $('#total-repayable-display').text('$' + totalRepayable.toFixed(2));
            $('#total_repayable').val(totalRepayable.toFixed(2));
        } else {
            $('#total-repayable-display').text('$0.00');
            $('#total_repayable').val('');
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
    $('#principal_amount, #interest_rate, #loan_tenure').on('input', calculateTotals);
    $('#loan_date, #loan_tenure').on('input', calculateDueDate);

    // Initial calculation on page load if values are pre-filled
    calculateTotals();
    calculateDueDate();

});
