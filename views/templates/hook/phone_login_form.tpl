<form action="{$link->getModuleLink('customcheckout', 'phoneverification')}" method="post" id="phone-login-form">
    <div class="form-group">
        <label for="phone_number">{l s='Phone Number' mod='customcheckout'}</label>
        <input type="tel" class="form-control" id="phone_number" name="phone_number" required>
    </div>
    <button type="submit" class="btn btn-primary">
        {l s='Login with Phone' mod='customcheckout'}
    </button>
</form>