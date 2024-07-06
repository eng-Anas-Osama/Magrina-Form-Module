<div class="custom-checkout-form">
    {$form_html nofilter}
</div>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        var createAccountChoice = document.querySelector('input[name="create_account"]');
        var emailField = document.querySelector('#email');

        createAccountChoice.addEventListener('change', function() {
            if (this.value === '1') {
                emailField.style.display = 'block';
                emailField.required = true;
            } else {
                emailField.style.display = 'none';
                emailField.required = false;
            }
        });

        var governmentSelect = document.querySelector('#government');
        var stateSelect = document.querySelector('#state');

        governmentSelect.addEventListener('change', function() {
            // Here you would typically make an AJAX call to get the states for the selected government
            // For demonstration, we'll just populate with dummy data
            stateSelect.innerHTML = '<option value="1">State 1</option><option value="2">State 2</option>';
        });
    });
</script>