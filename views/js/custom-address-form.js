document.addEventListener('DOMContentLoaded', function() {
    var createAccountCheckbox = document.querySelector('input[name="create_account"]');
    var emailField = document.querySelector('input[name="email"]').closest('.form-group');
    
    emailField.style.display = 'none';
    
    createAccountCheckbox.addEventListener('change', function() {
        if (this.checked) {
            emailField.style.display = 'block';
        } else {
            emailField.style.display = 'none';
        }
    });
    governmentSelect.addEventListener('change', function() {
        var governmentId = this.value;
        fetchStates(governmentId);
    });

    function fetchStates(governmentId) {
        fetch('/modules/customcheckout/ajax.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=getStates&government_id=' + governmentId
        })
        .then(response => response.json())
        .then(data => {
            stateSelect.innerHTML = '';
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
});