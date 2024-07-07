document.addEventListener('DOMContentLoaded', function() {
    var governmentSelect = document.querySelector('select[name="government"]');
    var stateSelect = document.querySelector('select[name="state"]');
    var createAccountCheckbox = document.querySelector('input[name="create_account"]');
    var emailContainer = document.querySelector('.create-account-container');

    if (governmentSelect && stateSelect) {
        governmentSelect.addEventListener('change', function() {
            var governmentId = this.value;
            fetchStates(governmentId);
        });
    }

    if (createAccountCheckbox && emailContainer) {
        createAccountCheckbox.addEventListener('change', function() {
            emailContainer.style.display = this.checked ? 'flex' : 'none';
        });
    }

    function fetchStates(governmentId) {
        // Clear current options
        stateSelect.innerHTML = '<option value="">' + prestashop.translator.trans('Select a state', {}, 'Shop.Forms.Labels') + '</option>';

        if (governmentId) {
            fetch(prestashop.urls.base_url + 'modules/customcheckout/ajax.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=getStates&government_id=' + governmentId
            })
            .then(response => response.json())
            .then(data => {
                data.forEach(function(state) {
                    var option = document.createElement('option');
                    option.value = state.id;
                    option.textContent = state.name;
                    stateSelect.appendChild(option);
                });
            })
            .catch((error) => {
                console.error('Error:', error);
            });
        }
    }

    // Initialize state dropdown if government is pre-selected
    if (governmentSelect.value) {
        fetchStates(governmentSelect.value);
    }

    // Initialize email field visibility
    if (createAccountCheckbox.checked) {
        emailContainer.style.display = 'flex';
    }
});